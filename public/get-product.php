<?php
require_once '../config/db.php'; // Asegúrate de que la ruta a db.php sea correcta

// Consulta para obtener todos los productos activos
$query = "SELECT id_producto, nombre, precio, stock, estado_producto, img, id_categoria, descripcion FROM productos ";
$result = $conn->query($query);

// Verificar si se obtuvo algún resultado
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
