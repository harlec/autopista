<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar campos requeridos
$required_fields = ['proveedor_id', 'numero_factura', 'fecha_emision', 'fecha_vencimiento', 'monto_total', 'moneda'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
        exit;
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Preparar datos
    $proveedor_id = intval($_POST['proveedor_id']);
    $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
    $numero_factura = sanitizeInput($_POST['numero_factura']);
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $monto_total = floatval($_POST['monto_total']);
    $moneda = sanitizeInput($_POST['moneda']);
    $igv = !empty($_POST['igv']) ? floatval($_POST['igv']) : 0;
    $subtotal = !empty($_POST['subtotal']) ? floatval($_POST['subtotal']) : ($monto_total - $igv);
    $descripcion = !empty($_POST['descripcion']) ? sanitizeInput($_POST['descripcion']) : null;
    $archivo_pdf = !empty($_POST['archivo_pdf']) ? sanitizeInput($_POST['archivo_pdf']) : null;
    $estado = !empty($_POST['estado']) ? sanitizeInput($_POST['estado']) : 'pendiente';
    
    // Verificar si ya existe una factura con el mismo número del mismo proveedor
    $query = "SELECT id FROM facturas WHERE numero_factura = :numero_factura AND proveedor_id = :proveedor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_factura', $numero_factura);
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una factura con ese número para este proveedor']);
        exit;
    }
    
    // Insertar factura
    $query = "INSERT INTO facturas (
        proveedor_id, categoria_id, numero_factura, fecha_emision, fecha_vencimiento,
        monto_total, moneda, igv, subtotal, descripcion, archivo_pdf, estado
    ) VALUES (
        :proveedor_id, :categoria_id, :numero_factura, :fecha_emision, :fecha_vencimiento,
        :monto_total, :moneda, :igv, :subtotal, :descripcion, :archivo_pdf, :estado
    )";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    $stmt->bindParam(':categoria_id', $categoria_id);
    $stmt->bindParam(':numero_factura', $numero_factura);
    $stmt->bindParam(':fecha_emision', $fecha_emision);
    $stmt->bindParam(':fecha_vencimiento', $fecha_vencimiento);
    $stmt->bindParam(':monto_total', $monto_total);
    $stmt->bindParam(':moneda', $moneda);
    $stmt->bindParam(':igv', $igv);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':archivo_pdf', $archivo_pdf);
    $stmt->bindParam(':estado', $estado);
    
    if ($stmt->execute()) {
        $factura_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Factura guardada correctamente',
            'factura_id' => $factura_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la factura']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
