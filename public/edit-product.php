<?php
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = $_POST['id_producto'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'];
    $idCategoria = $_POST['id_categoria'];

    if ($conn) {
        $sql = "UPDATE productos SET nombre = ?, precio = ?, stock = ?, estado_producto = ?, descripcion = ?, id_categoria = ? WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sdissii', $nombre, $precio, $stock, $estado, $descripcion, $idCategoria, $idProducto);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la conexión a la base de datos.']);
    }
}
?>
