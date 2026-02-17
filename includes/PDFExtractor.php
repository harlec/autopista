<?php
/**
 * Clase para extracción personalizada de datos de PDFs según plantillas de proveedor
 * Versión optimizada usando poppler-utils (pdftotext)
 */
class PDFExtractor {
    private $db;
    private $pdf_text;
    private $proveedor_id;
    
    public function __construct($db_connection) {
        $this->db = $db_connection;
    }
    
    /**
     * Extraer datos del PDF usando plantilla del proveedor
     */
    public function extractData($pdf_path, $proveedor_id = null) {
        // Verificar que el archivo existe
        if (!file_exists($pdf_path)) {
            return [
                'success' => false,
                'error' => 'Archivo PDF no encontrado'
            ];
        }
        
        try {
            // Extraer texto (orientado a Poppler)
            $this->pdf_text = $this->extractTextFromPDF($pdf_path);

            if (empty($this->pdf_text)) {
                return [
                    'success' => false,
                    'error' => 'No se pudo extraer texto del PDF'
                ];
            }

            // Detectar moneda del documento
            $moneda = $this->detectCurrency();

            // Si se especifica un proveedor, usar su plantilla
            if ($proveedor_id) {
                $this->proveedor_id = $proveedor_id;
                $result = $this->extractWithTemplate();
                $result['moneda'] = $moneda;
                $this->logExtraction($pdf_path, $result);
                return $result;
            }

            // Si no, intentar detectar proveedor y usar plantilla
            $detected_proveedor = $this->detectProveedor();
            if ($detected_proveedor) {
                $this->proveedor_id = $detected_proveedor;
                $result = $this->extractWithTemplate();
                $result['moneda'] = $moneda;
                $this->logExtraction($pdf_path, $result);
                return $result;
            }

            // Fallback a extracción genérica
            $result = $this->genericExtraction();
            $result['moneda'] = $moneda;
            $this->logExtraction($pdf_path, $result);
            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Detectar la moneda del documento
     */
    private function detectCurrency() {
        $currencies = [
            'MXN' => ['$', 'MXN', 'PESO MEXICANO', 'PESOS'],
            'USD' => ['USD', 'US\$', 'DOLAR', 'DOLLAR'],
            'EUR' => ['EUR', '€', 'EURO'],
            'PEN' => ['S/', 'PEN', 'SOLES', 'NUEVOS SOLES']
        ];
        
        // Contar menciones de cada moneda
        $scores = [];
        foreach ($currencies as $code => $patterns) {
            $scores[$code] = 0;
            foreach ($patterns as $pattern) {
                $count = substr_count(strtoupper($this->pdf_text), strtoupper($pattern));
                $scores[$code] += $count;
            }
        }
        
        // Reglas especiales de detección
        
        // Si encuentra "Peso Mexicano" o RFC mexicano, es MXN
        if (stripos($this->pdf_text, 'Peso Mexicano') !== false || 
            preg_match('/RFC:[\s]*[A-Z]{3,4}\d{6}[A-Z0-9]{3}/', $this->pdf_text)) {
            return 'MXN';
        }
        
        // Si encuentra "SUNAT" o RUC peruano de 11 dígitos, probablemente PEN
        if (stripos($this->pdf_text, 'SUNAT') !== false || 
            preg_match('/RUC[\s:]*\d{11}/', $this->pdf_text)) {
            // Verificar si es claramente USD
            if ($scores['USD'] > $scores['PEN'] * 2) {
                return 'USD';
            }
            return 'PEN';
        }
        
        // Retornar la moneda con mayor score
        arsort($scores);
        $detected = array_key_first($scores);
        
        // Si no hay evidencia clara, default a PEN
        return $scores[$detected] > 0 ? $detected : 'PEN';
    }
    
    /**
     * Extraer texto del PDF usando poppler-utils o librería PHP
     */
    private function extractTextFromPDF($pdf_path) {
        // Forzar uso de Poppler (pdftotext)
        $text = $this->extractWithPoppler($pdf_path);
        if (!empty($text)) {
            return $text;
        }
        // Si falla, intentar con exec directo
        $text = $this->extractWithExec($pdf_path);
        if (!empty($text)) {
            return $text;
        }
        // Si todo falla, retornar null
        return null;
    }
    
    /**
     * Verificar si poppler-utils está disponible
     */
    private function hasPoppler() {
        $output = [];
        $return_var = 0;
        @exec('which pdftotext 2>&1', $output, $return_var);
        return $return_var === 0;
    }
    
    /**
     * Extraer texto usando poppler-utils (pdftotext)
     */
    private function extractWithPoppler($pdf_path) {
        // Crear archivo temporal para la salida
        $temp_file = sys_get_temp_dir() . '/pdf_' . uniqid() . '.txt';
        
        // Comando pdftotext: -layout mantiene el formato, -enc UTF-8 para caracteres especiales
        $command = sprintf(
            'pdftotext -layout -enc UTF-8 %s %s 2>&1',
            escapeshellarg($pdf_path),
            escapeshellarg($temp_file)
        );
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($temp_file)) {
            $text = file_get_contents($temp_file);
            unlink($temp_file);
            return $text;
        }
        
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        return null;
    }
    
    /**
     * Extraer texto usando exec directo
     */
    private function extractWithExec($pdf_path) {
        $command = sprintf(
            'pdftotext -layout -enc UTF-8 %s - 2>&1',
            escapeshellarg($pdf_path)
        );
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            return implode("\n", $output);
        }
        
        return null;
    }

    /**
     * Guardar log de extracción para depuración
     */
    private function logExtraction($pdf_path, $data) {
        $log_dir = __DIR__ . '/../assets/uploads/facturas';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/log_extraccion.txt';
        $log = "==== " . date('Y-m-d H:i:s') . " ====\n";
        $log .= "PDF: $pdf_path\n";
        $log .= "--- TEXTO EXTRAÍDO ---\n";
        $log .= $this->pdf_text . "\n";
        $log .= "--- DATOS EXTRAÍDOS ---\n";
        $log .= print_r($data, true) . "\n";
        $log .= str_repeat("=", 40) . "\n";
        @file_put_contents($log_file, $log, FILE_APPEND);
    }
    
    /**
     * Extraer usando plantilla específica del proveedor
     */
    private function extractWithTemplate() {
        // Obtener plantillas del proveedor ordenadas por prioridad
        $query = "SELECT * FROM proveedor_plantillas 
                  WHERE proveedor_id = :proveedor_id AND activo = 1 
                  ORDER BY prioridad DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':proveedor_id', $this->proveedor_id);
        $stmt->execute();
        $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $extracted_data = [
            'success' => true,
            'proveedor_id' => $this->proveedor_id,
            'metodo' => 'plantilla_personalizada'
        ];
        
        // Probar cada plantilla hasta que funcione
        foreach ($plantillas as $plantilla) {
            $result = $this->applyTemplate($plantilla);
            
            // Si encontró al menos el número de factura o el total, considerar exitoso
            if (!empty($result['numero_factura']) || !empty($result['monto_total'])) {
                $extracted_data = array_merge($extracted_data, $result);
                $extracted_data['plantilla_usada'] = $plantilla['nombre_plantilla'];
                $extracted_data['plantilla_id'] = $plantilla['id'];
                break;
            }
        }
        
        // Completar campos faltantes con extracción genérica
        if (empty($extracted_data['numero_factura']) && empty($extracted_data['monto_total'])) {
            $generic = $this->genericExtraction();
            $extracted_data = array_merge($generic, $extracted_data);
        }
        
        return $extracted_data;
    }
    
    /**
     * Aplicar una plantilla específica
     */
    private function applyTemplate($plantilla) {
        $data = [];
        
        // Extraer número de factura
        if (!empty($plantilla['patron_numero_factura'])) {
            $data['numero_factura'] = $this->extractWithPattern(
                $plantilla['patron_numero_factura']
            );
        }
        
        // Extraer fecha de emisión
        if (!empty($plantilla['patron_fecha_emision'])) {
            $data['fecha_emision'] = $this->extractDateWithPattern(
                $plantilla['patron_fecha_emision']
            );
        }
        
        // Extraer fecha de vencimiento
        if (!empty($plantilla['patron_fecha_vencimiento'])) {
            $data['fecha_vencimiento'] = $this->extractDateWithPattern(
                $plantilla['patron_fecha_vencimiento']
            );
        }
        
        // Extraer monto total
        if (!empty($plantilla['patron_total'])) {
            $data['monto_total'] = $this->extractAmountWithPattern(
                $plantilla['patron_total']
            );
        }
        
        // Extraer subtotal
        if (!empty($plantilla['patron_subtotal'])) {
            $data['subtotal'] = $this->extractAmountWithPattern(
                $plantilla['patron_subtotal']
            );
        }
        
        // Extraer IGV
        if (!empty($plantilla['patron_igv'])) {
            $data['igv'] = $this->extractAmountWithPattern(
                $plantilla['patron_igv']
            );
        }
        
        // Extraer RUC
        if (!empty($plantilla['patron_ruc'])) {
            $data['ruc'] = $this->extractWithPattern(
                $plantilla['patron_ruc']
            );
        }
        
        // Calcular campos faltantes
        if (!empty($data['monto_total']) && empty($data['igv']) && empty($data['subtotal'])) {
            // Calcular IGV como 18% del total dividido por 1.18
            $data['subtotal'] = round($data['monto_total'] / 1.18, 2);
            $data['igv'] = round($data['monto_total'] - $data['subtotal'], 2);
        } elseif (!empty($data['subtotal']) && !empty($data['igv']) && empty($data['monto_total'])) {
            $data['monto_total'] = round($data['subtotal'] + $data['igv'], 2);
        }
        
        return $data;
    }
    
    /**
     * Extraer texto usando un patrón regex
     */
    private function extractWithPattern($pattern) {
        if (preg_match($pattern, $this->pdf_text, $matches)) {
            // Para facturas mexicanas (Serie + Folio)
            if (isset($matches[2]) && !empty($matches[2])) {
                // Formato: Serie: MXSP  Folio: 7056 → MXSP-7056
                return trim($matches[1]) . '-' . trim($matches[2]);
            }
            // Si hay un tercer grupo y el segundo está vacío
            if (isset($matches[3]) && !empty($matches[3]) && empty($matches[2])) {
                return trim($matches[3]);
            }
            // Formato normal
            return trim($matches[1]);
        }
        return null;
    }
    
    /**
     * Extraer fecha usando un patrón regex
     */
    private function extractDateWithPattern($pattern) {
        if (preg_match($pattern, $this->pdf_text, $matches)) {
            // Si el patrón captura día, mes y año por separado
            if (isset($matches[3])) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = $matches[2];
                $year = $matches[3];
                
                // Si el mes está en formato texto (Ene, Feb, etc.), convertir a número
                if (!is_numeric($month)) {
                    $month = $this->parseMonthName($month);
                }
                
                $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                return "$year-$month-$day";
            }
            // Si el patrón captura toda la fecha
            return $this->parseDate(trim($matches[1]));
        }
        return null;
    }
    
    /**
     * Convertir nombre de mes a número (soporta español)
     */
    private function parseMonthName($month_name) {
        $months = [
            'ene' => '01', 'enero' => '01', 'jan' => '01', 'january' => '01',
            'feb' => '02', 'febrero' => '02', 'february' => '02',
            'mar' => '03', 'marzo' => '03', 'march' => '03',
            'abr' => '04', 'abril' => '04', 'apr' => '04', 'april' => '04',
            'may' => '05', 'mayo' => '05',
            'jun' => '06', 'junio' => '06', 'june' => '06',
            'jul' => '07', 'julio' => '07', 'july' => '07',
            'ago' => '08', 'agosto' => '08', 'aug' => '08', 'august' => '08',
            'sep' => '09', 'septiembre' => '09', 'september' => '09', 'set' => '09', 'setiembre' => '09',
            'oct' => '10', 'octubre' => '10', 'october' => '10',
            'nov' => '11', 'noviembre' => '11', 'november' => '11',
            'dic' => '12', 'diciembre' => '12', 'dec' => '12', 'december' => '12'
        ];
        
        $month_lower = strtolower(trim($month_name));
        return isset($months[$month_lower]) ? $months[$month_lower] : $month_name;
    }
    
    /**
     * Extraer monto usando un patrón regex
     */
    private function extractAmountWithPattern($pattern) {
        if (preg_match($pattern, $this->pdf_text, $matches)) {
            $amount = str_replace(',', '', $matches[1]);
            return floatval($amount);
        }
        return null;
    }
    
    /**
     * Intentar detectar el proveedor basado en RUC o nombre en el PDF
     */
    private function detectProveedor() {
        // Buscar RUC en el texto
        if (preg_match('/RUC[\s:]*(\d{11})/i', $this->pdf_text, $matches)) {
            $ruc = $matches[1];
            
            $query = "SELECT id FROM proveedores WHERE ruc = :ruc LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':ruc', $ruc);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['id'];
            }
        }
        
        // Buscar nombre de proveedor
        $query = "SELECT id, nombre FROM proveedores WHERE activo = 1";
        $stmt = $this->db->query($query);
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($proveedores as $proveedor) {
            // Normalizar nombre para búsqueda flexible
            $nombre_limpio = preg_replace('/\s+(S\.?A\.?A?\.?|PERU|DEL|LA|EL)\s*/i', ' ', $proveedor['nombre']);
            
            if (stripos($this->pdf_text, $nombre_limpio) !== false) {
                return $proveedor['id'];
            }
        }
        
        return null;
    }
    
    /**
     * Extracción genérica cuando no hay plantilla
     */
    private function genericExtraction() {
        $data = [
            'success' => true,
            'metodo' => 'extraccion_generica'
        ];
        
        // Patrones genéricos mejorados
        $patterns = [
            'numero_factura' => [
                '/(?:FACTURA|RECIBO|BOLETA|COMPROBANTE)[\s:]+(?:N[°ºª]?\.?|NRO\.?|NÚMERO)?[\s:]*([A-Z0-9\-]+)/i',
                '/(?:N[°ºª]|NRO\.?|NÚMERO)[\s:]+(?:DE[\s]+)?(?:FACTURA|RECIBO|BOLETA)[\s:]*([A-Z0-9\-]+)/i',
                '/([FBE]\d{3}-\d+)/i',
                '/(?:DOCUMENTO|DOC\.)[\s:]*([A-Z0-9\-]+)/i'
            ],
            'fecha_emision' => [
                '/(?:FECHA[\s]+DE[\s]+EMISI[OÓ]N|EMISI[OÓ]N|FECHA[\s]+EMISI[OÓ]N)[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i',
                '/(?:FECHA)[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i',
                '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/'
            ],
            'fecha_vencimiento' => [
                '/(?:FECHA[\s]+DE[\s]+VENCIMIENTO|VENCIMIENTO|FECHA[\s]+VENC\.?)[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i',
                '/(?:VENCE|PAGAR[\s]+HASTA)[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i'
            ],
            'total' => [
                '/(?:TOTAL[\s]+A[\s]+PAGAR|IMPORTE[\s]+TOTAL|MONTO[\s]+TOTAL|TOTAL)[\s:]*S\/[\s]*([0-9,]+\.?\d{0,2})/i',
                '/(?:TOTAL|PAGAR)[\s:]*([0-9,]+\.\d{2})/i',
                '/(?:TOTAL)[\s:]*([0-9,]+\.?\d{0,2})/i'
            ],
            'subtotal' => [
                '/(?:SUB[\s]*TOTAL|SUBTOTAL|BASE[\s]+IMPONIBLE)[\s:]*S\/[\s]*([0-9,]+\.?\d{0,2})/i'
            ],
            'igv' => [
                '/(?:I\.?G\.?V\.?|IGV|IMPUESTO)[\s:]*S\/[\s]*([0-9,]+\.?\d{0,2})/i'
            ],
            'ruc' => [
                '/RUC[\s:]*(\d{11})/i',
                '/R\.U\.C\.[\s:]*(\d{11})/i'
            ]
        ];
        
        // Intentar extraer cada campo con múltiples patrones
        foreach ($patterns['numero_factura'] as $pattern) {
            if (empty($data['numero_factura'])) {
                $data['numero_factura'] = $this->extractWithPattern($pattern);
            }
        }
        
        foreach ($patterns['fecha_emision'] as $pattern) {
            if (empty($data['fecha_emision'])) {
                $data['fecha_emision'] = $this->extractDateWithPattern($pattern);
            }
        }
        
        foreach ($patterns['fecha_vencimiento'] as $pattern) {
            if (empty($data['fecha_vencimiento'])) {
                $data['fecha_vencimiento'] = $this->extractDateWithPattern($pattern);
            }
        }
        
        foreach ($patterns['total'] as $pattern) {
            if (empty($data['monto_total'])) {
                $data['monto_total'] = $this->extractAmountWithPattern($pattern);
            }
        }
        
        foreach ($patterns['subtotal'] as $pattern) {
            if (empty($data['subtotal'])) {
                $data['subtotal'] = $this->extractAmountWithPattern($pattern);
            }
        }
        
        foreach ($patterns['igv'] as $pattern) {
            if (empty($data['igv'])) {
                $data['igv'] = $this->extractAmountWithPattern($pattern);
            }
        }
        
        foreach ($patterns['ruc'] as $pattern) {
            if (empty($data['ruc'])) {
                $data['ruc'] = $this->extractWithPattern($pattern);
            }
        }
        
        // Si encontramos RUC, buscar el proveedor
        if (!empty($data['ruc'])) {
            $query = "SELECT id FROM proveedores WHERE ruc = :ruc LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':ruc', $data['ruc']);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data['proveedor_id'] = $row['id'];
            }
        }
        
        // Calcular campos faltantes
        if (!empty($data['monto_total']) && empty($data['igv']) && empty($data['subtotal'])) {
            $data['subtotal'] = round($data['monto_total'] / 1.18, 2);
            $data['igv'] = round($data['monto_total'] - $data['subtotal'], 2);
        }
        
        return $data;
    }
    
    /**
     * Convertir fecha de varios formatos a Y-m-d
     */
    private function parseDate($date_string) {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }
    
    /**
     * Obtener texto completo del PDF para debugging
     */
    public function getPdfText() {
        return $this->pdf_text;
    }
    
    /**
     * Obtener información del sistema de extracción
     */
    public function getSystemInfo() {
        return [
            'poppler_available' => $this->hasPoppler(),
            'smalot_available' => class_exists('\Smalot\PdfParser\Parser'),
            'preferred_method' => $this->hasPoppler() ? 'poppler-utils (pdftotext)' : 'smalot/pdfparser'
        ];
    }
}
?>
