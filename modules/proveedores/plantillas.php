<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = 'Plantillas de Extracción - Sistema de Facturas';
$current_page = 'proveedores';

$database = new Database();
$db = $database->getConnection();

// Obtener proveedores con sus plantillas
$query = "SELECT p.*, 
          COUNT(pt.id) as total_plantillas,
          GROUP_CONCAT(pt.nombre_plantilla SEPARATOR ', ') as plantillas
          FROM proveedores p
          LEFT JOIN proveedor_plantillas pt ON p.id = pt.proveedor_id AND pt.activo = 1
          WHERE p.activo = 1
          GROUP BY p.id
          ORDER BY p.tiene_plantilla DESC, p.nombre";

$stmt = $db->prepare($query);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!-- Header de la página -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Plantillas de Extracción de PDFs</h1>
            <p class="text-gray-600">Configura patrones personalizados para extraer datos de facturas de cada proveedor</p>
        </div>
        <a href="lista.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Proveedores
        </a>
    </div>
</div>

<!-- Info Box -->
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">¿Cómo funcionan las plantillas?</h3>
            <p class="mt-2 text-sm text-blue-700">
                Las plantillas utilizan expresiones regulares (regex) para encontrar y extraer información específica de los PDFs.
                Puedes crear múltiples plantillas por proveedor y probarlas con PDFs de ejemplo.
            </p>
        </div>
    </div>
</div>

<!-- Lista de proveedores -->
<div class="grid grid-cols-1 gap-4">
    <?php foreach ($proveedores as $proveedor): ?>
        <div class="card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <h3 class="text-lg font-bold text-gray-800 mr-3">
                            <?php echo htmlspecialchars($proveedor['nombre']); ?>
                        </h3>
                        <?php if ($proveedor['tiene_plantilla']): ?>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                <i class="fas fa-check-circle mr-1"></i>
                                Configurado
                            </span>
                        <?php else: ?>
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Sin plantilla
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-sm text-gray-600">RUC: <?php echo htmlspecialchars($proveedor['ruc']); ?></p>
                    
                    <?php if ($proveedor['total_plantillas'] > 0): ?>
                        <div class="mt-3">
                            <p class="text-sm font-medium text-gray-700">Plantillas activas:</p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($proveedor['plantillas']); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Este proveedor usará extracción genérica
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-2 ml-4">
                    <button onclick="verPlantillas(<?php echo $proveedor['id']; ?>, '<?php echo htmlspecialchars($proveedor['nombre']); ?>')" 
                            class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-cog mr-2"></i>
                        <?php echo $proveedor['total_plantillas'] > 0 ? 'Editar' : 'Configurar'; ?>
                    </button>
                    
                    <button onclick="probarExtraccion(<?php echo $proveedor['id']; ?>)" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
                            title="Probar con un PDF">
                        <i class="fas fa-flask"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal de Plantillas -->
<div id="modal-plantillas" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full m-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800" id="modal-plantillas-title">Configurar Plantillas</h2>
                <button onclick="cerrarModalPlantillas()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div id="plantillas-container">
                <!-- Se cargará dinámicamente -->
            </div>
            
            <button onclick="agregarPlantilla()" class="btn-primary mt-4">
                <i class="fas fa-plus mr-2"></i>
                Agregar Nueva Plantilla
            </button>
        </div>
    </div>
</div>

<!-- Modal de Prueba de Extracción -->
<div id="modal-prueba" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full m-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">Probar Extracción</h2>
                <button onclick="cerrarModalPrueba()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <p class="text-gray-600 mb-4">Sube un PDF de ejemplo para probar la extracción con las plantillas configuradas:</p>
            
            <div id="dropzone-prueba" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors mb-4">
                <i class="fas fa-file-pdf text-5xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 mb-2">Arrastra un PDF aquí o haz clic para seleccionar</p>
                <input type="file" id="pdf-prueba-input" accept=".pdf" class="hidden">
            </div>
            
            <div id="resultado-prueba" class="hidden">
                <h3 class="font-bold text-gray-800 mb-3">Resultados de la extracción:</h3>
                <div id="resultado-contenido" class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <!-- Resultados aquí -->
                </div>
            </div>
            
            <input type="hidden" id="proveedor-prueba-id">
        </div>
    </div>
</div>

<script>
let currentProveedorId = null;

function verPlantillas(proveedorId, proveedorNombre) {
    currentProveedorId = proveedorId;
    document.getElementById('modal-plantillas-title').textContent = 'Plantillas de ' + proveedorNombre;
    
    // Cargar plantillas existentes
    fetch('obtener_plantillas.php?proveedor_id=' + proveedorId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarPlantillas(data.plantillas);
                document.getElementById('modal-plantillas').classList.remove('hidden');
                document.getElementById('modal-plantillas').classList.add('flex');
            }
        });
}

function mostrarPlantillas(plantillas) {
    const container = document.getElementById('plantillas-container');
    
    if (plantillas.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                <p>No hay plantillas configuradas para este proveedor</p>
                <p class="text-sm mt-2">Haz clic en "Agregar Nueva Plantilla" para crear una</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = plantillas.map((plantilla, index) => `
        <div class="border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-800">${plantilla.nombre_plantilla}</h3>
                <div class="flex items-center space-x-2">
                    <span class="text-xs ${plantilla.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'} px-2 py-1 rounded">
                        ${plantilla.activo ? 'Activa' : 'Inactiva'}
                    </span>
                    <button onclick="editarPlantilla(${plantilla.id})" class="text-primary hover:text-primary-dark">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="eliminarPlantilla(${plantilla.id})" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="font-medium text-gray-700">Prioridad:</span>
                    <span class="text-gray-600">${plantilla.prioridad}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Fecha creación:</span>
                    <span class="text-gray-600">${new Date(plantilla.created_at).toLocaleDateString()}</span>
                </div>
            </div>
            
            ${plantilla.patron_numero_factura ? `<div class="mt-2 text-xs"><span class="font-medium">Patrón Nº Factura:</span> <code class="bg-gray-100 px-1">${plantilla.patron_numero_factura}</code></div>` : ''}
            ${plantilla.patron_total ? `<div class="mt-1 text-xs"><span class="font-medium">Patrón Total:</span> <code class="bg-gray-100 px-1">${plantilla.patron_total}</code></div>` : ''}
        </div>
    `).join('');
}

function agregarPlantilla() {
    // Abrir formulario de nueva plantilla
    window.location.href = 'editar_plantilla.php?proveedor_id=' + currentProveedorId;
}

function editarPlantilla(plantillaId) {
    window.location.href = 'editar_plantilla.php?id=' + plantillaId;
}

function eliminarPlantilla(plantillaId) {
    if (confirm('¿Estás seguro de eliminar esta plantilla?')) {
        fetch('eliminar_plantilla.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: plantillaId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Plantilla eliminada', 'success');
                verPlantillas(currentProveedorId, '');
            }
        });
    }
}

function cerrarModalPlantillas() {
    document.getElementById('modal-plantillas').classList.add('hidden');
    document.getElementById('modal-plantillas').classList.remove('flex');
    location.reload();
}

function probarExtraccion(proveedorId) {
    document.getElementById('proveedor-prueba-id').value = proveedorId;
    document.getElementById('resultado-prueba').classList.add('hidden');
    document.getElementById('modal-prueba').classList.remove('hidden');
    document.getElementById('modal-prueba').classList.add('flex');
}

function cerrarModalPrueba() {
    document.getElementById('modal-prueba').classList.add('hidden');
    document.getElementById('modal-prueba').classList.remove('flex');
}

// Configurar dropzone de prueba
const dropzonePrueba = document.getElementById('dropzone-prueba');
const filePruebaInput = document.getElementById('pdf-prueba-input');

dropzonePrueba.addEventListener('click', () => filePruebaInput.click());

filePruebaInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        procesarPDFPrueba(e.target.files[0]);
    }
});

dropzonePrueba.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzonePrueba.classList.add('border-primary', 'bg-green-50');
});

dropzonePrueba.addEventListener('dragleave', () => {
    dropzonePrueba.classList.remove('border-primary', 'bg-green-50');
});

dropzonePrueba.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzonePrueba.classList.remove('border-primary', 'bg-green-50');
    if (e.dataTransfer.files.length > 0) {
        procesarPDFPrueba(e.dataTransfer.files[0]);
    }
});

function procesarPDFPrueba(file) {
    const formData = new FormData();
    formData.append('pdf_file', file);
    formData.append('proveedor_id', document.getElementById('proveedor-prueba-id').value);
    
    showToast('Procesando PDF...', 'info');
    
    fetch('../facturas/procesar_pdf.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        mostrarResultadoPrueba(data);
    })
    .catch(error => {
        showToast('Error al procesar PDF', 'error');
    });
}

function mostrarResultadoPrueba(data) {
    const container = document.getElementById('resultado-contenido');
    const resultadoDiv = document.getElementById('resultado-prueba');
    
    let html = '';
    
    if (data.success && data.data) {
        const d = data.data;
        html = `
            <div class="space-y-2">
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">Método usado:</span>
                    <span class="text-gray-600">${data.debug?.metodo || 'N/A'}</span>
                </div>
                ${d.numero_factura ? `
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">Número de Factura:</span>
                    <span class="text-green-600 font-semibold">${d.numero_factura}</span>
                </div>` : ''}
                ${d.fecha_emision ? `
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">Fecha de Emisión:</span>
                    <span class="text-green-600 font-semibold">${d.fecha_emision}</span>
                </div>` : ''}
                ${d.monto_total ? `
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">Monto Total:</span>
                    <span class="text-green-600 font-semibold">S/ ${d.monto_total}</span>
                </div>` : ''}
                ${d.subtotal ? `
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">Subtotal:</span>
                    <span class="text-gray-600">S/ ${d.subtotal}</span>
                </div>` : ''}
                ${d.igv ? `
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="font-medium">IGV:</span>
                    <span class="text-gray-600">S/ ${d.igv}</span>
                </div>` : ''}
            </div>
            
            ${data.debug?.plantilla_usada ? `
            <div class="mt-4 p-3 bg-blue-50 rounded">
                <span class="text-sm font-medium text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Plantilla utilizada: ${data.debug.plantilla_usada}
                </span>
            </div>` : ''}
        `;
        
        showToast('Extracción completada', 'success');
    } else {
        html = `
            <div class="text-center py-4 text-red-600">
                <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                <p>No se pudieron extraer datos del PDF</p>
                <p class="text-sm mt-2">${data.message || 'Error desconocido'}</p>
            </div>
        `;
        showToast('No se pudieron extraer datos', 'warning');
    }
    
    container.innerHTML = html;
    resultadoDiv.classList.remove('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>
