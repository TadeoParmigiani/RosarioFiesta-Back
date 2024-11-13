<?php
require_once '../config/db.php';

// Obtener la categoría solicitada (por defecto 3 si no se especifica)
$id_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 3;

// Actualizar el estado a 'inactivo' para productos con stock 0
$updateStatusQuery = "UPDATE productos SET estado_producto = 'inactivo' WHERE stock = 0 AND estado_producto = 'activo'";
$conn->query($updateStatusQuery);

// Consulta para obtener productos activos en la categoría especificada
$query = "SELECT id_producto, nombre, precio, stock, estado_producto, img, id_categoria, descripcion 
          FROM productos 
          WHERE estado_producto = 'activo' AND id_categoria = $id_categoria";

$result = $conn->query($query);

// Verifico si se obtuvo algún resultado
if ($result->num_rows > 0) {
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    echo json_encode(['success' => true, 'productos' => $productos]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron productos activos']);
}

$conn->close();
?>
