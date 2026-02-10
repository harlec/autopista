<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Dashboard - Sistema de Facturas';
$current_page = 'dashboard';

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];

// Total de facturas pendientes
$query = "SELECT COUNT(*) as total, SUM(monto_total) as monto FROM facturas WHERE estado = 'pendiente'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Facturas vencidas
$query = "SELECT COUNT(*) as total, SUM(monto_total) as monto FROM facturas 
          WHERE estado = 'pendiente' AND fecha_vencimiento < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['vencidas'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Facturas pagadas este mes
$query = "SELECT COUNT(*) as total, SUM(monto_total) as monto FROM facturas 
          WHERE estado = 'pagada' AND MONTH(fecha_pago) = MONTH(CURDATE()) 
          AND YEAR(fecha_pago) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pagadas_mes'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Total proveedores activos
$query = "SELECT COUNT(*) as total FROM proveedores WHERE activo = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['proveedores'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Facturas próximas a vencer (próximos 7 días)
$query = "SELECT f.*, p.nombre as proveedor_nombre 
          FROM facturas f
          LEFT JOIN proveedores p ON f.proveedor_id = p.id
          WHERE f.estado = 'pendiente' 
          AND f.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          ORDER BY f.fecha_vencimiento ASC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$proximas_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimas facturas registradas
$query = "SELECT f.*, p.nombre as proveedor_nombre, c.nombre as categoria_nombre
          FROM facturas f
          LEFT JOIN proveedores p ON f.proveedor_id = p.id
          LEFT JOIN categorias_servicios c ON f.categoria_id = c.id
          ORDER BY f.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$ultimas_facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Header de la página -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h1>
    <p class="text-gray-600">Resumen general del sistema de gestión de facturas</p>
</div>

<!-- Tarjetas de estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Facturas Pendientes -->
    <div class="stat-card" style="border-left-color: #FFDD00;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?php echo $stats['pendientes']['total'] ?? 0; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-600 mb-1">Facturas Pendientes</h3>
        <p class="text-xl font-semibold text-gray-800">
            <?php echo formatCurrency($stats['pendientes']['monto'] ?? 0); ?>
        </p>
    </div>
    
    <!-- Facturas Vencidas -->
    <div class="stat-card" style="border-left-color: #EF4444;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?php echo $stats['vencidas']['total'] ?? 0; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-600 mb-1">Facturas Vencidas</h3>
        <p class="text-xl font-semibold text-red-600">
            <?php echo formatCurrency($stats['vencidas']['monto'] ?? 0); ?>
        </p>
    </div>
    
    <!-- Pagadas este mes -->
    <div class="stat-card" style="border-left-color: #72BF44;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?php echo $stats['pagadas_mes']['total'] ?? 0; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-600 mb-1">Pagadas este Mes</h3>
        <p class="text-xl font-semibold text-green-600">
            <?php echo formatCurrency($stats['pagadas_mes']['monto'] ?? 0); ?>
        </p>
    </div>
    
    <!-- Proveedores -->
    <div class="stat-card" style="border-left-color: #00BBE7;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-building text-blue-600 text-xl"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?php echo $stats['proveedores']['total'] ?? 0; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-600 mb-1">Proveedores Activos</h3>
        <a href="/modules/proveedores/lista.php" class="text-sm text-blue-600 hover:text-blue-800">
            Ver todos <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
</div>

<!-- Contenido principal en dos columnas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Facturas próximas a vencer -->
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-accent mr-2"></i>
                Próximas a Vencer
            </h2>
            <span class="text-sm text-gray-500">Próximos 7 días</span>
        </div>
        
        <?php if (count($proximas_vencer) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($proximas_vencer as $factura): 
                    $dias = getDaysDifference($factura['fecha_vencimiento']);
                    $urgencia = $dias <= 2 ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50';
                ?>
                    <div class="border-l-4 <?php echo $urgencia; ?> p-4 rounded-r-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($factura['proveedor_nombre']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($factura['numero_factura']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800"><?php echo formatCurrency($factura['monto_total']); ?></p>
                                <p class="text-xs text-gray-600">
                                    <?php if ($dias == 0): ?>
                                        <span class="text-red-600 font-semibold">Hoy</span>
                                    <?php elseif ($dias == 1): ?>
                                        <span class="text-orange-600 font-semibold">Mañana</span>
                                    <?php else: ?>
                                        En <?php echo $dias; ?> días
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-check-circle text-5xl mb-3 text-green-400"></i>
                <p>No hay facturas próximas a vencer</p>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 pt-4 border-t">
            <a href="/modules/facturas/lista.php?filter=proximas" 
               class="text-primary hover:text-primary-dark font-medium text-sm flex items-center justify-center">
                Ver todas las facturas pendientes
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
    
    <!-- Últimas facturas registradas -->
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-file-invoice text-primary mr-2"></i>
                Últimas Facturas
            </h2>
            <a href="/modules/facturas/nueva.php" class="btn-primary text-sm">
                <i class="fas fa-plus mr-2"></i>Nueva
            </a>
        </div>
        
        <?php if (count($ultimas_facturas) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($ultimas_facturas as $factura): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($factura['proveedor_nombre']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($factura['numero_factura']); ?></p>
                                <?php if ($factura['categoria_nombre']): ?>
                                    <span class="inline-block text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded mt-1">
                                        <?php echo htmlspecialchars($factura['categoria_nombre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right ml-4">
                                <?php echo getEstadoBadge($factura['estado']); ?>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Vence: <?php echo formatDate($factura['fecha_vencimiento']); ?></span>
                            <span class="font-bold text-gray-800"><?php echo formatCurrency($factura['monto_total']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                <p>No hay facturas registradas</p>
                <a href="/modules/facturas/nueva.php" class="btn-primary mt-4 inline-block">
                    Registrar primera factura
                </a>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 pt-4 border-t">
            <a href="/modules/facturas/lista.php" 
               class="text-primary hover:text-primary-dark font-medium text-sm flex items-center justify-center">
                Ver todas las facturas
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
