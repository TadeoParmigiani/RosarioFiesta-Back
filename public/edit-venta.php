<?php
// Conexión a la base de datos
require_once '../config/db.php'; 

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);
$id_venta = $data['id_venta'] ?? null;
$nuevo_estado = $data['nuevo_estado'] ?? null;

// Validar que se haya proporcionado el ID y el nuevo estado
if (!$id_venta || !$nuevo_estado) {
    echo json_encode(['success' => false, 'message' => 'ID de la venta y nuevo estado son requeridos']);
    exit();
}

// Actualizar el estado de la venta
$query = "UPDATE ventas SET estado = ? WHERE id_venta = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('si', $nuevo_estado, $id_venta);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
