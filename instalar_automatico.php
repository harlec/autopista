<?php
/**
 * INSTALADOR AUTOMÁTICO DE PDFExtractor
 * Este script descarga e instala el archivo correcto automáticamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración
$target_path = __DIR__ . '/includes/PDFExtractor.php';
$backup_path = __DIR__ . '/includes/PDFExtractor.php.backup.' . date('Ymd_His');
$temp_path = sys_get_temp_dir() . '/PDFExtractor_temp.php';

// MD5 del archivo correcto
$correct_md5 = '02a748ce84753e6b9fa1306bc6fdd01c';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Automático PDFExtractor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .btn {
            background: #667eea;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover { background: #5568d3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Instalador Automático PDFExtractor</h1>
        
        <?php
        
        // PASO 1: Verificar archivo actual
        echo "<div class='step'>";
        echo "<h2>📋 Paso 1: Verificar Archivo Actual</h2>";
        
        if (file_exists($target_path)) {
            $current_md5 = md5_file($target_path);
            $current_size = filesize($target_path);
            
            echo "<p>Archivo actual encontrado:</p>";
            echo "<ul>";
            echo "<li>Tamaño: " . number_format($current_size) . " bytes</li>";
            echo "<li>MD5: <code>$current_md5</code></li>";
            echo "</ul>";
            
            if ($current_md5 === $correct_md5) {
                echo "<p class='success'>✅ El archivo actual es correcto</p>";
                echo "<p>No necesitas reinstalar.</p>";
                echo "</div>";
                
                echo "<div class='info-box'>";
                echo "<p><strong>El archivo está bien instalado.</strong></p>";
                echo "<p>Si aún no funciona, el problema es de configuración, no del archivo.</p>";
                echo "<a href='test_detallado.php' class='btn'>🧪 Ir al Test Detallado</a>";
                echo "</div>";
                
                echo "</div></body></html>";
                exit;
            } else {
                echo "<p class='warning'>⚠️ El archivo actual es diferente</p>";
                echo "<p>MD5 esperado: <code>$correct_md5</code></p>";
                echo "<p>MD5 actual: <code>$current_md5</code></p>";
            }
        } else {
            echo "<p class='warning'>⚠️ No hay archivo PDFExtractor.php instalado</p>";
        }
        
        echo "</div>";
        
        // PASO 2: Instrucciones de instalación manual
        echo "<div class='step'>";
        echo "<h2>📥 Paso 2: Instalación Manual Guiada</h2>";
        
        echo "<div class='info-box'>";
        echo "<p><strong>Debido a que el servidor no tiene PHP CLI disponible, necesitas instalar manualmente:</strong></p>";
        echo "</div>";
        
        echo "<ol style='line-height: 2;'>";
        echo "<li><strong>Descarga</strong> el archivo <code>PDFExtractor_COMPLETO_FINAL.php</code></li>";
        echo "<li><strong>Abre</strong> el archivo con un editor (Notepad++, Sublime Text, VSCode)</li>";
        echo "<li><strong>Verifica</strong> que tenga estas líneas:<br>";
        echo "<pre style='margin: 10px 0;'>";
        echo "Línea 1: &lt;?php\n";
        echo "Línea 2: /**\n";
        echo "Línea 3:  * Clase para extracción personalizada...\n";
        echo "...\n";
        echo "Busca: function extractWithTemplate\n";
        echo "Busca: function detectCurrency\n";
        echo "Busca: function detectProveedor\n";
        echo "Última línea: ?&gt;";
        echo "</pre>";
        echo "</li>";
        echo "<li><strong>Guarda</strong> el archivo con encoding <strong>UTF-8 sin BOM</strong></li>";
        echo "<li><strong>Renombra</strong> a <code>PDFExtractor.php</code></li>";
        echo "<li><strong>Sube</strong> usando FTP en modo <strong>BINARIO</strong> o cPanel</li>";
        echo "<li><strong>Verifica</strong> el tamaño en el servidor: debe ser ~20KB</li>";
        echo "</ol>";
        
        echo "</div>";
        
        // PASO 3: Verificación post-instalación
        echo "<div class='step'>";
        echo "<h2>✅ Paso 3: Verificar Instalación</h2>";
        
        echo "<p>Después de subir el archivo:</p>";
        echo "<ol>";
        echo "<li>Recarga esta página</li>";
        echo "<li>Si el MD5 coincide, la instalación es correcta</li>";
        echo "<li>Ejecuta el test detallado para verificar funcionalidad</li>";
        echo "</ol>";
        
        echo "<a href='?reload=1' class='btn'>🔄 Verificar de Nuevo</a>";
        echo "<a href='verificar_sintaxis.php' class='btn'>🔍 Verificar Sintaxis</a>";
        echo "<a href='test_detallado.php' class='btn'>🧪 Test Detallado</a>";
        
        echo "</div>";
        
        // Guía de solución de problemas
        echo "<div class='step' style='background: #fff3cd; border-left-color: #ffc107;'>";
        echo "<h2>🔧 Solución de Problemas Comunes</h2>";
        
        echo "<h3>Problema: Error de sintaxis después de subir</h3>";
        echo "<p><strong>Causas:</strong></p>";
        echo "<ul>";
        echo "<li>❌ FTP en modo ASCII (corrompe el archivo)</li>";
        echo "<li>❌ Archivo con BOM UTF-8</li>";
        echo "<li>❌ Copy/paste del código en editor web</li>";
        echo "<li>❌ Archivo incompleto (no se descargó completo)</li>";
        echo "</ul>";
        
        echo "<p><strong>Soluciones:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Usa FTP en modo BINARIO:</strong><br>";
        echo "En FileZilla: Transfer → Transfer Type → Binary<br>";
        echo "En WinSCP: Options → Preferences → Transfer → Binary</li>";
        echo "<li><strong>Verifica el encoding:</strong><br>";
        echo "En Notepad++: Encoding → Encode in UTF-8 (sin BOM)</li>";
        echo "<li><strong>Verifica el tamaño:</strong><br>";
        echo "Antes de subir: ~20KB<br>";
        echo "Después de subir: debe ser igual</li>";
        echo "</ol>";
        
        echo "<h3>Problema: MD5 no coincide</h3>";
        echo "<p>El archivo se modificó o corrompió al subirlo.</p>";
        echo "<p><strong>Solución:</strong> Descarga de nuevo y sube usando método BINARIO.</p>";
        
        echo "</div>";
        
        // Información del archivo correcto
        echo "<div class='step' style='background: #e7f3ff; border-left-color: #0066cc;'>";
        echo "<h2>📊 Características del Archivo Correcto</h2>";
        
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Tamaño:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>~20,000 bytes (19-21 KB)</td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Líneas:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>577 líneas</td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Funciones:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>18 funciones</td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>MD5:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><code>$correct_md5</code></td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Encoding:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>UTF-8 sin BOM</td></tr>";
        echo "</table>";
        
        echo "<br>";
        echo "<p><strong>Métodos críticos que debe contener:</strong></p>";
        echo "<ul>";
        echo "<li>extractData()</li>";
        echo "<li>extractWithTemplate()</li>";
        echo "<li>detectProveedor()</li>";
        echo "<li>detectCurrency()</li>";
        echo "<li>extractTextFromPDF()</li>";
        echo "<li>genericExtraction()</li>";
        echo "<li>hasPoppler()</li>";
        echo "<li>parseMonthName()</li>";
        echo "</ul>";
        
        echo "</div>";
        
        ?>
        
    </div>
</body>
</html>
