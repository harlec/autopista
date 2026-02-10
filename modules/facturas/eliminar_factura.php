<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $id = intval($input['id']);
    
    // Obtener nombre del archivo PDF antes de eliminar
    $query = "SELECT archivo_pdf FROM facturas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminar factura
    $query = "DELETE FROM facturas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        // Eliminar archivo PDF si existe
        if ($factura && $factura['archivo_pdf']) {
            $pdf_path = __DIR__ . '/../../assets/uploads/facturas/' . $factura['archivo_pdf'];
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Factura eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la factura']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
