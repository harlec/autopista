<?php
/**
 * Diagnóstico Completo - Extracción de PDFs
 * Sube a: /diagnostico_extraccion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Extracción</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content { padding: 30px; }
        .section {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            margin: 10px 0;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .test-box {
            background: white;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico Completo de Extracción</h1>
            <p>Verificación paso a paso del proceso</p>
        </div>
        
        <div class="content">
            <?php
            
            // PASO 1: Verificar estructura
            echo '<div class="section">';
            echo '<h2>📁 PASO 1: Estructura de Archivos</h2>';
            
            $required_files = [
                'PDFExtractor' => __DIR__ . '/includes/PDFExtractor.php',
                'procesar_pdf' => __DIR__ . '/ajax/procesar_pdf.php',
                'database' => __DIR__ . '/config/database.php'
            ];
            
            $all_files_ok = true;
            foreach ($required_files as $name => $path) {
                if (file_exists($path)) {
                    echo "<span class='status success'>✅</span> $name existe<br>";
                } else {
                    echo "<span class='status error'>❌</span> $name NO existe: $path<br>";
                    $all_files_ok = false;
                }
            }
            echo '</div>';
            
            if (!$all_files_ok) {
                echo '<div class="error-box"><strong>ERROR CRÍTICO:</strong> Faltan archivos esenciales. Sube los archivos faltantes antes de continuar.</div>';
                echo '</div></div></body></html>';
                exit;
            }
            
            // PASO 2: Cargar Database
            echo '<div class="section">';
            echo '<h2>🗄️ PASO 2: Conexión Base de Datos</h2>';
            
            try {
                require_once __DIR__ . '/config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                if ($db) {
                    echo "<span class='status success'>✅</span> Conexión exitosa<br>";
                } else {
                    echo "<span class='status error'>❌</span> No se pudo conectar<br>";
                    echo '</div></div></div></body></html>';
                    exit;
                }
            } catch (Exception $e) {
                echo "<span class='status error'>❌</span> Error: " . $e->getMessage() . "<br>";
                echo '</div></div></div></body></html>';
                exit;
            }
            echo '</div>';
            
            // PASO 3: Verificar PDFExtractor
            echo '<div class="section">';
            echo '<h2>🔧 PASO 3: Cargar PDFExtractor</h2>';
            
            try {
                require_once __DIR__ . '/includes/PDFExtractor.php';
                
                if (class_exists('PDFExtractor')) {
                    echo "<span class='status success'>✅</span> Clase PDFExtractor cargada<br><br>";
                    
                    // Verificar métodos
                    $methods = get_class_methods('PDFExtractor');
                    $required_methods = ['extractData', 'extractWithTemplate', 'detectProveedor'];
                    
                    echo "<strong>Métodos verificados:</strong><br>";
                    foreach ($required_methods as $method) {
                        if (in_array($method, $methods)) {
                            echo "<span class='status success'>✅</span> $method<br>";
                        } else {
                            echo "<span class='status error'>❌</span> $method FALTA<br>";
                        }
                    }
                    
                    // Verificar método de moneda
                    if (in_array('detectCurrency', $methods)) {
                        echo "<span class='status success'>✅</span> detectCurrency (soporte multi-moneda)<br>";
                    } else {
                        echo "<span class='status warning'>⚠️</span> detectCurrency no existe (versión antigua)<br>";
                    }
                    
                } else {
                    echo "<span class='status error'>❌</span> No se pudo cargar la clase<br>";
                    echo '</div></div></div></body></html>';
                    exit;
                }
            } catch (Exception $e) {
                echo "<span class='status error'>❌</span> Error: " . $e->getMessage() . "<br>";
                echo '</div></div></div></body></html>';
                exit;
            }
            echo '</div>';
            
            // PASO 4: Verificar plantillas
            echo '<div class="section">';
            echo '<h2>📋 PASO 4: Plantillas en Base de Datos</h2>';
            
            try {
                $query = "SELECT p.nombre, p.ruc, pt.nombre_plantilla, pt.activo 
                          FROM proveedores p 
                          INNER JOIN proveedor_plantillas pt ON p.id = pt.proveedor_id 
                          WHERE pt.activo = 1";
                $stmt = $db->query($query);
                $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($plantillas) > 0) {
                    echo "<span class='status success'>✅</span> " . count($plantillas) . " plantillas activas<br><br>";
                    
                    foreach ($plantillas as $p) {
                        echo "• <strong>{$p['nombre']}</strong> ({$p['ruc']})<br>";
                        echo "  └─ {$p['nombre_plantilla']}<br>";
                    }
                } else {
                    echo "<span class='status error'>❌</span> No hay plantillas activas<br>";
                }
            } catch (Exception $e) {
                echo "<span class='status error'>❌</span> Error: " . $e->getMessage() . "<br>";
            }
            echo '</div>';
            
            // PASO 5: Prueba de extracción REAL
            echo '<div class="section">';
            echo '<h2>🧪 PASO 5: Prueba de Extracción</h2>';
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_pdf'])) {
                echo '<div class="test-box">';
                echo '<h3>Procesando PDF...</h3>';
                
                $file = $_FILES['test_pdf'];
                $proveedor_id = isset($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
                
                if ($file['error'] === UPLOAD_ERR_OK && $file['type'] === 'application/pdf') {
                    $temp_pdf = sys_get_temp_dir() . '/' . uniqid('test_') . '.pdf';
                    move_uploaded_file($file['tmp_name'], $temp_pdf);
                    
                    echo "<p><strong>Archivo:</strong> {$file['name']}</p>";
                    echo "<p><strong>Tamaño:</strong> " . round($file['size']/1024, 2) . " KB</p>";
                    
                    if ($proveedor_id) {
                        echo "<p><strong>Proveedor ID:</strong> $proveedor_id</p>";
                    } else {
                        echo "<p><strong>Proveedor:</strong> Auto-detectar</p>";
                    }
                    
                    echo "<hr style='margin:15px 0;'>";
                    
                    try {
                        // Crear extractor
                        $extractor = new PDFExtractor($db);
                        
                        echo "<p>✅ Extractor creado</p>";
                        
                        // Extraer datos
                        $result = $extractor->extractData($temp_pdf, $proveedor_id);
                        
                        echo "<p>✅ extractData() ejecutado</p>";
                        echo "<hr style='margin:15px 0;'>";
                        
                        // Mostrar resultado
                        echo "<h3>Resultado de Extracción:</h3>";
                        
                        if ($result['success']) {
                            echo "<span class='status success'>✅ ÉXITO</span><br><br>";
                            
                            echo "<strong>Datos extraídos:</strong><br>";
                            echo "<table style='width:100%; border-collapse: collapse;'>";
                            
                            $campos = [
                                'numero_factura' => 'Número Factura',
                                'fecha_emision' => 'Fecha Emisión',
                                'fecha_vencimiento' => 'Fecha Vencimiento',
                                'monto_total' => 'Monto Total',
                                'subtotal' => 'Subtotal',
                                'igv' => 'IGV',
                                'ruc' => 'RUC/RFC',
                                'moneda' => 'Moneda',
                                'tipo_cambio' => 'Tipo Cambio',
                                'proveedor_id' => 'Proveedor ID',
                                'metodo' => 'Método Usado',
                                'plantilla_usada' => 'Plantilla'
                            ];
                            
                            foreach ($campos as $key => $label) {
                                if (isset($result[$key])) {
                                    $valor = $result[$key];
                                    if (empty($valor) && $valor !== 0) {
                                        $valor = '<em style="color:#999;">vacío</em>';
                                    }
                                    echo "<tr style='border-bottom:1px solid #ddd;'>";
                                    echo "<td style='padding:8px; font-weight:bold; width:200px;'>$label:</td>";
                                    echo "<td style='padding:8px;'>$valor</td>";
                                    echo "</tr>";
                                }
                            }
                            echo "</table>";
                            
                        } else {
                            echo "<span class='status error'>❌ FALLÓ</span><br><br>";
                            echo "<p><strong>Error:</strong> " . ($result['error'] ?? 'Desconocido') . "</p>";
                        }
                        
                        // Mostrar resultado completo
                        echo "<br><h3>Resultado Completo (JSON):</h3>";
                        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        
                        // Obtener texto del PDF
                        $pdf_text = $extractor->getPdfText();
                        if (!empty($pdf_text)) {
                            echo "<br><h3>Texto Extraído del PDF (primeros 1000 caracteres):</h3>";
                            echo "<pre>" . htmlspecialchars(substr($pdf_text, 0, 1000)) . "...</pre>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='error-box'>";
                        echo "<strong>EXCEPCIÓN:</strong><br>";
                        echo $e->getMessage();
                        echo "<br><br><strong>Trace:</strong><br>";
                        echo "<pre>" . $e->getTraceAsString() . "</pre>";
                        echo "</div>";
                    }
                    
                    unlink($temp_pdf);
                    
                } else {
                    echo "<span class='status error'>❌</span> Error al subir archivo<br>";
                }
                
                echo '</div>';
            } else {
                // Mostrar formulario
                echo '<p>Sube un PDF para probar la extracción completa:</p>';
                
                // Obtener proveedores
                $query = "SELECT id, nombre FROM proveedores ORDER BY nombre";
                $stmt = $db->query($query);
                $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<form method="POST" enctype="multipart/form-data" style="margin-top:15px;">';
                
                echo '<div style="margin-bottom:15px;">';
                echo '<label style="display:block; margin-bottom:5px; font-weight:bold;">Proveedor (opcional):</label>';
                echo '<select name="proveedor_id" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">';
                echo '<option value="">Auto-detectar</option>';
                foreach ($proveedores as $p) {
                    echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['nombre']) . '</option>';
                }
                echo '</select>';
                echo '</div>';
                
                echo '<div style="margin-bottom:15px;">';
                echo '<label style="display:block; margin-bottom:5px; font-weight:bold;">Archivo PDF:</label>';
                echo '<input type="file" name="test_pdf" accept=".pdf" required style="padding:10px; width:100%;">';
                echo '</div>';
                
                echo '<button type="submit" style="padding:12px 30px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px; font-weight:bold;">';
                echo '🧪 Probar Extracción Completa';
                echo '</button>';
                echo '</form>';
            }
            
            echo '</div>';
            
            ?>
        </div>
    </div>
</body>
</html>
