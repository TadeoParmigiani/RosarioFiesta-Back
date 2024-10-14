<?php
require_once '../config/db.php'; 


$id_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 3;


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
