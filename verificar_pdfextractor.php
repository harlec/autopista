<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificación PDFExtractor</h1>";

// Verificar si existe
if (file_exists(__DIR__ . '/includes/PDFExtractor.php')) {
    echo "<p>✅ PDFExtractor.php existe</p>";
    
    require_once __DIR__ . '/includes/PDFExtractor.php';
    
    // Verificar métodos
    if (class_exists('PDFExtractor')) {
        echo "<p>✅ Clase PDFExtractor cargada</p>";
        
        $methods = get_class_methods('PDFExtractor');
        
        echo "<h2>Métodos disponibles:</h2>";
        echo "<ul>";
        foreach ($methods as $method) {
            echo "<li>" . $method . "</li>";
        }
        echo "</ul>";
        
        // Verificar método crítico
        if (in_array('detectCurrency', $methods)) {
            echo "<p style='color:green; font-weight:bold;'>✅ Método detectCurrency existe (versión actualizada)</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ Método detectCurrency NO existe (archivo desactualizado)</p>";
            echo "<p><strong>Solución:</strong> Reemplaza PDFExtractor.php con la versión actualizada</p>";
        }
        
        // Verificar método de Serie+Folio
        $reflection = new ReflectionClass('PDFExtractor');
        $extractMethod = $reflection->getMethod('extractWithPattern');
        
        echo "<h2>Código de extractWithPattern:</h2>";
        $filename = $reflection->getFileName();
        $start = $extractMethod->getStartLine() - 1;
        $end = $extractMethod->getEndLine();
        $length = $end - $start;
        
        $source = file($filename);
        $body = implode("", array_slice($source, $start, $length));
        
        echo "<pre style='background:#2d3748; color:#e2e8f0; padding:15px; border-radius:5px;'>";
        echo htmlspecialchars($body);
        echo "</pre>";
        
        if (strpos($body, 'Serie + Folio') !== false || strpos($body, 'matches[2]') !== false) {
            echo "<p style='color:green; font-weight:bold;'>✅ Método soporta formato mexicano (Serie+Folio)</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ Método NO soporta formato mexicano</p>";
            echo "<p><strong>Solución:</strong> Actualiza PDFExtractor.php con la versión que incluye soporte CFDI</p>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ No se pudo cargar la clase PDFExtractor</p>";
    }
} else {
    echo "<p style='color:red;'>❌ PDFExtractor.php NO existe en /includes/</p>";
    echo "<p>Ruta buscada: " . __DIR__ . '/includes/PDFExtractor.php</p>';
}
?>
