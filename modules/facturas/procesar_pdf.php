<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_FILES['pdf_file'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
    exit;
}

$file = $_FILES['pdf_file'];

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

// Intentar extraer datos del PDF
// NOTA: Esta funcionalidad requiere instalar la librería smalot/pdfparser
// Ejecutar: composer require smalot/pdfparser

try {
    // Verificar si existe el autoloader de Composer
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdf_path);
        $text = $pdf->getText();
        
        // Extraer datos usando patrones comunes
        $extracted_data = [
            'numero_factura' => extractInvoiceNumber($text),
            'fecha_emision' => extractDate($text),
            'ruc' => extractRUC($text),
            'monto_total' => extractTotalAmount($text),
            'archivo_pdf' => $upload_result['filename']
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Datos extraídos correctamente',
            'data' => $extracted_data,
            'raw_text' => substr($text, 0, 500) // Primeros 500 caracteres para debug
        ]);
        
    } else {
        // Si no está instalada la librería, extraer datos básicos del nombre del archivo
        $extracted_data = [
            'archivo_pdf' => $upload_result['filename'],
            'fecha_emision' => date('Y-m-d')
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'PDF cargado. Ingrese los datos manualmente.',
            'data' => $extracted_data,
            'note' => 'Para extracción automática, instale: composer require smalot/pdfparser'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => true,
        'message' => 'PDF cargado pero no se pudieron extraer datos automáticamente',
        'data' => [
            'archivo_pdf' => $upload_result['filename'],
            'fecha_emision' => date('Y-m-d')
        ],
        'error' => $e->getMessage()
    ]);
}

// Funciones auxiliares para extracción de datos
function extractInvoiceNumber($text) {
    // Patrones comunes para número de factura
    $patterns = [
        '/(?:FACTURA|FACTURA ELECTR[OÓ]NICA|N[°ºª]|NRO\.?|N[UÚ]MERO)[\s:]*([A-Z0-9\-]+)/i',
        '/([FBE]\d{3}-\d+)/i', // Formato F001-00001234
        '/SERIE[\s:]*([A-Z0-9\-]+)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return null;
}

function extractDate($text) {
    // Buscar fechas en diversos formatos
    $patterns = [
        '/FECHA DE EMISI[OÓ]N[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i',
        '/FECHA[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i',
        '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            // Convertir a formato Y-m-d
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "$year-$month-$day";
        }
    }
    
    return null;
}

function extractRUC($text) {
    if (preg_match('/RUC[\s:]*(\d{11})/i', $text, $matches)) {
        return $matches[1];
    }
    return null;
}

function extractTotalAmount($text) {
    // Patrones para encontrar el total
    $patterns = [
        '/(?:TOTAL|IMPORTE TOTAL|MONTO TOTAL)[\s:]*S\/?\s*([0-9,]+\.?\d{0,2})/i',
        '/(?:TOTAL|IMPORTE TOTAL)[\s:]*([0-9,]+\.?\d{0,2})/i',
        '/S\/\s*([0-9,]+\.\d{2})\s*$/m' // Total al final de línea
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            // Limpiar formato y convertir a float
            $amount = str_replace(',', '', $matches[1]);
            return floatval($amount);
        }
    }
    
    return null;
}
?>
