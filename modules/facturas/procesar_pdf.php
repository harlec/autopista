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

$extracted_data = [
    'numero_factura' => $extracted['numero_factura'] ?? null,
    'fecha_emision' => $extracted['fecha_emision'] ?? null,
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
