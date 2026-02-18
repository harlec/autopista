<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

$page_title = 'Editar Factura - Sistema de Facturas';
$current_page = 'nueva-factura';

$database = new Database();
$db = $database->getConnection();

// Obtener proveedores y categorías (mismo comportamiento que nueva.php)
$query = "SELECT id, nombre, ruc FROM proveedores WHERE activo = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT id, nombre, color FROM categorias_servicios WHERE activo = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar datos de la factura
if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header('Location: lista.php');
    exit;
}

$id = intval($_GET['id']);
$query = "SELECT * FROM facturas WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    header('Location: lista.php');
    exit;
}

include '../../includes/header.php';
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Editar Factura</h1>
            <p class="text-gray-600">Modifica los datos y guarda los cambios</p>
        </div>
        <a href="lista.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a la lista
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="card sticky top-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                Archivo PDF
            </h2>
            <?php if ($factura['archivo_pdf']): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-file-pdf text-3xl text-red-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($factura['archivo_pdf']); ?></p>
                            <p class="text-sm text-gray-600">Tamaño: --</p>
                        </div>
                        <a href="../../assets/uploads/facturas/<?php echo htmlspecialchars($factura['archivo_pdf']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 ml-2">Abrir PDF</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-sm text-gray-500">No hay PDF asociado</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:col-span-2">
        <form id="factura-form" class="space-y-6">
            <?php if (function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"><?php endif; ?>
            <input type="hidden" name="id" value="<?php echo $factura['id']; ?>">

            <!-- Información del Proveedor -->
            <div class="card">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-building text-info mr-2"></i>
                    Información del Proveedor
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor <span class="text-red-500">*</span></label>
                        <select name="proveedor_id" id="proveedor_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Seleccione un proveedor</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['id']; ?>" <?php echo $factura['proveedor_id'] == $proveedor['id'] ? 'selected' : ''; ?> ><?php echo htmlspecialchars($proveedor['nombre'] . ' - ' . $proveedor['ruc']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select name="categoria_id" id="categoria_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo $factura['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Datos de la Factura -->
            <div class="card">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-file-invoice text-primary mr-2"></i>
                    Datos de la Factura
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número de Factura <span class="text-red-500">*</span></label>
                        <input type="text" name="numero_factura" id="numero_factura" required value="<?php echo htmlspecialchars($factura['numero_factura']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_emision" id="fecha_emision" required value="<?php echo htmlspecialchars($factura['fecha_emision']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vencimiento <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required value="<?php echo htmlspecialchars($factura['fecha_vencimiento']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Moneda <span class="text-red-500">*</span></label>
                        <select name="moneda" id="moneda" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="PEN" <?php echo $factura['moneda'] == 'PEN' ? 'selected' : ''; ?>>Soles (S/)</option>
                            <option value="USD" <?php echo $factura['moneda'] == 'USD' ? 'selected' : ''; ?>>Dólares ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtotal</label>
                        <input type="number" name="subtotal" id="subtotal" step="0.01" min="0" value="<?php echo htmlspecialchars($factura['subtotal']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">IGV (18%)</label>
                        <input type="number" name="igv" id="igv" step="0.01" min="0" value="<?php echo htmlspecialchars($factura['igv']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto Total <span class="text-red-500">*</span></label>
                        <input type="number" name="monto_total" id="monto_total" required step="0.01" min="0" value="<?php echo htmlspecialchars($factura['monto_total']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-lg font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"><?php echo htmlspecialchars($factura['descripcion']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <a href="lista.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                    <div class="space-x-3">
                        <button type="submit" name="estado" value="pendiente" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Actualizar Factura
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// Reusar JS de nueva.php: submit por fetch ya soporta envío de 'id' para actualizar
document.getElementById('factura-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('guardar_factura.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => { window.location.href = 'lista.php'; }, 900);
        } else {
            showToast(data.message || 'Error al guardar la factura', 'error');
        }
    })
    .catch(error => { console.error(error); showToast('Error al guardar la factura', 'error'); });
});

// Mantener comportamiento de cálculo del total
document.getElementById('subtotal').addEventListener('input', function() {
    const subtotal = parseFloat(this.value) || 0;
    const igv = subtotal * 0.18;
    document.getElementById('igv').value = igv.toFixed(2);
    document.getElementById('monto_total').value = (subtotal + igv).toFixed(2);
});

</script>

<?php include '../../includes/footer.php'; ?>