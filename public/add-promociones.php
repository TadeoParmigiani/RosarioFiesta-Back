<?php

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $estado_producto = $_POST['estado_producto'];
    $img = $_POST['img'];
    $productos_base = isset($_POST['productosBase']) ? $_POST['productosBase'] : [];
    $cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : [];
    $id_categoria_promocion = 1; // Categoría fija para promociones

    // Validar entradas
    if (empty($productos_base) || count($productos_base) !== count($cantidades)) {
        echo "Debe proporcionar productos base y cantidades válidos.";
        exit;
    }

    // Calcular el stock de la promoción
    $stock_promocion = PHP_INT_MAX;

    foreach ($productos_base as $index => $id_producto_base) {
        $cantidad = $cantidades[$index];

        // Consultar el stock del producto base
        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto_base);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        $stmt->close();

        if ($producto) {
            $stock_base = $producto['stock'];

            // Calcular cuántas promociones se pueden hacer con este producto
            $stock_promocion = min($stock_promocion, floor($stock_base / $cantidad));
        } else {
            echo "Producto base con ID $id_producto_base no encontrado.";
            exit;
        }
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Insertar la promoción en `productos`
        $stmt = $conn->prepare("
            INSERT INTO productos (nombre, precio, descripcion, estado_producto, img, id_categoria, es_promocion, stock) 
            VALUES (?, ?, ?, ?, ?, ?, TRUE, ?)
        ");
        $stmt->bind_param("sdssssi", $nombre, $precio, $descripcion, $estado_producto, $img, $id_categoria_promocion, $stock_promocion);
        $stmt->execute();
        $id_promocion = $stmt->insert_id;
        $stmt->close();

        // 2. Relacionar la promoción con los productos base en `promocion_productos`
        foreach ($productos_base as $index => $id_producto_base) {
            $cantidad = $cantidades[$index];
            $stmt = $conn->prepare("INSERT INTO promociones_productos (id_promocion, id_producto, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_promocion, $id_producto_base, $cantidad);
            $stmt->execute();
            $stmt->close();
        }

        // Confirmar transacción
        $conn->commit();
        echo "Promoción y productos base guardados exitosamente.";
    } catch (Exception $e) {
        // Revertir transacción si algo falla
        $conn->rollback();
        echo "Error al guardar los datos: " . $e->getMessage();
    }

    $conn->close();
}
?>
