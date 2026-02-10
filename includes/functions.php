<?php
// Funciones comunes del sistema

function formatCurrency($amount, $currency = 'PEN') {
    $symbol = $currency === 'PEN' ? 'S/ ' : '$ ';
    return $symbol . number_format($amount, 2, '.', ',');
}

function formatDate($date) {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

function getEstadoBadge($estado) {
    $badges = [
        'pendiente' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>',
        'pagada' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagada</span>',
        'vencida' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>',
        'anulada' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Anulada</span>'
    ];
    return $badges[$estado] ?? $estado;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isDatePast($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

function getDaysDifference($date1, $date2 = null) {
    if ($date2 === null) {
        $date2 = date('Y-m-d');
    }
    $diff = strtotime($date1) - strtotime($date2);
    return floor($diff / (60 * 60 * 24));
}

function uploadFile($file, $directory = 'uploads/facturas/') {
    $upload_dir = __DIR__ . '/../assets/' . $directory;
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Solo se permiten archivos PDF'];
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $directory . $new_filename];
    }
    
    return ['success' => false, 'message' => 'Error al subir el archivo'];
}

function extractPdfData($pdf_path) {
    // Esta función requiere la librería smalot/pdfparser
    // Se implementará con Composer: composer require smalot/pdfparser
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdf_path);
        $text = $pdf->getText();
        
        // Patrones para extraer información común
        $data = [
            'numero_factura' => extractPattern($text, '/(?:FACTURA|N[°ºª]|NRO\.?|NÚMERO)[\s:]*([A-Z0-9\-]+)/i'),
            'fecha_emision' => extractDateFromText($text),
            'ruc' => extractPattern($text, '/RUC[\s:]*(\d{11})/i'),
            'total' => extractAmountFromText($text),
            'raw_text' => $text
        ];
        
        return $data;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function extractPattern($text, $pattern) {
    if (preg_match($pattern, $text, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

function extractDateFromText($text) {
    // Busca fechas en formato DD/MM/YYYY o DD-MM-YYYY
    if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $matches)) {
        return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
    }
    return null;
}

function extractAmountFromText($text) {
    // Busca montos con formato S/ 1,234.56 o 1234.56
    if (preg_match('/(?:TOTAL|S\/|USD|\$)[\s:]*([0-9,]+\.?\d{0,2})/i', $text, $matches)) {
        return floatval(str_replace(',', '', $matches[1]));
    }
    return null;
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
