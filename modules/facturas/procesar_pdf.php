<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

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

// Saneos y mejoras sobre lo extraído
$original_name = isset($file['name']) ? $file['name'] : '';

// Si el número extraído parece improbable, preferir filename si contiene patrón claro
if (!empty($extracted['numero_factura'])) {
    if (!function_exists('isLikelyInvoiceNumber') || !isLikelyInvoiceNumber($extracted['numero_factura'])) {
        if (preg_match('/(S\d{3}[-_]\d{6,12})/i', $original_name, $mf)) {
            $extracted['numero_factura'] = $mf[1];
        }
    }
} else {
    if (preg_match('/(S\d{3}[-_]\d{6,12})/i', $original_name, $m2)) {
        $extracted['numero_factura'] = $m2[1];
    } elseif (preg_match('/([A-Z0-9]{1,4}[-_]\d{6,12})/i', $original_name, $m3)) {
        $extracted['numero_factura'] = preg_replace('/[\s_]+/', '', $m3[1]);
    }
}

// Fecha de emisión: preferir valor válido; si no existe, buscar cerca de palabras clave en el texto; si sigue sin existir, intentar filename
if (empty($extracted['fecha_emision']) || !function_exists('isValidYmd') || !isValidYmd($extracted['fecha_emision'])) {
    $candidate = null;
    if (!empty($extracted['raw_text'])) {
        $candidate = extractDateNearKeyword($extracted['raw_text'], ['FECHA','EMIS','EMISION','EMITIDO']);
    }
    if (!$candidate && preg_match('/(20\d{6})/', $original_name, $mf2)) {
        $candidate = extractDateFromText($mf2[1]);
    }
    if ($candidate) $extracted['fecha_emision'] = $candidate;
}

// Fecha de vencimiento: validar y, si inválida, intentar palabra clave o filename; si sigue sin existir, calcular +30 días
if (empty($extracted['fecha_vencimiento']) || !function_exists('isValidYmd') || !isValidYmd($extracted['fecha_vencimiento'])) {
    $candidate = null;
    if (!empty($extracted['raw_text'])) {
        $candidate = extractDueDateFromText($extracted['raw_text']);
    }
    if (!$candidate && preg_match('/_?(20\d{6})/', $original_name, $mf3)) {
        $candidate = extractDateFromText($mf3[1]);
    }
    $extracted['fecha_vencimiento'] = $candidate ?: null;
}

// Si aún falta fecha de vencimiento, calcular a +30 días desde fecha_emision (si es válida)
$computed_venc = null;
if (!empty($extracted['fecha_emision']) && function_exists('isValidYmd') && isValidYmd($extracted['fecha_emision'])) {
    $computed_venc = date('Y-m-d', strtotime($extracted['fecha_emision'] . ' +30 days'));
}

// Validar final de campos antes de devolver
$final_fecha_emision = (!empty($extracted['fecha_emision']) && isValidYmd($extracted['fecha_emision'])) ? $extracted['fecha_emision'] : null;
$final_fecha_venc = (!empty($extracted['fecha_vencimiento']) && isValidYmd($extracted['fecha_vencimiento'])) ? $extracted['fecha_vencimiento'] : ($computed_venc ?: null);

$extracted_data = [
    'numero_factura' => $extracted['numero_factura'] ?? null,
    'fecha_emision' => $final_fecha_emision,
    'fecha_vencimiento' => $final_fecha_venc,
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
