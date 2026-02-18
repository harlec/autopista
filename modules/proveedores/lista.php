<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

$page_title = 'Proveedores - Sistema de Facturas';
$current_page = 'proveedores';

$database = new Database();
$db = $database->getConnection();

// Obtener proveedores con estadísticas
$query = "SELECT p.*, 
          COUNT(f.id) as total_facturas,
          SUM(CASE WHEN f.estado = 'pendiente' THEN 1 ELSE 0 END) as facturas_pendientes,
          SUM(CASE WHEN f.estado = 'pendiente' THEN f.monto_total ELSE 0 END) as monto_pendiente
          FROM proveedores p
          LEFT JOIN facturas f ON p.id = f.proveedor_id
          GROUP BY p.id
          ORDER BY p.nombre";

$stmt = $db->prepare($query);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!-- Header de la página -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Proveedores</h1>
            <p class="text-gray-600">Gestiona los proveedores de servicios</p>
        </div>
        <button onclick="abrirModalProveedor()" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>
            Nuevo Proveedor
        </button>
    </div>
</div>

<!-- Grid de proveedores -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($proveedores as $proveedor): ?>
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-building text-white text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg">
                            <?php echo htmlspecialchars($proveedor['nombre']); ?>
                        </h3>
                        <p class="text-sm text-gray-500">RUC: <?php echo htmlspecialchars($proveedor['ruc']); ?></p>
                    </div>
                </div>
                <span class="<?php echo $proveedor['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> 
                             px-2 py-1 rounded text-xs font-semibold">
                    <?php echo $proveedor['activo'] ? 'Activo' : 'Inactivo'; ?>
                </span>
            </div>
            
            <?php if ($proveedor['direccion']): ?>
                <div class="mb-3">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?php echo htmlspecialchars($proveedor['direccion']); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($proveedor['telefono'] || $proveedor['email']): ?>
                <div class="mb-4 space-y-1">
                    <?php if ($proveedor['telefono']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-phone mr-2"></i>
                            <?php echo htmlspecialchars($proveedor['telefono']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($proveedor['email']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-envelope mr-2"></i>
                            <?php echo htmlspecialchars($proveedor['email']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-500">Total Facturas</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $proveedor['total_facturas']; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Pendientes</p>
                        <p class="text-xl font-bold text-yellow-600"><?php echo $proveedor['facturas_pendientes']; ?></p>
                    </div>
                </div>
                
                <?php if ($proveedor['monto_pendiente'] > 0): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <p class="text-xs text-yellow-800 mb-1">Monto Pendiente</p>
                        <p class="text-lg font-bold text-yellow-900">
                            <?php echo formatCurrency($proveedor['monto_pendiente']); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between">
                    <a href="../facturas/lista.php?proveedor=<?php echo $proveedor['id']; ?>" 
                       class="text-sm text-primary hover:text-primary-dark">
                        Ver facturas <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                    <div class="flex space-x-2">
                        <button onclick="editarProveedor(<?php echo $proveedor['id']; ?>)" 
                                class="text-primary hover:text-primary-dark">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="eliminarProveedor(<?php echo $proveedor['id']; ?>)" 
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($proveedores) == 0): ?>
        <div class="col-span-full text-center py-12">
            <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-600 mb-2">No hay proveedores registrados</h3>
            <p class="text-gray-500 mb-6">Comienza agregando tu primer proveedor</p>
            <button onclick="abrirModalProveedor()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Agregar Proveedor
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para agregar/editar proveedor -->
<div id="modal-proveedor" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800" id="modal-title">Nuevo Proveedor</h2>
                <button onclick="cerrarModalProveedor()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="form-proveedor" class="p-6">
                        <?php if (function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"><?php endif; ?>
            <input type="hidden" id="proveedor_id" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        RUC <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ruc" id="ruc" required maxlength="11" pattern="[0-9]{11}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email" name="email" id="email"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Dirección
                    </label>
                    <textarea name="direccion" id="direccion" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Persona de Contacto
                    </label>
                    <input type="text" name="contacto" id="contacto"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="activo" id="activo" checked class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Proveedor activo</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="cerrarModalProveedor()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalProveedor() {
    document.getElementById('modal-title').textContent = 'Nuevo Proveedor';
    document.getElementById('form-proveedor').reset();
    document.getElementById('proveedor_id').value = '';
    document.getElementById('modal-proveedor').classList.remove('hidden');
    document.getElementById('modal-proveedor').classList.add('flex');
}

function cerrarModalProveedor() {
    document.getElementById('modal-proveedor').classList.add('hidden');
    document.getElementById('modal-proveedor').classList.remove('flex');
}

function editarProveedor(id) {
    fetch('obtener_proveedor.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const p = data.proveedor;
                document.getElementById('modal-title').textContent = 'Editar Proveedor';
                document.getElementById('proveedor_id').value = p.id;
                document.getElementById('nombre').value = p.nombre;
                document.getElementById('ruc').value = p.ruc;
                document.getElementById('telefono').value = p.telefono || '';
                document.getElementById('email').value = p.email || '';
                document.getElementById('direccion').value = p.direccion || '';
                document.getElementById('contacto').value = p.contacto || '';
                document.getElementById('activo').checked = p.activo == 1;
                
                document.getElementById('modal-proveedor').classList.remove('hidden');
                document.getElementById('modal-proveedor').classList.add('flex');
            }
        });
}

function eliminarProveedor(id) {
    if (confirm('¿Estás seguro de eliminar este proveedor? Se eliminarán todas sus facturas asociadas.')) {
        fetch('eliminar_proveedor.php', {
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
                showToast('Proveedor eliminado correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error al eliminar', 'error');
            }
        });
    }
}

document.getElementById('form-proveedor').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('guardar_proveedor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Proveedor guardado correctamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Error al guardar', 'error');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
