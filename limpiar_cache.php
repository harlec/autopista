<?php
/**
 * LIMPIAR CACHE DE PHP
 * OPcache, APC, y otros sistemas de cache
 */

echo "<h1>Limpieza de Cache PHP</h1>";

echo "<h2>1. OPcache</h2>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color:green;'>✅ OPcache limpiado exitosamente</p>";
    } else {
        echo "<p style='color:red;'>❌ No se pudo limpiar OPcache</p>";
    }
} else {
    echo "<p style='color:gray;'>⚪ OPcache no está habilitado</p>";
}

echo "<h2>2. APCu</h2>";
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        echo "<p style='color:green;'>✅ APCu limpiado exitosamente</p>";
    } else {
        echo "<p style='color:red;'>❌ No se pudo limpiar APCu</p>";
    }
} else {
    echo "<p style='color:gray;'>⚪ APCu no está habilitado</p>";
}

echo "<h2>3. Realpath Cache</h2>";
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    echo "<p style='color:green;'>✅ Realpath cache limpiado</p>";
}

echo "<hr>";
echo "<h2>✅ Limpieza Completada</h2>";
echo "<p><strong>Ahora ejecuta el test de nuevo:</strong></p>";
echo "<a href='test_detallado.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px; display:inline-block;'>🧪 Ir al Test Detallado</a>";
?>
