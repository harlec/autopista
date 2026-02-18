<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// CSRF token
if (empty($_POST['csrf_token']) || !function_exists('verify_csrf_token') || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = sanitizeInput($_POST['nombre']);
    $ruc = sanitizeInput($_POST['ruc']);
    $direccion = !empty($_POST['direccion']) ? sanitizeInput($_POST['direccion']) : null;
    $telefono = !empty($_POST['telefono']) ? sanitizeInput($_POST['telefono']) : null;
    $email = !empty($_POST['email']) ? sanitizeInput($_POST['email']) : null;
    $contacto = !empty($_POST['contacto']) ? sanitizeInput($_POST['contacto']) : null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar RUC
    if (!preg_match('/^\d{11}$/', $ruc)) {
        echo json_encode(['success' => false, 'message' => 'El RUC debe tener 11 dígitos']);
        exit;
    }
    
    if ($id > 0) {
        // Actualizar proveedor existente
        $query = "UPDATE proveedores SET 
                  nombre = :nombre, 
                  ruc = :ruc, 
                  direccion = :direccion, 
                  telefono = :telefono, 
                  email = :email, 
                  contacto = :contacto, 
                  activo = :activo
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
    } else {
        // Verificar si ya existe un proveedor con ese RUC
        $query = "SELECT id FROM proveedores WHERE ruc = :ruc";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ruc', $ruc);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un proveedor con ese RUC']);
            exit;
        }
        
        // Insertar nuevo proveedor
        $query = "INSERT INTO proveedores (nombre, ruc, direccion, telefono, email, contacto, activo) 
                  VALUES (:nombre, :ruc, :direccion, :telefono, :email, :contacto, :activo)";
        
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':ruc', $ruc);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':contacto', $contacto);
    $stmt->bindParam(':activo', $activo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proveedor guardado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el proveedor']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
