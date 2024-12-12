<?php

require_once '../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

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
        $response['message'] = "Debe proporcionar productos base y cantidades válidos.";
        echo json_encode($response);
        exit;
    }

    // Eliminar productos duplicados (sin sumar las cantidades)
    $productos_base_unicos = array_unique($productos_base);
    
    // Filtramos las cantidades para mantener solo las correspondientes a los productos base únicos
    $cantidades_unicas = [];
    foreach ($productos_base_unicos as $producto) {
        $indice = array_search($producto, $productos_base);
        $cantidades_unicas[] = $cantidades[$indice];
    }

    // Calcular el stock de la promoción
    $stock_promocion = PHP_INT_MAX;

    foreach ($productos_base_unicos as $id_producto_base) {
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
            $stock_promocion = min($stock_promocion, floor($stock_base / $cantidades_unicas[array_search($id_producto_base, $productos_base_unicos)]));
        } else {
            $response['message'] = "Producto base con ID $id_producto_base no encontrado.";
            echo json_encode($response);
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

        // 2. Relacionar la promoción con los productos base en `promociones_productos`
        foreach ($productos_base_unicos as $index => $id_producto_base) {
            // Insertamos la relación entre la promoción y el producto base
            $stmt = $conn->prepare("INSERT INTO promociones_productos (id_promocion, id_producto, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_promocion, $id_producto_base, $cantidades_unicas[$index]);
            $stmt->execute();
            $stmt->close();
        }

        // Confirmar transacción
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Promoción y productos base guardados exitosamente.";
    } catch (Exception $e) {
        // Revertir transacción si algo falla
        $conn->rollback();
        $response['message'] = "Error al guardar los datos: " . $e->getMessage();
    }

    $conn->close();
} else {
    $response['message'] = "Método de solicitud no válido.";
}

echo json_encode($response);
