<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = 'Nueva Factura - Sistema de Facturas';
$current_page = 'nueva-factura';

$database = new Database();
$db = $database->getConnection();

// Obtener proveedores
$query = "SELECT id, nombre, ruc FROM proveedores WHERE activo = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías
$query = "SELECT id, nombre, color FROM categorias_servicios WHERE activo = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!-- Header de la página -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Nueva Factura</h1>
            <p class="text-gray-600">Registra una nueva factura cargando el PDF o ingresando los datos manualmente</p>
        </div>
        <a href="lista.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a la lista
        </a>
    </div>
</div>

<!-- Formulario principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Columna izquierda: Carga de PDF -->
    <div class="lg:col-span-1">
        <div class="card sticky top-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                Cargar PDF
            </h2>
            
            <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 mb-2">Arrastra el PDF aquí o haz clic para seleccionar</p>
                <p class="text-sm text-gray-500">Solo archivos PDF (máx. 10MB)</p>
                <input type="file" id="pdf-input" accept=".pdf" class="hidden">
            </div>
            
            <div id="pdf-preview" class="mt-4 hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-file-pdf text-3xl text-red-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800" id="pdf-filename"></p>
                            <p class="text-sm text-gray-600" id="pdf-size"></p>
                        </div>
                        <button onclick="removePdf()" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <button onclick="extractPdfData()" class="btn-primary w-full mt-4" id="extract-btn">
                    <i class="fas fa-magic mr-2"></i>
                    Extraer Datos del PDF
                </button>
            </div>
            
            <div id="extraction-status" class="mt-4 hidden">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium text-green-800">Datos extraídos correctamente</p>
                            <p class="text-sm text-green-600">Verifica los datos en el formulario</p>
                            <p id="extraction-meta" class="text-xs text-gray-500 mt-2">&nbsp;</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Columna derecha: Formulario de datos -->
    <div class="lg:col-span-2">
        <form id="factura-form" class="space-y-6">
            
            <!-- Información del Proveedor -->
            <div class="card">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-building text-info mr-2"></i>
                    Información del Proveedor
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Proveedor <span class="text-red-500">*</span>
                        </label>
                        <select name="proveedor_id" id="proveedor_id" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Seleccione un proveedor</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['id']; ?>">
                                    <?php echo htmlspecialchars($proveedor['nombre'] . ' - ' . $proveedor['ruc']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Categoría
                        </label>
                        <select name="categoria_id" id="categoria_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Factura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="numero_factura" id="numero_factura" required
                               placeholder="Ej: F001-00001234"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha de Emisión <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_emision" id="fecha_emision" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha de Vencimiento <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Moneda <span class="text-red-500">*</span>
                        </label>
                        <select name="moneda" id="moneda" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="PEN">Soles (S/)</option>
                            <option value="USD">Dólares ($)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Subtotal
                        </label>
                        <input type="number" name="subtotal" id="subtotal" step="0.01" min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            IGV (18%)
                        </label>
                        <input type="number" name="igv" id="igv" step="0.01" min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Monto Total <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="monto_total" id="monto_total" required step="0.01" min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-lg font-semibold">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea name="descripcion" id="descripcion" rows="3"
                                  placeholder="Descripción breve del servicio facturado"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <button type="button" onclick="window.location.href='lista.php'" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    
                    <div class="space-x-3">
                        <button type="submit" name="estado" value="pendiente"
                                class="btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Factura
                        </button>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="archivo_pdf" id="archivo_pdf">
        </form>
    </div>
</div>

<script>
let selectedFile = null;

// Configurar dropzone
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('pdf-input');

dropzone.addEventListener('click', () => fileInput.click());

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-primary', 'bg-green-50');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-primary', 'bg-green-50');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-primary', 'bg-green-50');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    if (file.type !== 'application/pdf') {
        showToast('Solo se permiten archivos PDF', 'error');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        showToast('El archivo no debe superar los 10MB', 'error');
        return;
    }
    
    selectedFile = file;
    
    document.getElementById('pdf-filename').textContent = file.name;
    document.getElementById('pdf-size').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    document.getElementById('pdf-preview').classList.remove('hidden');
    dropzone.classList.add('hidden');
}

function removePdf() {
    selectedFile = null;
    fileInput.value = '';
    document.getElementById('pdf-preview').classList.add('hidden');
    document.getElementById('extraction-status').classList.add('hidden');
    dropzone.classList.remove('hidden');
}

function extractPdfData() {
    if (!selectedFile) {
        showToast('No hay archivo seleccionado', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('pdf_file', selectedFile);
    
    const extractBtn = document.getElementById('extract-btn');
    extractBtn.disabled = true;
    extractBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Extrayendo datos...';
    
    fetch('procesar_pdf.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Rellenar campos con datos extraídos
            if (data.data.numero_factura) {
                document.getElementById('numero_factura').value = data.data.numero_factura;
            }
            if (data.data.fecha_emision) {
                document.getElementById('fecha_emision').value = data.data.fecha_emision;
            }
            if (data.data.monto_total) {
                document.getElementById('monto_total').value = data.data.monto_total;
            }
            if (data.data.archivo_pdf) {
                document.getElementById('archivo_pdf').value = data.data.archivo_pdf;
            }

            // Mostrar método y texto reconocido (debug)
            document.getElementById('extraction-meta').textContent = 'Método: ' + (data.method || 'desconocido');
            console.log('PDF extraction method:', data.method);
            console.log('PDF raw_text (truncated):', data.raw_text ? data.raw_text.substring(0,1000) : '');

            document.getElementById('extraction-status').classList.remove('hidden');
            showToast('Datos extraídos correctamente. Verifica la información.', 'success');
        } else {
            showToast(data.message || 'Error al extraer datos del PDF', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar el PDF', 'error');
    })
    .finally(() => {
        extractBtn.disabled = false;
        extractBtn.innerHTML = '<i class="fas fa-magic mr-2"></i>Extraer Datos del PDF';
    });
}

// Calcular automáticamente el total
document.getElementById('subtotal').addEventListener('input', calcularTotal);
document.getElementById('igv').addEventListener('input', calcularTotal);

function calcularTotal() {
    const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    const igv = parseFloat(document.getElementById('igv').value) || 0;
    document.getElementById('monto_total').value = (subtotal + igv).toFixed(2);
}

// Calcular IGV automáticamente al cambiar subtotal
document.getElementById('subtotal').addEventListener('input', function() {
    const subtotal = parseFloat(this.value) || 0;
    const igv = subtotal * 0.18;
    document.getElementById('igv').value = igv.toFixed(2);
});

// Enviar formulario
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
            showToast('Factura guardada correctamente', 'success');
            setTimeout(() => {
                window.location.href = 'lista.php';
            }, 1500);
        } else {
            showToast(data.message || 'Error al guardar la factura', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al guardar la factura', 'error');
    });
});

// Establecer fecha actual como fecha de emisión por defecto
document.getElementById('fecha_emision').valueAsDate = new Date();

// Calcular fecha de vencimiento (30 días después)
document.getElementById('fecha_emision').addEventListener('change', function() {
    const fechaEmision = new Date(this.value);
    fechaEmision.setDate(fechaEmision.getDate() + 30);
    document.getElementById('fecha_vencimiento').valueAsDate = fechaEmision;
});
</script>

<?php include '../../includes/footer.php'; ?>
