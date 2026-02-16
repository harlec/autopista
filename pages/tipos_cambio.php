<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar sesión
checkLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Tipos de Cambio";
$active_page = "configuracion";

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'agregar') {
            $fecha = $_POST['fecha'];
            $moneda = $_POST['moneda'];
            $tipo_cambio = floatval($_POST['tipo_cambio']);
            $fuente = $_POST['fuente'] ?? 'Manual';
            
            $query = "INSERT INTO tipos_cambio (fecha, moneda_origen, moneda_destino, tipo_cambio, fuente) 
                      VALUES (:fecha, :moneda, 'PEN', :tipo_cambio, :fuente)
                      ON DUPLICATE KEY UPDATE 
                      tipo_cambio = VALUES(tipo_cambio),
                      fuente = VALUES(fuente)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':moneda', $moneda);
            $stmt->bindParam(':tipo_cambio', $tipo_cambio);
            $stmt->bindParam(':fuente', $fuente);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Tipo de cambio registrado correctamente";
            } else {
                $_SESSION['error_message'] = "Error al registrar tipo de cambio";
            }
            
            header('Location: tipos_cambio.php');
            exit;
        }
    }
}

// Obtener tipos de cambio recientes
$query = "SELECT * FROM tipos_cambio ORDER BY fecha DESC, moneda_origen LIMIT 50";
$stmt = $db->query($query);
$tipos_cambio = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tipos de cambio actuales (último registro de cada moneda)
$query_actuales = "SELECT tc1.* 
                   FROM tipos_cambio tc1
                   INNER JOIN (
                       SELECT moneda_origen, MAX(fecha) as max_fecha
                       FROM tipos_cambio
                       GROUP BY moneda_origen
                   ) tc2 ON tc1.moneda_origen = tc2.moneda_origen 
                        AND tc1.fecha = tc2.max_fecha
                   ORDER BY tc1.moneda_origen";
$stmt_actuales = $db->query($query_actuales);
$tipos_actuales = $stmt_actuales->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">💱 Tipos de Cambio</h1>
            <p class="text-muted">Gestiona los tipos de cambio para conversión automática</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tipos de Cambio Actuales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">📊 Tipos de Cambio Vigentes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($tipos_actuales as $tc): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">
                                        <span class="badge bg-primary fs-5"><?php echo $tc['moneda_origen']; ?></span>
                                    </h3>
                                    <div class="display-6 text-primary my-3">
                                        S/ <?php echo number_format($tc['tipo_cambio'], 4); ?>
                                    </div>
                                    <small class="text-muted">
                                        1 <?php echo $tc['moneda_origen']; ?> = S/ <?php echo number_format($tc['tipo_cambio'], 4); ?> PEN
                                    </small>
                                    <div class="mt-2">
                                        <small class="badge bg-secondary">
                                            <?php echo date('d/m/Y', strtotime($tc['fecha'])); ?>
                                        </small>
                                        <small class="badge bg-info">
                                            <?php echo $tc['fuente']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($tipos_actuales)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                ⚠️ No hay tipos de cambio registrados. Registra al menos USD y MXN.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario de Registro -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">➕ Registrar Tipo de Cambio</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="tipos_cambio.php">
                        <input type="hidden" name="action" value="agregar">
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Moneda</label>
                            <select name="moneda" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <option value="USD">USD - Dólar Americano</option>
                                <option value="MXN">MXN - Peso Mexicano</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="COP">COP - Peso Colombiano</option>
                                <option value="CLP">CLP - Peso Chileno</option>
                                <option value="BRL">BRL - Real Brasileño</option>
                            </select>
                            <small class="text-muted">¿Cuántos soles vale 1 unidad de esta moneda?</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Cambio (PEN)</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" name="tipo_cambio" class="form-control" 
                                       step="0.0001" min="0" placeholder="3.7500" required>
                            </div>
                            <small class="text-muted">Ejemplo: 1 USD = 3.75 PEN</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fuente</label>
                            <select name="fuente" class="form-select">
                                <option value="Manual">Manual</option>
                                <option value="SUNAT">SUNAT</option>
                                <option value="SBS">SBS</option>
                                <option value="Banco Central">Banco Central</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            💾 Guardar Tipo de Cambio
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="text-primary">ℹ️ Información</h6>
                    <ul class="small mb-0">
                        <li>Los tipos de cambio se usan para convertir facturas en moneda extranjera</li>
                        <li>El sistema usa el tipo de cambio más reciente disponible</li>
                        <li>Actualiza regularmente para mayor precisión</li>
                        <li>Consulta SUNAT o SBS para tipos oficiales</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Historial -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">📋 Historial de Tipos de Cambio</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Moneda</th>
                                    <th>Tipo de Cambio</th>
                                    <th>Fuente</th>
                                    <th>Registrado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tipos_cambio as $tc): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($tc['fecha'])); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $tc['moneda_origen']; ?></span>
                                        →
                                        <span class="badge bg-secondary"><?php echo $tc['moneda_destino']; ?></span>
                                    </td>
                                    <td>
                                        <strong>S/ <?php echo number_format($tc['tipo_cambio'], 4); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $tc['fuente']; ?></span>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($tc['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($tipos_cambio)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No hay tipos de cambio registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<?php include '../includes/footer.php'; ?>
