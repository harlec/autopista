<?php
/**
 * Script de Diagnóstico - Extracción de PDFs
 * Subir a: /diagnostico_pdf.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Sistema de Facturas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 5px 0;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        .info-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .info-label { font-weight: bold; color: #555; }
        .info-value {
            color: #333;
            font-family: monospace;
            background: white;
            padding: 5px 10px;
            border-radius: 5px;
            word-break: break-all;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico del Sistema</h1>
            <p>Verificación de Extracción de PDFs</p>
        </div>
        
        <div class="content">
            <?php
            
            // 1. VERIFICAR ESTRUCTURA DE DIRECTORIOS
            echo '<div class="section">';
            echo '<h2>📁 Estructura de Directorios</h2>';
            
            $directories = [
                'config' => __DIR__ . '/config',
                'includes' => __DIR__ . '/includes',
                'modules' => __DIR__ . '/modules',
                'ajax' => __DIR__ . '/ajax',
                'assets' => __DIR__ . '/assets',
                'vendor' => __DIR__ . '/vendor'
            ];
            
            echo '<div class="info-grid">';
            foreach ($directories as $name => $path) {
                echo '<div class="info-label">' . $name . ':</div>';
                echo '<div class="info-value">';
                if (is_dir($path)) {
                    echo '<span class="status success">✅ Existe</span> ' . $path;
                } else {
                    echo '<span class="status error">❌ No existe</span> ' . $path;
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            
            // 2. VERIFICAR ARCHIVOS CLAVE
            echo '<div class="section">';
            echo '<h2>📄 Archivos Clave</h2>';
            
            $files = [
                'Database' => __DIR__ . '/config/database.php',
                'Functions' => __DIR__ . '/includes/functions.php',
                'PDFExtractor' => __DIR__ . '/includes/PDFExtractor.php',
                'Procesar PDF' => __DIR__ . '/ajax/procesar_pdf.php',
                'Nueva Factura' => __DIR__ . '/modules/facturas/nueva.php'
            ];
            
            echo '<div class="info-grid">';
            foreach ($files as $name => $path) {
                echo '<div class="info-label">' . $name . ':</div>';
                echo '<div class="info-value">';
                if (file_exists($path)) {
                    echo '<span class="status success">✅ Existe</span>';
                    echo '<br><small>' . $path . '</small>';
                } else {
                    echo '<span class="status error">❌ No existe</span>';
                    echo '<br><small>' . $path . '</small>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            
            // 3. VERIFICAR CLASES PHP
            echo '<div class="section">';
            echo '<h2>🔧 Clases PHP</h2>';
            
            if (file_exists(__DIR__ . '/includes/PDFExtractor.php')) {
                require_once __DIR__ . '/includes/PDFExtractor.php';
                
                if (class_exists('PDFExtractor')) {
                    echo '<span class="status success">✅ PDFExtractor existe</span><br>';
                    
                    // Verificar métodos
                    $methods = get_class_methods('PDFExtractor');
                    echo '<div style="margin-top:10px"><strong>Métodos disponibles:</strong></div>';
                    echo '<pre>' . print_r($methods, true) . '</pre>';
                } else {
                    echo '<span class="status error">❌ PDFExtractor no se puede cargar</span>';
                }
            } else {
                echo '<span class="status error">❌ PDFExtractor.php no existe</span>';
            }
            echo '</div>';
            
            // 4. VERIFICAR BASE DE DATOS
            echo '<div class="section">';
            echo '<h2>🗄️ Conexión a Base de Datos</h2>';
            
            if (file_exists(__DIR__ . '/config/database.php')) {
                try {
                    require_once __DIR__ . '/config/database.php';
                    
                    if (class_exists('Database')) {
                        $database = new Database();
                        $db = $database->getConnection();
                        
                        if ($db) {
                            echo '<span class="status success">✅ Conexión exitosa</span><br><br>';
                            
                            // Verificar tablas
                            $tables = ['facturas', 'proveedores', 'proveedor_plantillas', 'tipos_cambio'];
                            echo '<div class="info-grid">';
                            foreach ($tables as $table) {
                                try {
                                    $query = "SELECT COUNT(*) as total FROM $table";
                                    $stmt = $db->query($query);
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    echo '<div class="info-label">' . $table . ':</div>';
                                    echo '<div class="info-value">';
                                    echo '<span class="status success">✅ Existe</span> ';
                                    echo '(' . $result['total'] . ' registros)';
                                    echo '</div>';
                                } catch (Exception $e) {
                                    echo '<div class="info-label">' . $table . ':</div>';
                                    echo '<div class="info-value">';
                                    echo '<span class="status error">❌ No existe</span>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                            
                            // Verificar columnas de moneda
                            echo '<br><strong>Verificando columnas multi-moneda:</strong><br><br>';
                            try {
                                $query = "SHOW COLUMNS FROM facturas LIKE 'moneda'";
                                $stmt = $db->query($query);
                                $moneda_col = $stmt->fetch();
                                
                                if ($moneda_col) {
                                    echo '<span class="status success">✅ Columna "moneda" existe en facturas</span><br>';
                                } else {
                                    echo '<span class="status warning">⚠️ Columna "moneda" NO existe - Ejecutar upgrade_multimoneda.sql</span><br>';
                                }
                                
                                $query = "SHOW COLUMNS FROM facturas LIKE 'tipo_cambio'";
                                $stmt = $db->query($query);
                                $tc_col = $stmt->fetch();
                                
                                if ($tc_col) {
                                    echo '<span class="status success">✅ Columna "tipo_cambio" existe en facturas</span><br>';
                                } else {
                                    echo '<span class="status warning">⚠️ Columna "tipo_cambio" NO existe - Ejecutar upgrade_multimoneda.sql</span><br>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="error-box">' . $e->getMessage() . '</div>';
                            }
                            
                        } else {
                            echo '<span class="status error">❌ No se pudo conectar</span>';
                        }
                    } else {
                        echo '<span class="status error">❌ Clase Database no existe</span>';
                    }
                } catch (Exception $e) {
                    echo '<span class="status error">❌ Error: ' . $e->getMessage() . '</span>';
                }
            } else {
                echo '<span class="status error">❌ database.php no existe</span>';
            }
            echo '</div>';
            
            // 5. VERIFICAR POPPLER
            echo '<div class="section">';
            echo '<h2>📦 Poppler-Utils</h2>';
            
            $output = [];
            $return_var = 0;
            @exec('which pdftotext 2>&1', $output, $return_var);
            
            if ($return_var === 0) {
                echo '<span class="status success">✅ pdftotext disponible</span><br>';
                echo '<div class="info-value">' . implode("\n", $output) . '</div><br>';
                
                // Verificar versión
                $output2 = [];
                @exec('pdftotext -v 2>&1', $output2);
                echo '<strong>Versión:</strong><br>';
                echo '<pre>' . implode("\n", $output2) . '</pre>';
            } else {
                echo '<span class="status error">❌ pdftotext no disponible</span><br>';
                echo '<div class="error-box">Instala poppler-utils: sudo apt-get install poppler-utils</div>';
            }
            echo '</div>';
            
            // 6. VERIFICAR PROVEEDORES CON PLANTILLA
            echo '<div class="section">';
            echo '<h2>👥 Proveedores con Plantilla</h2>';
            
            if (isset($db)) {
                try {
                    $query = "SELECT p.id, p.nombre, p.ruc, p.tiene_plantilla, 
                              COUNT(pt.id) as num_plantillas
                              FROM proveedores p
                              LEFT JOIN proveedor_plantillas pt ON p.id = pt.proveedor_id
                              GROUP BY p.id
                              ORDER BY p.nombre";
                    $stmt = $db->query($query);
                    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($proveedores) > 0) {
                        echo '<table style="width:100%; border-collapse: collapse;">';
                        echo '<thead style="background:#667eea; color:white;">';
                        echo '<tr><th style="padding:10px; text-align:left;">Proveedor</th>';
                        echo '<th style="padding:10px; text-align:left;">RUC/RFC</th>';
                        echo '<th style="padding:10px; text-align:center;">Plantillas</th>';
                        echo '<th style="padding:10px; text-align:center;">Estado</th></tr>';
                        echo '</thead><tbody>';
                        
                        foreach ($proveedores as $p) {
                            echo '<tr style="border-bottom:1px solid #ddd;">';
                            echo '<td style="padding:10px;">' . htmlspecialchars($p['nombre']) . '</td>';
                            echo '<td style="padding:10px;">' . htmlspecialchars($p['ruc']) . '</td>';
                            echo '<td style="padding:10px; text-align:center;">' . $p['num_plantillas'] . '</td>';
                            echo '<td style="padding:10px; text-align:center;">';
                            if ($p['num_plantillas'] > 0) {
                                echo '<span class="status success">✅ Configurado</span>';
                            } else {
                                echo '<span class="status warning">⚠️ Sin plantilla</span>';
                            }
                            echo '</td></tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<span class="status warning">⚠️ No hay proveedores registrados</span>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error-box">' . $e->getMessage() . '</div>';
                }
            }
            echo '</div>';
            
            // 7. PROBAR EXTRACCIÓN
            echo '<div class="section">';
            echo '<h2>🧪 Prueba de Extracción</h2>';
            echo '<p>Sube un PDF para probar la extracción:</p>';
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_pdf'])) {
                $file = $_FILES['test_pdf'];
                
                if ($file['error'] === UPLOAD_ERR_OK && $file['type'] === 'application/pdf') {
                    $temp_pdf = sys_get_temp_dir() . '/' . uniqid('test_') . '.pdf';
                    move_uploaded_file($file['tmp_name'], $temp_pdf);
                    
                    echo '<div style="background:white; padding:15px; border-radius:5px; margin-top:15px;">';
                    echo '<h3>Resultado de Extracción:</h3>';
                    
                    try {
                        if (class_exists('PDFExtractor') && isset($db)) {
                            $extractor = new PDFExtractor($db);
                            $result = $extractor->extractData($temp_pdf);
                            
                            echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                        } else {
                            echo '<span class="status error">❌ No se puede instanciar PDFExtractor</span>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="error-box">' . $e->getMessage() . '</div>';
                    }
                    
                    unlink($temp_pdf);
                    echo '</div>';
                }
            }
            
            echo '<form method="POST" enctype="multipart/form-data" style="margin-top:15px;">';
            echo '<input type="file" name="test_pdf" accept=".pdf" required style="padding:10px;">';
            echo '<button type="submit" style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; margin-left:10px;">Probar Extracción</button>';
            echo '</form>';
            echo '</div>';
            
            ?>
            
            <!-- Resumen Final -->
            <div class="section">
                <h2>📋 Resumen y Recomendaciones</h2>
                <?php
                $issues = [];
                
                if (!is_dir(__DIR__ . '/includes')) {
                    $issues[] = '❌ Falta directorio /includes/';
                }
                if (!file_exists(__DIR__ . '/includes/PDFExtractor.php')) {
                    $issues[] = '❌ Falta archivo PDFExtractor.php';
                }
                if (!file_exists(__DIR__ . '/ajax/procesar_pdf.php')) {
                    $issues[] = '❌ Falta archivo procesar_pdf.php';
                }
                
                if (empty($issues)) {
                    echo '<span class="status success">✅ Todo está correctamente configurado</span>';
                } else {
                    echo '<h3>Problemas detectados:</h3>';
                    echo '<ul>';
                    foreach ($issues as $issue) {
                        echo '<li>' . $issue . '</li>';
                    }
                    echo '</ul>';
                    
                    echo '<h3 style="margin-top:20px;">Soluciones:</h3>';
                    echo '<ol>';
                    echo '<li>Asegúrate de haber subido todos los archivos a las rutas correctas</li>';
                    echo '<li>Verifica los permisos de los directorios (755)</li>';
                    echo '<li>Revisa el archivo procesar_pdf.php en /ajax/</li>';
                    echo '<li>Ejecuta upgrade_multimoneda.sql si aún no lo has hecho</li>';
                    echo '</ol>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
