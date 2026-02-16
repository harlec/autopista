<?php
/**
 * Script de prueba para verificar poppler-utils y extracción de PDFs
 * Sube este archivo a /test_poppler.php en tu servidor
 */

// Verificar si poppler-utils está instalado
function checkPoppler() {
    $output = [];
    $return_var = 0;
    @exec('which pdftotext 2>&1', $output, $return_var);
    
    return [
        'installed' => $return_var === 0,
        'path' => $return_var === 0 ? implode("\n", $output) : null
    ];
}

// Verificar versión de poppler
function getPopplerVersion() {
    $output = [];
    $return_var = 0;
    @exec('pdftotext -v 2>&1', $output, $return_var);
    
    return implode("\n", $output);
}

// Obtener información del sistema
function getSystemInfo() {
    return [
        'php_version' => phpversion(),
        'exec_enabled' => function_exists('exec'),
        'shell_exec_enabled' => function_exists('shell_exec'),
        'open_basedir' => ini_get('open_basedir'),
        'safe_mode' => ini_get('safe_mode'),
        'disable_functions' => ini_get('disable_functions')
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Poppler-Utils</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
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
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
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
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
            font-family: monospace;
            background: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .upload-section {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: white;
            margin-top: 20px;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .result {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid #ddd;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Test Poppler-Utils</h1>
            <p>Verificación de herramientas de extracción de PDFs</p>
        </div>
        
        <div class="content">
            <?php
            $poppler = checkPoppler();
            $systemInfo = getSystemInfo();
            ?>
            
            <!-- Estado de Poppler -->
            <div class="section">
                <h2>📦 Estado de Poppler-Utils</h2>
                <?php if ($poppler['installed']): ?>
                    <span class="status success">✅ INSTALADO</span>
                    <div class="info-grid">
                        <div class="info-label">Ubicación:</div>
                        <div class="info-value"><?php echo htmlspecialchars($poppler['path']); ?></div>
                        
                        <div class="info-label">Versión:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars(getPopplerVersion())); ?></div>
                    </div>
                <?php else: ?>
                    <span class="status error">❌ NO INSTALADO</span>
                    <div class="alert warning" style="margin-top: 15px;">
                        <strong>⚠️ Poppler-utils no está instalado o no es accesible</strong>
                        <p style="margin-top: 10px;">Para instalarlo en tu servidor:</p>
                        <code style="display: block; background: white; padding: 10px; margin-top: 10px; border-radius: 5px;">
                            sudo apt-get install poppler-utils
                        </code>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Información del Sistema -->
            <div class="section">
                <h2>⚙️ Configuración del Sistema</h2>
                <div class="info-grid">
                    <div class="info-label">PHP Version:</div>
                    <div class="info-value"><?php echo $systemInfo['php_version']; ?></div>
                    
                    <div class="info-label">exec() habilitado:</div>
                    <div class="info-value">
                        <?php echo $systemInfo['exec_enabled'] ? '✅ Sí' : '❌ No'; ?>
                    </div>
                    
                    <div class="info-label">shell_exec() habilitado:</div>
                    <div class="info-value">
                        <?php echo $systemInfo['shell_exec_enabled'] ? '✅ Sí' : '❌ No'; ?>
                    </div>
                    
                    <div class="info-label">Funciones deshabilitadas:</div>
                    <div class="info-value">
                        <?php echo empty($systemInfo['disable_functions']) ? 'Ninguna' : $systemInfo['disable_functions']; ?>
                    </div>
                </div>
                
                <?php if (!$systemInfo['exec_enabled']): ?>
                    <div class="alert warning" style="margin-top: 15px;">
                        <strong>⚠️ La función exec() está deshabilitada</strong>
                        <p style="margin-top: 10px;">Contacta con tu proveedor de hosting para habilitarla.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Test de Extracción -->
            <?php if ($poppler['installed'] && $systemInfo['exec_enabled']): ?>
            <div class="section">
                <h2>🧪 Probar Extracción de PDF</h2>
                <p style="margin-bottom: 15px;">Sube un PDF para probar la extracción de texto con poppler-utils:</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-section">
                        <div class="upload-icon">📄</div>
                        <input type="file" name="test_pdf" accept=".pdf" required>
                        <br><br>
                        <button type="submit" name="test_extract" class="btn">Probar Extracción</button>
                    </div>
                </form>
                
                <?php
                if (isset($_POST['test_extract']) && isset($_FILES['test_pdf'])) {
                    $file = $_FILES['test_pdf'];
                    
                    if ($file['error'] === UPLOAD_ERR_OK && $file['type'] === 'application/pdf') {
                        $temp_pdf = sys_get_temp_dir() . '/' . uniqid('test_') . '.pdf';
                        move_uploaded_file($file['tmp_name'], $temp_pdf);
                        
                        $temp_txt = sys_get_temp_dir() . '/' . uniqid('test_') . '.txt';
                        $command = sprintf(
                            'pdftotext -layout -enc UTF-8 %s %s 2>&1',
                            escapeshellarg($temp_pdf),
                            escapeshellarg($temp_txt)
                        );
                        
                        exec($command, $output, $return_var);
                        
                        echo '<div class="result">';
                        if ($return_var === 0 && file_exists($temp_txt)) {
                            echo '<strong style="color: green;">✅ Extracción Exitosa</strong><br><br>';
                            echo '<strong>Texto extraído:</strong><br><br>';
                            echo htmlspecialchars(file_get_contents($temp_txt));
                            unlink($temp_txt);
                        } else {
                            echo '<strong style="color: red;">❌ Error en la extracción</strong><br><br>';
                            echo 'Código de salida: ' . $return_var . '<br>';
                            echo 'Salida: ' . htmlspecialchars(implode("\n", $output));
                        }
                        echo '</div>';
                        
                        unlink($temp_pdf);
                    } else {
                        echo '<div class="alert warning">⚠️ Error: Solo se permiten archivos PDF</div>';
                    }
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Recomendaciones -->
            <div class="section">
                <h2>💡 Recomendaciones</h2>
                <?php if ($poppler['installed'] && $systemInfo['exec_enabled']): ?>
                    <p style="color: green; font-weight: bold;">✅ Tu servidor está correctamente configurado para extraer PDFs</p>
                    <p style="margin-top: 10px;">El sistema usará <strong>poppler-utils (pdftotext)</strong> que es más rápido y confiable que las librerías PHP.</p>
                <?php elseif (!$poppler['installed']): ?>
                    <p style="color: orange;">⚠️ Instala poppler-utils para mejor rendimiento</p>
                    <p style="margin-top: 10px;">Alternativamente, el sistema puede usar <strong>smalot/pdfparser</strong> (requiere Composer).</p>
                <?php else: ?>
                    <p style="color: red;">❌ Funciones de ejecución deshabilitadas</p>
                    <p style="margin-top: 10px;">Contacta con tu proveedor de hosting para habilitar exec().</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
