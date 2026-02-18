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
    // Extrae texto del PDF (smalot | pdftotext | pdftoppm+tesseract) — ver README para requisitos
    // Se implementará con Composer: composer require smalot/pdfparser
    
    // No requerimos el autoloader aquí directamente — lo intentaremos si está disponible más abajo

    // Implementación con fallback: smalot -> pdftotext -> pdftoppm+tesseract
    $text = '';
    $method = null;

    $commandExists = function ($cmd) {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $res = @shell_exec('where ' . escapeshellcmd($cmd) . ' 2>NUL');
            return !empty(trim($res));
        }
        $res = @shell_exec('command -v ' . escapeshellcmd($cmd) . ' 2>/dev/null');
        return !empty(trim($res));
    };

    // 1) smalot/pdfparser (si Composer está presente)
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $parser = new \Smalot\PdfParser\Parser();
            $pdfObj = $parser->parseFile($pdf_path);
            $text = $pdfObj->getText();
            $method = 'smalot';
        } catch (Exception $e) {
            $text = '';
        }
    }

    // 2) pdftotext (Poppler)
    if (empty(trim($text)) || strlen(trim($text)) < 40) {
        if (function_exists('shell_exec') && $commandExists('pdftotext')) {
            $cmd = 'pdftotext -layout -q ' . escapeshellarg($pdf_path) . ' -';
            $out = @shell_exec($cmd);
            if (!empty(trim($out))) {
                $text = $out;
                $method = 'pdftotext';
            }
        }
    }

    // 3) pdftoppm + tesseract (OCR)
    if (empty(trim($text)) || strlen(trim($text)) < 40) {
        if ((function_exists('exec') || function_exists('shell_exec')) && $commandExists('pdftoppm') && $commandExists('tesseract')) {
            $tmpDir = sys_get_temp_dir();
            $prefix = $tmpDir . DIRECTORY_SEPARATOR . 'autopista_pdf_' . uniqid();
            @exec('pdftoppm -r 300 -jpeg ' . escapeshellarg($pdf_path) . ' ' . escapeshellarg($prefix) . ' 2>/dev/null');
            $images = glob($prefix . '-*.jpg');
            $ocrText = '';
            foreach ($images as $img) {
                $tcmd = 'tesseract ' . escapeshellarg($img) . ' stdout 2>/dev/null';
                $pageText = @shell_exec($tcmd);
                if ($pageText) $ocrText .= "\n" . $pageText;
                @unlink($img);
            }
            if (!empty(trim($ocrText))) {
                $text = $ocrText;
                $method = 'tesseract';
            }
        }
    }

    if (empty(trim($text))) {
        return ['error' => 'No se pudo extraer texto del PDF. Instale smalot/pdfparser (Composer) o Poppler/Tesseract para OCR.'];
    }

    // Mapear campos usando los helpers existentes
    $data = [
        'numero_factura'   => extractInvoiceNumber($text),
        'fecha_emision'    => extractDateFromText($text),
        'fecha_vencimiento'=> extractDueDateFromText($text),
        'ruc'              => extractRUCFromText($text),
        'total'            => extractAmountFromText($text),
        'raw_text'         => $text,
        'method'           => $method
    ];

    return $data;
}

function extractInvoiceNumber($text) {
    // Intentar varios patrones comunes para número/serie de factura (ordenados por prioridad)
    $patterns = [
        '/([FfBbEe]\s?\d{3}[-\s]?\d{4,8})/',                  // F001-00001234 / F001 00001234
        '/\b(\d{3}[-]\d{4,8})\b/',                            // 001-00001234 (fallback)
        '/(?:FACTURA(?: ELECTR[OÓ]NICA)?)[^\n\r\dA-Z\-]*([A-Z0-9\-\/]{3,30}\d+)/i',
        '/\bN(?:°|º|ro|RO|º)\.?\s*[:\-\s]*([A-Z0-9\-\/]{3,30})/i',
        '/SERIE[\s:\-]*([A-Z0-9\-\/]{1,12})/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $m)) {
            $candidate = trim($m[1]);
            // Normalizar espacios y guiones
            $candidate = preg_replace('/\s+/', '', $candidate);
            return $candidate;
        }
    }

    return null;
}

function extractRUCFromText($text) {
    // Buscar RUC explícito
    if (preg_match('/R\.?U\.?C\.?[\s:\-]*([0-9\.\s\-]{8,20})/i', $text, $m)) {
        $digits = preg_replace('/\D/', '', $m[1]);
        if (strlen($digits) === 11) return $digits;
    }

    // Buscar cualquier secuencia de 11 dígitos
    if (preg_match('/\b(\d{11})\b/', $text, $m2)) {
        return $m2[1];
    }

    return null;
}

function extractDateFromText($text) {
    // Formatos numéricos (YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, YYYYMMDD)
    if (preg_match('/(\d{4})[\-\/](\d{1,2})[\-\/](\d{1,2})/', $text, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    if (preg_match('/(\d{4})(0[1-9]|1[0-2])([0-3][0-9])/', $text, $m3)) {
        // Formato continuo YYYYMMDD
        return sprintf('%04d-%02d-%02d', $m3[1], $m3[2], $m3[3]);
    }

    if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }

    // Fechas con nombre de mes en español: e.g. "13 de abril de 2025"
    $months = [
        'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,
        'setiembre'=>9,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
    ];

    if (preg_match('/(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|setiembre|septiembre|octubre|noviembre|diciembre)\s+de\s+(\d{4})/i', $text, $m2)) {
        $day = intval($m2[1]);
        $monthName = strtolower($m2[2]);
        $year = intval($m2[3]);
        $month = $months[$monthName] ?? null;
        if ($month) return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    return null;
}

/**
 * Extrae la fecha de vencimiento buscando palabras clave (VENCE, VENCIMIENTO, FECHA DE VENCIMIENTO)
 * Retorna fecha en formato YYYY-MM-DD o null.
 */
function extractDueDateFromText($text) {
    $patterns = [
        '/VENCIM(?:IE?NTO)?[\s\:]*[:\-\s]{0,5}(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/i',
        '/VENCE[\s\:]*[:\-\s]{0,5}(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/i',
        '/FECHA DE VENCIM(?:IE?NTO)?[\s\:]*[:\-\s]{0,5}(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/i',
        '/VENCIMIENTO[\s\:]*[:\-\s]{0,5}(\d{4}[\-\/]\d{2}[\-\/]\d{2})/i'
    ];

    foreach ($patterns as $p) {
        if (preg_match($p, $text, $m)) {
            $d = extractDateFromText($m[1]);
            if ($d) return $d;
        }
    }

    // Si no se encuentra con palabras clave, intentar detectar dos fechas y asumir la más grande (vencimiento suele ser posterior)
    if (preg_match_all('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/', $text, $matches)) {
        $dates = array_map('extractDateFromText', $matches[1]);
        $dates = array_filter($dates);
        if (count($dates) >= 2) {
            sort($dates);
            return end($dates); // la más reciente
        }
    }

    // Buscar YYYYMMDD cercano a la palabra "venc" en el texto
    if (preg_match('/venc[ai][c]?[^\d]{0,40}(20\d{6})/i', $text, $m2)) {
        return extractDateFromText($m2[1]);
    }

    return null;
}

function normalizeAmountString($s) {
    // Limpia símbolos y decide separador decimal
    $s = trim($s);
    $s = preg_replace('/[^0-9,\.\-]/', '', $s);

    $lastComma = strrpos($s, ',');
    $lastDot = strrpos($s, '.');

    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            // coma como decimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // punto como decimal
            $s = str_replace(',', '', $s);
        }
    } elseif ($lastComma !== false) {
        // solo coma
        if (preg_match('/,[0-9]{2}$/', $s)) {
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
    } else {
        // solo punto o ninguno -> eliminar comas residuales
        $s = str_replace(',', '', $s);
    }

    return floatval($s);
}

function extractAmountFromText($text) {
    // 1) Buscar líneas explícitas con TOTAL
    if (preg_match('/(?:TOTAL(?: A PAGAR| NETO| GENERAL)?|IMPORTE TOTAL|MONTO TOTAL)[\s:\-]*S?\/?\s*([0-9\.,]+\d{1,2})/im', $text, $m)) {
        return normalizeAmountString($m[1]);
    }

    // 2) Buscar cualquier monto (fallback) y devolver el mayor (asumiendo que el total suele ser el más alto)
    if (preg_match_all('/([0-9]{1,3}(?:[\.,][0-9]{3})*(?:[\.,]\d{2}))/m', $text, $matches)) {
        $values = array_map('normalizeAmountString', $matches[1]);
        if (!empty($values)) return max($values);
    }

    return null;
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
