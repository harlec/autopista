<?php
/**
 * VERIFICADOR DE SINTAXIS Y CARGA
 * Detecta errores de sintaxis, encoding, y problemas de carga
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificador de Sintaxis</title>
    <style>
        body {
            font-family: monospace;
            background: #000;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        .error { color: #f00; background: #300; padding: 10px; margin: 10px 0; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        pre { 
            background: #111; 
            padding: 15px; 
            border: 1px solid #0f0; 
            overflow-x: auto; 
            white-space: pre-wrap;
        }
        .box { 
            border: 2px solid #0f0; 
            padding: 15px; 
            margin: 20px 0; 
        }
        h1 { color: #0ff; }
        h2 { color: #ff0; }
    </style>
</head>
<body>

<h1>🔍 VERIFICADOR DE SINTAXIS PHP</h1>

<?php

$pdf_extractor_path = __DIR__ . '/includes/PDFExtractor.php';

// ========================================
// TEST 1: VERIFICAR ARCHIVO
// ========================================
echo "<div class='box'>";
echo "<h2>1. INFORMACIÓN DEL ARCHIVO</h2>";

if (!file_exists($pdf_extractor_path)) {
    echo "<span class='error'>❌ ARCHIVO NO EXISTE: $pdf_extractor_path</span><br>";
    echo "</div></body></html>";
    exit;
}

echo "<span class='success'>✅ Archivo existe</span><br><br>";

$size = filesize($pdf_extractor_path);
$perms = substr(sprintf('%o', fileperms($pdf_extractor_path)), -4);

echo "📍 Ruta: $pdf_extractor_path<br>";
echo "📦 Tamaño: " . number_format($size) . " bytes (" . round($size/1024, 1) . " KB)<br>";
echo "🔐 Permisos: $perms<br>";
echo "📝 Líneas: " . count(file($pdf_extractor_path)) . "<br>";

if ($size < 15000) {
    echo "<span class='warning'>⚠️ ADVERTENCIA: Archivo pequeño. Debería ser ~20KB</span><br>";
}

echo "</div>";

// ========================================
// TEST 2: VERIFICAR ENCODING
// ========================================
echo "<div class='box'>";
echo "<h2>2. VERIFICAR ENCODING</h2>";

$content = file_get_contents($pdf_extractor_path);

// Detectar BOM
$bom_utf8 = pack('H*','EFBBBF');
$bom_utf16be = pack('H*','FEFF');
$bom_utf16le = pack('H*','FFFE');

if (substr($content, 0, 3) === $bom_utf8) {
    echo "<span class='warning'>⚠️ Tiene BOM UTF-8 (puede causar problemas)</span><br>";
} elseif (substr($content, 0, 2) === $bom_utf16be) {
    echo "<span class='error'>❌ Tiene BOM UTF-16 BE</span><br>";
} elseif (substr($content, 0, 2) === $bom_utf16le) {
    echo "<span class='error'>❌ Tiene BOM UTF-16 LE</span><br>";
} else {
    echo "<span class='success'>✅ Sin BOM</span><br>";
}

// Verificar si es válido UTF-8
if (mb_check_encoding($content, 'UTF-8')) {
    echo "<span class='success'>✅ Encoding UTF-8 válido</span><br>";
} else {
    echo "<span class='error'>❌ Encoding NO es UTF-8 válido</span><br>";
}

echo "</div>";

// ========================================
// TEST 3: VERIFICAR SINTAXIS PHP
// ========================================
echo "<div class='box'>";
echo "<h2>3. VERIFICAR SINTAXIS PHP</h2>";

// Método 1: php -l (lint)
$output = [];
$return_var = 0;
$command = "php -l " . escapeshellarg($pdf_extractor_path) . " 2>&1";
exec($command, $output, $return_var);

echo "<strong>Resultado de 'php -l' (syntax check):</strong><br>";
echo "<pre>";
echo htmlspecialchars(implode("\n", $output));
echo "</pre>";

if ($return_var === 0) {
    echo "<span class='success'>✅ Sintaxis PHP correcta</span><br>";
} else {
    echo "<span class='error'>❌ ERROR DE SINTAXIS DETECTADO</span><br>";
    echo "<div class='error'>";
    echo "El archivo tiene errores de sintaxis PHP.<br>";
    echo "No se puede cargar hasta que se corrija.<br>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ========================================
// TEST 4: BUSCAR MÉTODOS EN EL CÓDIGO
// ========================================
echo "<div class='box'>";
echo "<h2>4. BUSCAR MÉTODOS EN EL CÓDIGO FUENTE</h2>";

$lines = file($pdf_extractor_path);
$methods_found = [];
$in_class = false;

foreach ($lines as $line_num => $line) {
    // Detectar inicio de clase
    if (preg_match('/class\s+PDFExtractor/i', $line)) {
        $in_class = true;
        echo "<span class='info'>Línea " . ($line_num + 1) . ": Clase PDFExtractor encontrada</span><br>";
    }
    
    // Buscar funciones
    if ($in_class && preg_match('/(?:public|private|protected)?\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $line, $matches)) {
        $method_name = $matches[1];
        $methods_found[] = $method_name;
    }
}

echo "<br><strong>Métodos encontrados en el código (" . count($methods_found) . "):</strong><br>";
foreach ($methods_found as $method) {
    echo "• $method<br>";
}

// Verificar métodos críticos
$critical_methods = ['extractData', 'extractWithTemplate', 'detectProveedor', 'detectCurrency'];
echo "<br><strong>Verificación de métodos críticos en código fuente:</strong><br>";

foreach ($critical_methods as $method) {
    if (in_array($method, $methods_found)) {
        echo "<span class='success'>✅</span> $method - encontrado en código<br>";
    } else {
        echo "<span class='error'>❌</span> $method - NO encontrado en código<br>";
    }
}

echo "</div>";

// ========================================
// TEST 5: INTENTAR CARGAR CON INCLUDE
// ========================================
echo "<div class='box'>";
echo "<h2>5. INTENTAR CARGAR EL ARCHIVO</h2>";

echo "<strong>Método 1: require_once</strong><br>";

// Capturar cualquier output del archivo
ob_start();
$load_error = null;

try {
    require_once $pdf_extractor_path;
    $included_output = ob_get_clean();
    
    if (!empty($included_output)) {
        echo "<span class='warning'>⚠️ El archivo produjo output al cargarse:</span><br>";
        echo "<pre>" . htmlspecialchars($included_output) . "</pre>";
    } else {
        echo "<span class='success'>✅ Archivo cargado sin output</span><br>";
    }
    
} catch (ParseError $e) {
    ob_end_clean();
    $load_error = $e;
    echo "<div class='error'>";
    echo "<strong>❌ ERROR DE PARSEO (ParseError):</strong><br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
    
} catch (Error $e) {
    ob_end_clean();
    $load_error = $e;
    echo "<div class='error'>";
    echo "<strong>❌ ERROR FATAL (Error):</strong><br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
    
} catch (Exception $e) {
    ob_end_clean();
    $load_error = $e;
    echo "<div class='error'>";
    echo "<strong>❌ EXCEPCIÓN (Exception):</strong><br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ========================================
// TEST 6: VERIFICAR CLASE CARGADA
// ========================================
echo "<div class='box'>";
echo "<h2>6. VERIFICAR CLASE EN MEMORIA</h2>";

if (class_exists('PDFExtractor', false)) {
    echo "<span class='success'>✅ Clase PDFExtractor existe en memoria</span><br><br>";
    
    // Obtener métodos de la clase
    $reflection = new ReflectionClass('PDFExtractor');
    $methods = $reflection->getMethods();
    
    echo "<strong>Métodos de la clase cargada (" . count($methods) . "):</strong><br>";
    
    $method_names = [];
    foreach ($methods as $method) {
        $visibility = '';
        if ($method->isPublic()) $visibility = 'public';
        if ($method->isPrivate()) $visibility = 'private';
        if ($method->isProtected()) $visibility = 'protected';
        
        $method_name = $method->getName();
        $method_names[] = $method_name;
        
        echo "• <span class='info'>$visibility</span> $method_name()<br>";
    }
    
    // Comparar con lo encontrado en código
    echo "<br><strong>Comparación:</strong><br>";
    echo "Métodos en código fuente: " . count($methods_found) . "<br>";
    echo "Métodos en clase cargada: " . count($method_names) . "<br>";
    
    if (count($method_names) < count($methods_found)) {
        echo "<span class='error'>⚠️ Se cargaron MENOS métodos de los que hay en el código</span><br>";
        
        $missing = array_diff($methods_found, $method_names);
        if (!empty($missing)) {
            echo "<br><strong>Métodos que están en el código pero NO en la clase:</strong><br>";
            foreach ($missing as $m) {
                echo "<span class='error'>❌</span> $m<br>";
            }
        }
    } elseif (count($method_names) > count($methods_found)) {
        echo "<span class='warning'>⚠️ Se cargaron MÁS métodos de los esperados (puede haber herencia)</span><br>";
    } else {
        echo "<span class='success'>✅ Coinciden los métodos</span><br>";
    }
    
    // Verificar métodos críticos en la clase
    echo "<br><strong>Métodos críticos en la clase cargada:</strong><br>";
    foreach ($critical_methods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "<span class='success'>✅</span> $method - existe y es llamable<br>";
        } else {
            echo "<span class='error'>❌</span> $method - NO existe en la clase<br>";
        }
    }
    
} else {
    echo "<div class='error'>";
    echo "❌ CLASE PDFExtractor NO EXISTE EN MEMORIA<br><br>";
    echo "El archivo se cargó pero la clase no se registró.<br>";
    echo "Posibles causas:<br>";
    echo "• El archivo no contiene 'class PDFExtractor'<br>";
    echo "• La clase está dentro de un namespace<br>";
    echo "• Hay un error de sintaxis que no se detectó<br>";
    echo "</div>";
}

echo "</div>";

// ========================================
// TEST 7: MOSTRAR INICIO Y FIN DEL ARCHIVO
// ========================================
echo "<div class='box'>";
echo "<h2>7. CONTENIDO DEL ARCHIVO</h2>";

echo "<strong>Primeras 20 líneas:</strong><br>";
echo "<pre>";
for ($i = 0; $i < min(20, count($lines)); $i++) {
    echo sprintf("%3d: %s", $i+1, htmlspecialchars($lines[$i]));
}
echo "</pre>";

echo "<strong>Últimas 20 líneas:</strong><br>";
echo "<pre>";
$start = max(0, count($lines) - 20);
for ($i = $start; $i < count($lines); $i++) {
    echo sprintf("%3d: %s", $i+1, htmlspecialchars($lines[$i]));
}
echo "</pre>";

echo "</div>";

// ========================================
// RESUMEN
// ========================================
echo "<div class='box' style='border-color: #ff0;'>";
echo "<h2>📋 RESUMEN Y DIAGNÓSTICO</h2>";

$issues = [];

if ($size < 15000) {
    $issues[] = "⚠️ Archivo muy pequeño ($size bytes). Debería ser ~20KB";
}

if (count($methods_found) < 15) {
    $issues[] = "⚠️ Solo " . count($methods_found) . " métodos en código. Debería tener ~18";
}

if (!class_exists('PDFExtractor', false)) {
    $issues[] = "❌ CRÍTICO: Clase no se cargó en memoria";
} else {
    $missing_in_class = [];
    foreach ($critical_methods as $method) {
        if (!method_exists('PDFExtractor', $method)) {
            $missing_in_class[] = $method;
        }
    }
    
    if (!empty($missing_in_class)) {
        $issues[] = "❌ CRÍTICO: Faltan métodos en la clase: " . implode(', ', $missing_in_class);
    }
}

if (empty($issues)) {
    echo "<span class='success' style='font-size: 20px;'>✅ TODO ESTÁ CORRECTO</span><br><br>";
    echo "El archivo está bien. Si aún no funciona, el problema está en otro lado.<br>";
} else {
    echo "<span class='error' style='font-size: 20px;'>❌ PROBLEMAS DETECTADOS:</span><br><br>";
    foreach ($issues as $issue) {
        echo "$issue<br>";
    }
    
    echo "<br><strong>SOLUCIÓN:</strong><br>";
    echo "1. Descarga de nuevo PDFExtractor_COMPLETO_FINAL.php<br>";
    echo "2. Verifica que tenga 20KB ANTES de subirlo<br>";
    echo "3. Súbelo en modo BINARIO (no ASCII) si usas FTP<br>";
    echo "4. Verifica que no se corrompió al subir<br>";
}

echo "</div>";

?>

<div style="text-align: center; margin: 30px 0;">
    <button onclick="location.reload()" style="background: #0f0; color: #000; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        🔄 RECARGAR TEST
    </button>
</div>

</body>
</html>
