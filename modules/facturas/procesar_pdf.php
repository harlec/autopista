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

// Extraer texto y campos usando la función centralizada en includes/functions.php
$extracted = extractPdfData($pdf_path);

if (isset($extracted['error'])) {
    echo json_encode([
        'success' => true,
        'message' => 'PDF cargado pero no se pudieron extraer datos automáticamente',
        'data' => [
            'archivo_pdf' => $upload_result['filename'],
            'fecha_emision' => date('Y-m-d')
        ],
        'error' => $extracted['error']
    ]);
    exit;
}

// Fallback adicional: intentar extraer del nombre de archivo original si faltan campos
$original_name = isset(
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    $file['name']) ? $file['name'] : '';

if (empty($extracted['numero_factura'])) {
    if (preg_match('/([A-Z0-9]{1,4}[-_]\d{6,12})/i', $original_name, $m)) {
        $extracted['numero_factura'] = preg_replace('/[\s_]+/', '', $m[1]);
    } elseif (preg_match('/(S\d{3}[-_]\d{6,12})/i', $original_name, $m2)) {
        $extracted['numero_factura'] = $m2[1];
    }
}

if (empty($extracted['fecha_emision'])) {
    if (preg_match('/(20\d{6})/', $original_name, $m3)) {
        $maybe = extractDateFromText($m3[1]);
        if ($maybe) $extracted['fecha_emision'] = $maybe;
    } elseif (preg_match('/(\d{1,2}[\-\/]\d{1,2}[\-\/]20\d{2})/', $original_name, $m4)) {
        $maybe = extractDateFromText($m4[1]);
        if ($maybe) $extracted['fecha_emision'] = $maybe;
    }
}

if (empty($extracted['fecha_vencimiento'])) {
    if (preg_match('/_?(20\d{6})/', $original_name, $m5)) {
        $maybe = extractDateFromText($m5[1]);
        if ($maybe) $extracted['fecha_vencimiento'] = $maybe;
    }
}

// Si aún falta fecha de vencimiento, calcular a +30 días desde fecha_emision
$computed_venc = null;
if (!empty($extracted['fecha_emision'])) {
    $computed_venc = date('Y-m-d', strtotime($extracted['fecha_emision'] . ' +30 days'));
}

$extracted_data = [
    'numero_factura' => $extracted['numero_factura'] ?? null,
    'fecha_emision' => $extracted['fecha_emision'] ?? null,
    'fecha_vencimiento' => $extracted['fecha_vencimiento'] ?? $computed_venc,
    'ruc' => $extracted['ruc'] ?? null,
    'monto_total' => $extracted['total'] ?? null,
    'archivo_pdf' => $upload_result['filename']
];

echo json_encode([
    'success' => true,
    'message' => 'Datos extraídos correctamente',
    'data' => $extracted_data,
    'raw_text' => substr($extracted['raw_text'] ?? '', 0, 500),
    'method' => $extracted['method'] ?? null
]);
?>
