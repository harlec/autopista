<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/PDFExtractor.php';

header('Content-Type: application/json');

if (!isset($_FILES['pdf_file'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
    exit;
}

$file = $_FILES['pdf_file'];
$proveedor_id = isset($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;

// Validar el archivo
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    exit;
}

if ($file['type'] !== 'application/pdf') {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'El archivo no debe superar los 10MB']);
    exit;
}

// Guardar el archivo
$upload_result = uploadFile($file);

if (!$upload_result['success']) {
    echo json_encode(['success' => false, 'message' => $upload_result['message']]);
    exit;
}

$pdf_path = __DIR__ . '/../../assets/' . $upload_result['path'];

try {
    // Conexión a base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si existe el autoloader de Composer
    $has_composer = file_exists(__DIR__ . '/../../vendor/autoload.php');
    
    if ($has_composer) {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }
    
    // Usar el nuevo extractor personalizado
    $extractor = new PDFExtractor($db);
    $extracted_data = $extractor->extractData($pdf_path, $proveedor_id);
    
    // Agregar información del archivo
    $extracted_data['archivo_pdf'] = $upload_result['filename'];
    
    // Obtener tipo de cambio si la moneda no es PEN
    if (!empty($extracted_data['moneda']) && $extracted_data['moneda'] !== 'PEN') {
        $query_tc = "SELECT tipo_cambio FROM tipos_cambio 
                     WHERE moneda_origen = :moneda 
                       AND moneda_destino = 'PEN' 
                     ORDER BY fecha DESC LIMIT 1";
        $stmt_tc = $db->prepare($query_tc);
        $stmt_tc->bindParam(':moneda', $extracted_data['moneda']);
        $stmt_tc->execute();
        $tc = $stmt_tc->fetch(PDO::FETCH_ASSOC);
        
        if ($tc) {
            $extracted_data['tipo_cambio'] = $tc['tipo_cambio'];
            $extracted_data['monto_total_pen'] = round($extracted_data['monto_total'] * $tc['tipo_cambio'], 2);
        } else {
            // Si no hay tipo de cambio, usar 1
            $extracted_data['tipo_cambio'] = 1.0;
            $extracted_data['tipo_cambio_warning'] = 'No se encontró tipo de cambio para ' . $extracted_data['moneda'];
        }
    } else {
        // Si es PEN o no se detectó moneda, tipo de cambio 1
        $extracted_data['moneda'] = 'PEN';
        $extracted_data['tipo_cambio'] = 1.0;
    }
    
    // Si se detectó o especificó un proveedor, obtener su información
    if (!empty($extracted_data['proveedor_id'])) {
        $query = "SELECT nombre, tiene_plantilla, moneda_predeterminada FROM proveedores WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $extracted_data['proveedor_id']);
        $stmt->execute();
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($proveedor) {
            $extracted_data['proveedor_nombre'] = $proveedor['nombre'];
            $extracted_data['tiene_plantilla'] = $proveedor['tiene_plantilla'] == 1;
            
            // Si el proveedor tiene moneda predeterminada y no se detectó, usarla
            if (empty($extracted_data['moneda']) && !empty($proveedor['moneda_predeterminada'])) {
                $extracted_data['moneda'] = $proveedor['moneda_predeterminada'];
            }
        }
    }
    
    // Si la extracción fue exitosa
    if ($extracted_data['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Datos extraídos correctamente',
            'data' => $extracted_data,
            'debug' => [
                'metodo' => $extracted_data['metodo'] ?? 'desconocido',
                'plantilla_usada' => $extracted_data['plantilla_usada'] ?? null,
                'tiene_composer' => $has_composer
            ]
        ]);
    } else {
        // Si no se pudo extraer pero el archivo se subió
        echo json_encode([
            'success' => true,
            'message' => 'PDF cargado. Complete los datos manualmente.',
            'data' => [
                'archivo_pdf' => $upload_result['filename'],
                'fecha_emision' => date('Y-m-d'),
                'proveedor_id' => $proveedor_id
            ],
            'note' => $extracted_data['message'] ?? 'No se pudieron extraer datos automáticamente'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => true,
        'message' => 'PDF cargado pero no se pudieron extraer datos automáticamente',
        'data' => [
            'archivo_pdf' => $upload_result['filename'],
            'fecha_emision' => date('Y-m-d'),
            'proveedor_id' => $proveedor_id
        ],
        'error' => $e->getMessage()
    ]);
}
?>
