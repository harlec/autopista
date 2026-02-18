<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_login();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $id = intval($_GET['id']);
    
    $query = "SELECT * FROM proveedores WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($proveedor) {
        echo json_encode(['success' => true, 'proveedor' => $proveedor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
