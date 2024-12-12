<?php
require_once '../config/db.php';

// Verificar si los datos necesarios están presentes
if (!isset($_POST['editImg']) || !isset($_POST['id_producto']) || !isset($_POST['editNombre']) || !isset($_POST['editPrecio']) || !isset($_POST['editDescripcion']) || !isset($_POST['editEstado']) || !isset($_POST['productosBase']) || !isset($_POST['cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la edición']);
    exit();
}

// Asignar variables desde los datos enviados (POST)
$id_producto = $_POST['id_producto'];
$nombre = $_POST['editNombre'];
$precio = $_POST['editPrecio'];
$descripcion = $_POST['editDescripcion'];
$estado_producto = $_POST['editEstado'];
$productosBase = $_POST['productosBase']; // No usamos json_decode(), ya que es un arreglo directamente
$cantidades = $_POST['cantidad']; // Igual para las cantidades
$img = $_POST['editImg'];

// Eliminar duplicados en productosBase
$productosBase = array_unique($productosBase);

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
    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, descripcion = ?, estado_producto = ?, stock = ?, img = ? WHERE id_producto = ?");
    $stmt->bind_param("sdssisi", $nombre, $precio, $descripcion, $estado_producto, $stock_promocion, $img, $id_producto);
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

        // Insertar los productos base de la promoción
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
