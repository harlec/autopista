<?php
/**
 * Diagnóstico Ultra Detallado
 * Muestra EXACTAMENTE dónde falla
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Detallado</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .error { color: #ff0000; background: #ffeeee; padding: 10px; margin: 10px 0; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        pre { background: #000; padding: 15px; border: 1px solid #00ff00; overflow-x: auto; }
        .section { border: 2px solid #00ff00; padding: 15px; margin: 20px 0; }
        h2 { color: #00ffff; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNÓSTICO ULTRA DETALLADO</h1>
    
    <?php
    
    echo "<div class='section'>";
    echo "<h2>1. INFORMACIÓN DEL SERVIDOR</h2>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "Directorio actual: " . __DIR__ . "<br>";
    echo "Usuario PHP: " . get_current_user() . "<br>";
    echo "</div>";
    
    // TEST 1: Verificar archivo
    echo "<div class='section'>";
    echo "<h2>2. VERIFICAR ARCHIVO PDFExtractor.php</h2>";
    
    $pdf_extractor_path = __DIR__ . '/includes/PDFExtractor.php';
    echo "Ruta: $pdf_extractor_path<br>";
    
    if (file_exists($pdf_extractor_path)) {
        echo "<span class='success'>✅ Archivo existe</span><br>";
        
        $size = filesize($pdf_extractor_path);
        echo "Tamaño: " . number_format($size) . " bytes<br>";
        
        if ($size < 10000) {
            echo "<span class='error'>⚠️ ADVERTENCIA: Archivo muy pequeño ($size bytes). Debería ser ~30KB</span><br>";
        }
        
        $perms = substr(sprintf('%o', fileperms($pdf_extractor_path)), -4);
        echo "Permisos: $perms<br>";
        
        // Leer primeras líneas
        $lines = file($pdf_extractor_path);
        echo "<br><strong>Primeras 10 líneas del archivo:</strong><br>";
        echo "<pre>";
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]);
        }
        echo "</pre>";
        
        // Buscar métodos críticos en el código
        $content = file_get_contents($pdf_extractor_path);
        
        echo "<br><strong>Verificando métodos en el código:</strong><br>";
        
        $required_methods = [
            'function extractData' => 'extractData',
            'function extractWithTemplate' => 'extractWithTemplate',
            'function detectProveedor' => 'detectProveedor',
            'function detectCurrency' => 'detectCurrency',
            'function extractTextFromPDF' => 'extractTextFromPDF',
            'function genericExtraction' => 'genericExtraction'
        ];
        
        foreach ($required_methods as $search => $name) {
            if (strpos($content, $search) !== false) {
                echo "<span class='success'>✅ $name - encontrado en código</span><br>";
            } else {
                echo "<span class='error'>❌ $name - NO encontrado en código</span><br>";
            }
        }
        
    } else {
        echo "<span class='error'>❌ Archivo NO existe</span><br>";
        echo "Verifica que subiste el archivo a la ruta correcta<br>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
    
    // TEST 2: Intentar cargar
    echo "<div class='section'>";
    echo "<h2>3. CARGAR CLASE PDFExtractor</h2>";
    
    try {
        require_once $pdf_extractor_path;
        echo "<span class='success'>✅ Archivo cargado sin errores de sintaxis</span><br>";
        
        if (class_exists('PDFExtractor')) {
            echo "<span class='success'>✅ Clase PDFExtractor existe</span><br>";
            
            // Verificar métodos de la clase
            $methods = get_class_methods('PDFExtractor');
            echo "<br><strong>Métodos disponibles en la clase (" . count($methods) . "):</strong><br>";
            
            sort($methods);
            foreach ($methods as $method) {
                echo "• $method<br>";
            }
            
            // Verificar métodos críticos
            echo "<br><strong>Verificación de métodos críticos:</strong><br>";
            $critical = ['extractData', 'extractWithTemplate', 'detectProveedor', 'detectCurrency'];
            foreach ($critical as $method) {
                if (in_array($method, $methods)) {
                    echo "<span class='success'>✅ $method</span><br>";
                } else {
                    echo "<span class='error'>❌ $method - FALTA</span><br>";
                }
            }
            
        } else {
            echo "<span class='error'>❌ Clase PDFExtractor NO se pudo encontrar después de require</span><br>";
            echo "<div class='error'>Posible problema: El archivo no contiene 'class PDFExtractor'</div>";
        }
        
    } catch (ParseError $e) {
        echo "<div class='error'>";
        echo "<strong>ERROR DE SINTAXIS PHP:</strong><br>";
        echo "Mensaje: " . $e->getMessage() . "<br>";
        echo "Archivo: " . $e->getFile() . "<br>";
        echo "Línea: " . $e->getLine() . "<br>";
        echo "<br><strong>Trace:</strong><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>ERROR AL CARGAR:</strong><br>";
        echo $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
    
    // TEST 3: Conectar BD
    echo "<div class='section'>";
    echo "<h2>4. CONECTAR BASE DE DATOS</h2>";
    
    $db_path = __DIR__ . '/config/database.php';
    echo "Ruta: $db_path<br>";
    
    if (file_exists($db_path)) {
        echo "<span class='success'>✅ database.php existe</span><br>";
        
        try {
            require_once $db_path;
            
            if (class_exists('Database')) {
                echo "<span class='success'>✅ Clase Database cargada</span><br>";
                
                $database = new Database();
                $db = $database->getConnection();
                
                if ($db) {
                    echo "<span class='success'>✅ Conexión exitosa</span><br>";
                } else {
                    echo "<span class='error'>❌ No se pudo conectar a la BD</span><br>";
                    echo "</div></body></html>";
                    exit;
                }
            } else {
                echo "<span class='error'>❌ Clase Database no encontrada</span><br>";
                echo "</div></body></html>";
                exit;
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error BD: " . $e->getMessage() . "</div>";
            echo "</div></body></html>";
            exit;
        }
    } else {
        echo "<span class='error'>❌ database.php NO existe</span><br>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
    
    // TEST 4: Crear instancia
    echo "<div class='section'>";
    echo "<h2>5. CREAR INSTANCIA DE PDFExtractor</h2>";
    
    try {
        $extractor = new PDFExtractor($db);
        echo "<span class='success'>✅ Instancia creada exitosamente</span><br>";
        echo "Tipo: " . get_class($extractor) . "<br>";
        
        // Verificar que los métodos son llamables
        echo "<br><strong>Verificar métodos llamables:</strong><br>";
        $test_methods = ['extractData', 'getPdfText', 'getSystemInfo'];
        
        foreach ($test_methods as $method) {
            if (method_exists($extractor, $method)) {
                if (is_callable([$extractor, $method])) {
                    echo "<span class='success'>✅ $method - existe y es llamable</span><br>";
                } else {
                    echo "<span class='warning'>⚠️ $method - existe pero NO es llamable</span><br>";
                }
            } else {
                echo "<span class='error'>❌ $method - NO existe</span><br>";
            }
        }
        
        // Probar método getSystemInfo si existe
        if (method_exists($extractor, 'getSystemInfo')) {
            echo "<br><strong>Información del sistema de extracción:</strong><br>";
            try {
                $info = $extractor->getSystemInfo();
                echo "<pre>";
                print_r($info);
                echo "</pre>";
            } catch (Exception $e) {
                echo "<div class='error'>Error al llamar getSystemInfo(): " . $e->getMessage() . "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>ERROR AL CREAR INSTANCIA:</strong><br>";
        echo $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
    
    // TEST 5: Prueba con PDF real
    echo "<div class='section'>";
    echo "<h2>6. PRUEBA CON PDF REAL</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_pdf'])) {
        $file = $_FILES['test_pdf'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $temp_pdf = sys_get_temp_dir() . '/' . uniqid('diag_') . '.pdf';
            move_uploaded_file($file['tmp_name'], $temp_pdf);
            
            echo "📄 Archivo: " . htmlspecialchars($file['name']) . "<br>";
            echo "📦 Tamaño: " . number_format($file['size']) . " bytes<br>";
            echo "📍 Temporal: $temp_pdf<br><br>";
            
            echo "<strong>Iniciando extracción...</strong><br><br>";
            
            try {
                // Llamar extractData
                echo "→ Llamando extractData()...<br>";
                
                $proveedor_id = isset($_POST['proveedor_id']) && !empty($_POST['proveedor_id']) 
                    ? intval($_POST['proveedor_id']) 
                    : null;
                
                if ($proveedor_id) {
                    echo "→ Usando proveedor ID: $proveedor_id<br>";
                } else {
                    echo "→ Auto-detección de proveedor<br>";
                }
                
                $result = $extractor->extractData($temp_pdf, $proveedor_id);
                
                echo "<br><span class='success'>✅ extractData() ejecutado sin excepciones</span><br><br>";
                
                echo "<strong>RESULTADO COMPLETO:</strong><br>";
                echo "<pre>";
                print_r($result);
                echo "</pre>";
                
                // Analizar resultado
                if (isset($result['success'])) {
                    if ($result['success']) {
                        echo "<br><span class='success'>🎉 SUCCESS = TRUE</span><br>";
                        
                        echo "<br><strong>Datos extraídos:</strong><br>";
                        $campos = ['numero_factura', 'fecha_emision', 'monto_total', 'subtotal', 'igv', 'moneda', 'proveedor_id'];
                        
                        foreach ($campos as $campo) {
                            if (isset($result[$campo])) {
                                $valor = $result[$campo];
                                if (empty($valor) && $valor !== 0) {
                                    echo "• $campo: <span class='warning'>(vacío)</span><br>";
                                } else {
                                    echo "• $campo: <span class='success'>$valor</span><br>";
                                }
                            }
                        }
                        
                    } else {
                        echo "<br><span class='error'>❌ SUCCESS = FALSE</span><br>";
                        if (isset($result['error'])) {
                            echo "Error: " . $result['error'] . "<br>";
                        }
                    }
                }
                
                // Intentar obtener texto del PDF
                echo "<br><strong>Texto extraído del PDF:</strong><br>";
                try {
                    $pdf_text = $extractor->getPdfText();
                    if (!empty($pdf_text)) {
                        echo "<span class='success'>✅ Se extrajo texto (" . strlen($pdf_text) . " caracteres)</span><br>";
                        echo "<br>Primeros 500 caracteres:<br>";
                        echo "<pre>" . htmlspecialchars(substr($pdf_text, 0, 500)) . "</pre>";
                    } else {
                        echo "<span class='error'>❌ No se extrajo texto del PDF</span><br>";
                    }
                } catch (Exception $e) {
                    echo "<span class='error'>Error al obtener texto: " . $e->getMessage() . "</span><br>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<strong>EXCEPCIÓN DURANTE EXTRACCIÓN:</strong><br>";
                echo "Tipo: " . get_class($e) . "<br>";
                echo "Mensaje: " . $e->getMessage() . "<br>";
                echo "Archivo: " . $e->getFile() . "<br>";
                echo "Línea: " . $e->getLine() . "<br>";
                echo "<br><strong>Stack Trace:</strong><br>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                echo "</div>";
            } catch (Error $e) {
                echo "<div class='error'>";
                echo "<strong>ERROR FATAL DURANTE EXTRACCIÓN:</strong><br>";
                echo "Tipo: " . get_class($e) . "<br>";
                echo "Mensaje: " . $e->getMessage() . "<br>";
                echo "Archivo: " . $e->getFile() . "<br>";
                echo "Línea: " . $e->getLine() . "<br>";
                echo "<br><strong>Stack Trace:</strong><br>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                echo "</div>";
            }
            
            unlink($temp_pdf);
            
        } else {
            echo "<span class='error'>Error subiendo archivo: " . $file['error'] . "</span><br>";
        }
        
    } else {
        // Mostrar formulario
        echo "<p>Sube un PDF para hacer la prueba completa:</p>";
        
        // Obtener proveedores
        try {
            $query = "SELECT id, nombre FROM proveedores ORDER BY nombre";
            $stmt = $db->query($query);
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<form method="POST" enctype="multipart/form-data">';
            echo '<select name="proveedor_id" style="padding:8px; margin:10px 0; width:300px;">';
            echo '<option value="">Auto-detectar proveedor</option>';
            foreach ($proveedores as $p) {
                echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['nombre']) . '</option>';
            }
            echo '</select><br>';
            
            echo '<input type="file" name="test_pdf" accept=".pdf" required style="margin:10px 0;"><br>';
            echo '<button type="submit" style="padding:10px 20px; background:#00ff00; color:#000; border:none; cursor:pointer; margin-top:10px;">🧪 PROBAR EXTRACCIÓN</button>';
            echo '</form>';
            
        } catch (Exception $e) {
            echo "<div class='error'>Error obteniendo proveedores: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "</div>";
    
    ?>
    
    <div class='section'>
        <h2>✅ RESUMEN</h2>
        <p>Si llegaste hasta aquí sin errores, el sistema está bien configurado.</p>
        <p>Sube un PDF arriba para probar la extracción completa.</p>
    </div>
    
</body>
</html>
