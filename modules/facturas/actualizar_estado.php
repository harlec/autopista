<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['estado'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $id = intval($input['id']);
    $estado = $input['estado'];
    $fecha_pago = isset($input['fecha_pago']) ? $input['fecha_pago'] : null;
    
    if ($estado == 'pagada' && $fecha_pago) {
        $query = "UPDATE facturas SET estado = :estado, fecha_pago = :fecha_pago WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':fecha_pago', $fecha_pago);
    } else {
        $query = "UPDATE facturas SET estado = :estado WHERE id = :id";
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
