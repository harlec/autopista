<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header('Location: lista.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$id = intval($_GET['id']);
$query = "SELECT f.*, p.nombre as proveedor_nombre, p.ruc as proveedor_ruc, c.nombre as categoria_nombre
          FROM facturas f
          LEFT JOIN proveedores p ON f.proveedor_id = p.id
          LEFT JOIN categorias_servicios c ON f.categoria_id = c.id
          WHERE f.id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    header('Location: lista.php');
    exit;
}

$page_title = 'Ver Factura - ' . $factura['numero_factura'];
include '../../includes/header.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Factura: <?php echo htmlspecialchars($factura['numero_factura']); ?></h1>
            <p class="text-sm text-gray-500">Proveedor: <?php echo htmlspecialchars($factura['proveedor_nombre']); ?> — RUC: <?php echo htmlspecialchars($factura['proveedor_ruc']); ?></p>
        </div>
        <div class="space-x-3">
            <a href="editar.php?id=<?php echo $factura['id']; ?>" class="px-4 py-2 bg-yellow-500 text-white rounded">Editar</a>
            <a href="lista.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded">Volver</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card md:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Fecha de Emisión</p>
                <p class="font-medium"><?php echo formatDate($factura['fecha_emision']); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Fecha de Vencimiento</p>
                <p class="font-medium"><?php echo formatDate($factura['fecha_vencimiento']); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Estado</p>
                <p><?php echo getEstadoBadge($factura['estado']); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Categoría</p>
                <p><?php echo htmlspecialchars($factura['categoria_nombre'] ?: '-'); ?></p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs text-gray-500">Descripción</p>
                <p><?php echo nl2br(htmlspecialchars($factura['descripcion'] ?: '-')); ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <p class="text-xs text-gray-500">Monto Total</p>
        <p class="text-2xl font-bold"><?php echo formatCurrency($factura['monto_total'], $factura['moneda']); ?></p>

        <div class="mt-4">
            <?php if ($factura['archivo_pdf']): ?>
                <a href="../../assets/uploads/facturas/<?php echo htmlspecialchars($factura['archivo_pdf']); ?>" target="_blank" class="block bg-blue-50 border border-blue-200 rounded-lg p-3 text-center text-blue-600">Abrir PDF</a>
            <?php else: ?>
                <div class="text-sm text-gray-500">No hay PDF adjunto</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>