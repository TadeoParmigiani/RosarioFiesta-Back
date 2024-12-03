<?php
// Conexión a la base de datos
require_once '../config/db.php'; 
session_start(); 

// Leer los datos enviados (ID del producto a eliminar)
$data = json_decode(file_get_contents('php://input'), true);
$id_producto = $data['id_producto'] ?? null;

// Validar que se haya proporcionado el ID
if (!$id_producto) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID del producto es requerido']);
    exit();
}

// Iniciar una transacción
$conn->begin_transaction();

try {
    // Verificar si el producto existe
    $query_verificar_producto = "SELECT id_producto FROM productos WHERE id_producto = ?";
    $stmt_verificar = $conn->prepare($query_verificar_producto);
    $stmt_verificar->bind_param("i", $id_producto);
    $stmt_verificar->execute();
    $stmt_verificar->store_result();

    if ($stmt_verificar->num_rows === 0) {
        throw new Exception('El producto no existe');
    }

    $stmt_verificar->close();

    // Eliminar los registros relacionados en promociones_productos
    $query_eliminar_promociones = "DELETE FROM promociones_productos WHERE id_promocion = ?";
    $stmt_eliminar_promociones = $conn->prepare($query_eliminar_promociones);
    $stmt_eliminar_promociones->bind_param("i", $id_producto);

    if (!$stmt_eliminar_promociones->execute()) {
        throw new Exception('Error al eliminar las promociones asociadas al producto');
    }

    $stmt_eliminar_promociones->close();

    // Eliminar el producto de la tabla productos
    $query_eliminar_producto = "DELETE FROM productos WHERE id_producto = ?";
    $stmt_eliminar_producto = $conn->prepare($query_eliminar_producto);
    $stmt_eliminar_producto->bind_param("i", $id_producto);

    if (!$stmt_eliminar_producto->execute()) {
        throw new Exception('Error al eliminar el producto');
    }

    $stmt_eliminar_producto->close();

    // Confirmar la transacción
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Producto y promociones eliminados exitosamente']);
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Cerrar la conexión
$conn->close();
?>
