<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['proveedor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Proveedor no especificado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $proveedor_id = intval($_GET['proveedor_id']);
    
    $query = "SELECT * FROM proveedor_plantillas 
              WHERE proveedor_id = :proveedor_id 
              ORDER BY prioridad DESC, created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    $stmt->execute();
    
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plantillas' => $plantillas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
