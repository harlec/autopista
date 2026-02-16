<?php
/**
 * BUSCAR TODOS LOS ARCHIVOS PDFExtractor.php
 */

echo "<h1>🔍 Buscar Archivos PDFExtractor</h1>";

$base_dir = __DIR__;

echo "<p><strong>Directorio base:</strong> $base_dir</p>";
echo "<hr>";

// Función recursiva para buscar archivos
function findFiles($dir, $filename, &$results = []) {
    if (!is_dir($dir)) return $results;
    
    $files = @scandir($dir);
    if (!$files) return $results;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_file($path) && $file === $filename) {
            $results[] = $path;
        }
        
        if (is_dir($path) && $file !== 'vendor' && $file !== 'node_modules') {
            findFiles($path, $filename, $results);
        }
    }
    
    return $results;
}

echo "<h2>Buscando PDFExtractor.php...</h2>";

$found = findFiles($base_dir, 'PDFExtractor.php');

if (empty($found)) {
    echo "<p style='color:red;'>❌ No se encontró ningún archivo PDFExtractor.php</p>";
} else {
    echo "<p style='color:green;'>✅ Encontrados " . count($found) . " archivo(s):</p>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>Ruta</th><th>Tamaño</th><th>MD5</th><th>Métodos</th></tr>";
    
    foreach ($found as $file) {
        $size = filesize($file);
        $md5 = md5_file($file);
        
        // Contar métodos
        $content = file_get_contents($file);
        $method_count = substr_count($content, 'function ');
        
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($file) . "</code></td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td><code>$md5</code></td>";
        echo "<td>$method_count funciones</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if (count($found) > 1) {
        echo "<div style='background:#fff3cd; padding:15px; margin:20px 0; border-left:4px solid #ffc107;'>";
        echo "<strong>⚠️ ADVERTENCIA: Múltiples archivos encontrados</strong><br>";
        echo "Esto puede causar confusión. Verifica cuál se está cargando.";
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>Verificar cuál se está cargando</h2>";

$test_file = $base_dir . '/includes/PDFExtractor.php';

if (file_exists($test_file)) {
    echo "<p><strong>Archivo esperado:</strong> $test_file</p>";
    echo "<p>Tamaño: " . number_format(filesize($test_file)) . " bytes</p>";
    echo "<p>MD5: <code>" . md5_file($test_file) . "</code></p>";
    
    // Intentar cargar y ver qué pasa
    echo "<br><strong>Intentando cargar...</strong><br>";
    
    try {
        require_once $test_file;
        
        if (class_exists('PDFExtractor')) {
            $reflection = new ReflectionClass('PDFExtractor');
            $methods = $reflection->getMethods();
            
            echo "<p style='color:green;'>✅ Clase cargada con " . count($methods) . " métodos:</p>";
            echo "<ul>";
            foreach ($methods as $method) {
                echo "<li>" . $method->getName() . "</li>";
            }
            echo "</ul>";
            
            // Verificar de dónde se cargó
            echo "<p><strong>Archivo fuente de la clase:</strong><br>";
            echo "<code>" . $reflection->getFileName() . "</code></p>";
            
        } else {
            echo "<p style='color:red;'>❌ Clase no existe después de cargar</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

?>
