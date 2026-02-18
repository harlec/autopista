<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

$page_title = 'Lista de Facturas - Sistema de Facturas';
$current_page = 'facturas';

$database = new Database();
$db = $database->getConnection();

// Filtros
$estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
$proveedor_filter = isset($_GET['proveedor']) ? intval($_GET['proveedor']) : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construcción de la consulta
$where_conditions = [];
$params = [];

if (!empty($estado_filter)) {
    $where_conditions[] = "f.estado = :estado";
    $params[':estado'] = $estado_filter;
}

if ($proveedor_filter > 0) {
    $where_conditions[] = "f.proveedor_id = :proveedor_id";
    $params[':proveedor_id'] = $proveedor_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(f.numero_factura LIKE :search OR p.nombre LIKE :search OR f.descripcion LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($fecha_desde)) {
    $where_conditions[] = "f.fecha_emision >= :fecha_desde";
    $params[':fecha_desde'] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "f.fecha_emision <= :fecha_hasta";
    $params[':fecha_hasta'] = $fecha_hasta;
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Actualizar facturas vencidas
$update_query = "UPDATE facturas SET estado = 'vencida' 
                 WHERE estado = 'pendiente' AND fecha_vencimiento < CURDATE()";
$db->exec($update_query);

// Consulta principal
$query = "SELECT f.*, p.nombre as proveedor_nombre, p.ruc, c.nombre as categoria_nombre, c.color as categoria_color
          FROM facturas f
          LEFT JOIN proveedores p ON f.proveedor_id = p.id
          LEFT JOIN categorias_servicios c ON f.categoria_id = c.id
          $where_clause
          ORDER BY f.fecha_vencimiento ASC, f.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener proveedores para el filtro
$query_proveedores = "SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre";
$stmt_proveedores = $db->prepare($query_proveedores);
$stmt_proveedores->execute();
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas de la vista actual
$total_facturas = count($facturas);
$monto_total = array_sum(array_column($facturas, 'monto_total'));
$pendientes = count(array_filter($facturas, fn($f) => $f['estado'] == 'pendiente'));
$vencidas = count(array_filter($facturas, fn($f) => $f['estado'] == 'vencida'));

include '../../includes/header.php';
?>

<!-- Header de la página -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Gestión de Facturas</h1>
            <p class="text-gray-600">Administra y controla todas las facturas de servicios</p>
        </div>
        <a href="nueva.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>
            Nueva Factura
        </a>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Facturas</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_facturas; ?></p>
            </div>
            <i class="fas fa-file-invoice text-3xl text-gray-400"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Monto Total</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo formatCurrency($monto_total); ?></p>
            </div>
            <i class="fas fa-money-bill-wave text-3xl text-primary"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Pendientes</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $pendientes; ?></p>
            </div>
            <i class="fas fa-clock text-3xl text-yellow-400"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Vencidas</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $vencidas; ?></p>
            </div>
            <i class="fas fa-exclamation-triangle text-3xl text-red-400"></i>
        </div>
    </div>
</div>

<!-- Panel de filtros -->
<div class="card mb-6">
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-1"></i>
                    Buscar
                </label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Número de factura, proveedor..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-filter mr-1"></i>
                    Estado
                </label>
                <select name="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estado_filter == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="pagada" <?php echo $estado_filter == 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                    <option value="vencida" <?php echo $estado_filter == 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                    <option value="anulada" <?php echo $estado_filter == 'anulada' ? 'selected' : ''; ?>>Anulada</option>
                </select>
            </div>
            
            <!-- Proveedor -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-building mr-1"></i>
                    Proveedor
                </label>
                <select name="proveedor" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="0">Todos</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <option value="<?php echo $proveedor['id']; ?>" <?php echo $proveedor_filter == $proveedor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($proveedor['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Botones -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
                <a href="lista.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </div>
        
        <!-- Filtros de fecha (expandible) -->
        <div class="pt-4 border-t border-gray-200">
            <details>
                <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-primary">
                    <i class="fas fa-calendar mr-2"></i>Filtros de fecha
                </summary>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                        <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                        <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </details>
        </div>
    </form>
</div>

<!-- Tabla de facturas -->
<div class="card">
    <?php if (count($facturas) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Factura
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Proveedor
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categoría
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Emisión
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vencimiento
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Monto
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($facturas as $factura): 
                        $dias_vencimiento = getDaysDifference($factura['fecha_vencimiento']);
                        $urgente = $factura['estado'] == 'pendiente' && $dias_vencimiento <= 3;
                    ?>
                        <tr class="hover:bg-gray-50 <?php echo $urgente ? 'bg-red-50' : ''; ?>">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($factura['archivo_pdf']): ?>
                                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($factura['numero_factura']); ?>
                                        </div>
                                        <?php if ($factura['descripcion']): ?>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">
                                                <?php echo htmlspecialchars($factura['descripcion']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($factura['proveedor_nombre']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    RUC: <?php echo htmlspecialchars($factura['ruc']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($factura['categoria_nombre']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background-color: <?php echo htmlspecialchars($factura['categoria_color']); ?>20; 
                                                 color: <?php echo htmlspecialchars($factura['categoria_color']); ?>;">
                                        <?php echo htmlspecialchars($factura['categoria_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatDate($factura['fecha_emision']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo formatDate($factura['fecha_vencimiento']); ?>
                                </div>
                                <?php if ($factura['estado'] == 'pendiente' && $dias_vencimiento >= 0): ?>
                                    <div class="text-xs <?php echo $urgente ? 'text-red-600 font-semibold' : 'text-gray-500'; ?>">
                                        <?php 
                                        if ($dias_vencimiento == 0) {
                                            echo 'Vence hoy';
                                        } else {
                                            echo 'En ' . $dias_vencimiento . ' días';
                                        }
                                        ?>
                                    </div>
                                <?php elseif ($factura['estado'] == 'vencida'): ?>
                                    <div class="text-xs text-red-600 font-semibold">
                                        Vencida hace <?php echo abs($dias_vencimiento); ?> días
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo formatCurrency($factura['monto_total'], $factura['moneda']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <?php echo getEstadoBadge($factura['estado']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <?php if ($factura['archivo_pdf']): ?>
                                        <a href="../../assets/uploads/facturas/<?php echo htmlspecialchars($factura['archivo_pdf']); ?>" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-900" title="Ver PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Ver (detalle) -->
                                    <a href="ver.php?id=<?php echo $factura['id']; ?>" class="text-gray-600 hover:text-gray-900" title="Ver factura">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="editar.php?id=<?php echo $factura['id']; ?>" 
                                       class="text-primary hover:text-primary-dark" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($factura['estado'] == 'pendiente'): ?>
                                        <button onclick="marcarPagada(<?php echo $factura['id']; ?>)"
                                                class="text-green-600 hover:text-green-900" title="Marcar como pagada">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="eliminarFactura(<?php echo $factura['id']; ?>)"
                                            class="text-red-600 hover:text-red-900" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-600 mb-2">No se encontraron facturas</h3>
            <p class="text-gray-500 mb-6">
                <?php if (!empty($search) || !empty($estado_filter) || $proveedor_filter > 0): ?>
                    Intenta ajustar los filtros de búsqueda
                <?php else: ?>
                    Comienza registrando tu primera factura
                <?php endif; ?>
            </p>
            <?php if (empty($search) && empty($estado_filter) && $proveedor_filter == 0): ?>
                <a href="nueva.php" class="btn-primary inline-block">
                    <i class="fas fa-plus mr-2"></i>
                    Registrar primera factura
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function marcarPagada(id) {
    if (confirm('¿Marcar esta factura como pagada?')) {
        const fecha_pago = prompt('Fecha de pago (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
        if (!fecha_pago) return;
        
        fetch('actualizar_estado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            },
            body: JSON.stringify({
                id: id,
                estado: 'pagada',
                fecha_pago: fecha_pago
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Factura marcada como pagada', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error al actualizar', 'error');
            }
        });
    }
}

function eliminarFactura(id) {
    if (confirm('¿Estás seguro de eliminar esta factura? Esta acción no se puede deshacer.')) {
        fetch('eliminar_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Factura eliminada correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error al eliminar', 'error');
            }
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
