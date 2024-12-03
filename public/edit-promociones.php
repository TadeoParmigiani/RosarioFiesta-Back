<?php
require_once '../config/db.php';

// Obtener los datos enviados mediante POST
$data = json_decode(file_get_contents("php://input"));

// Verificar si los datos necesarios están presentes
if (!isset($data->id_producto) || !isset($data->nombre) || !isset($data->precio) || !isset($data->descripcion) || !isset($data->estado_producto) || !isset($data->productosBase) || !isset($data->cantidad)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la edición']);
    exit();
}

$id_producto = $data->id_producto;
$nombre = $data->nombre;
$precio = $data->precio;
$descripcion = $data->descripcion;
$estado_producto = $data->estado_producto;
$productosBase = $data->productosBase;
$cantidades = $data->cantidad;

// Calcular el stock de la promoción
$stock_promocion = PHP_INT_MAX; // Inicializar con el valor máximo posible

foreach ($productosBase as $index => $productoId) {
    $cantidad = $cantidades[$index];

    // Consultar el stock actual del producto base
    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id_producto = ?");
    $stmt->bind_param("i", $productoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();

    if ($producto) {
        $stock_base = $producto['stock'];

        // Calcular cuántas promociones se pueden hacer con este producto
        $stock_promocion = min($stock_promocion, floor($stock_base / $cantidad));
    } else {
        echo json_encode(['success' => false, 'message' => "Producto base con ID $productoId no encontrado"]);
        exit();
    }
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Actualizar la información básica de la promoción
    $stmt = $conn->prepare("
        UPDATE productos 
        SET nombre = ?, precio = ?, descripcion = ?, estado_producto = ?, stock = ? 
        WHERE id_producto = ?
    ");
    $stmt->bind_param("sdssii", $nombre, $precio, $descripcion, $estado_producto, $stock_promocion, $id_producto);
    $stmt->execute();
    $stmt->close();

    // Eliminar los productos base existentes de la promoción
    $stmt = $conn->prepare("DELETE FROM promociones_productos WHERE id_promocion = ?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $stmt->close();

    // Insertar los nuevos productos base y sus cantidades
    foreach ($productosBase as $index => $productoId) {
        $cantidad = $cantidades[$index];

        $stmt = $conn->prepare("INSERT INTO promociones_productos (id_promocion, id_producto, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_producto, $productoId, $cantidad);
        $stmt->execute();
        $stmt->close();
    }

    // Confirmar transacción
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Promoción editada correctamente']);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al editar la promoción: ' . $e->getMessage()]);
}
?>
