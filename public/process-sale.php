<?php
require_once '../config/db.php'; 
session_start(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['cliente']['nombre'] ?? null;
$apellido = $data['cliente']['apellido'] ?? null;
$email = $data['cliente']['email'] ?? null;
$dni = $data['cliente']['dni'] ?? null;
$telefono = $data['cliente']['telefono'] ?? null;
$metodo_pago = $data['metodo_pago'] ?? null;
$productos = $data['productos'] ?? null; 

if (!$nombre || !$apellido || !$email || !$dni || !$telefono || !$metodo_pago || !$productos) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit();
}

$conn->begin_transaction();

try {
    $query_verificar_cliente = "SELECT id_cliente FROM clientes WHERE email = ?";
    $stmt_verificar_cliente = $conn->prepare($query_verificar_cliente);
    $stmt_verificar_cliente->bind_param("s", $email);
    $stmt_verificar_cliente->execute();
    $stmt_verificar_cliente->store_result();

    if ($stmt_verificar_cliente->num_rows > 0) {
        $stmt_verificar_cliente->bind_result($cliente_id);
        $stmt_verificar_cliente->fetch();
        $stmt_verificar_cliente->close();
    } else {
        $query_cliente = "INSERT INTO clientes (nombre, apellido, email, dni, telefono, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_cliente = $conn->prepare($query_cliente);
        $stmt_cliente->bind_param("sssssi", $nombre, $apellido, $email, $dni, $telefono, $id_usuario);

        if (!$stmt_cliente->execute()) {
            throw new Exception('Error al insertar el cliente');
        }

        $cliente_id = $stmt_cliente->insert_id;
        $stmt_cliente->close();
    }

    $total = 0;
    foreach ($productos as $producto) {
        $total += $producto['quantity'] * $producto['price'];
    }

    $query_venta = "INSERT INTO ventas (id_cliente, metodo_pago, estado, fecha, total) VALUES (?, ?, 'Pendiente', NOW(), ?)";
    $stmt_venta = $conn->prepare($query_venta);
    $stmt_venta->bind_param("isd", $cliente_id, $metodo_pago, $total);

    if (!$stmt_venta->execute()) {
        throw new Exception('Error al insertar la venta');
    }

    $venta_id = $stmt_venta->insert_id;
    $stmt_venta->close();

    foreach ($productos as $producto) {
        $query_producto = "INSERT INTO ventas_productos (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_producto = $conn->prepare($query_producto);
        $stmt_producto->bind_param("iiid", $venta_id, $producto['id'], $producto['quantity'], $producto['price']);

        if (!$stmt_producto->execute()) {
            throw new Exception('Error al insertar el producto en la venta');
        }

        $query_actualizar_stock = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
        $stmt_actualizar_stock = $conn->prepare($query_actualizar_stock);
        $stmt_actualizar_stock->bind_param("ii", $producto['quantity'], $producto['id']);

        if (!$stmt_actualizar_stock->execute()) {
            throw new Exception('Error al actualizar el stock del producto');
        }

        $stmt_actualizar_stock->close();

        // Si el producto es una promoción, actualizar el stock de los productos base
        $query_promocion = "SELECT id_producto, cantidad FROM promociones_productos WHERE id_promocion = ?";
        $stmt_promocion = $conn->prepare($query_promocion);
        $stmt_promocion->bind_param("i", $producto['id']);
        $stmt_promocion->execute();
        $result_promocion = $stmt_promocion->get_result();

        while ($promocion = $result_promocion->fetch_assoc()) {
            $id_producto_base = $promocion['id_producto'];
            $cantidad_base = $promocion['cantidad'] * $producto['quantity'];

            // Actualizar el stock del producto base
            $query_actualizar_base = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
            $stmt_actualizar_base = $conn->prepare($query_actualizar_base);
            $stmt_actualizar_base->bind_param("ii", $cantidad_base, $id_producto_base);

            if (!$stmt_actualizar_base->execute()) {
                throw new Exception('Error al actualizar el stock del producto base');
            }
            $stmt_actualizar_base->close();

            // Obtener las promociones que contienen este producto base
            $query_promociones_impactadas = "SELECT id_promocion FROM promociones_productos WHERE id_producto = ?";
            $stmt_promociones_impactadas = $conn->prepare($query_promociones_impactadas);
            $stmt_promociones_impactadas->bind_param("i", $id_producto_base);
            $stmt_promociones_impactadas->execute();
            $result_promociones_impactadas = $stmt_promociones_impactadas->get_result();

            while ($promocion_impactada = $result_promociones_impactadas->fetch_assoc()) {
                $id_promocion_impactada = $promocion_impactada['id_promocion'];

                // Calcular el nuevo stock mínimo para la promoción
                $query_stock_promocion = "
                    SELECT MIN(p.stock / pp.cantidad) AS stock_minimo
                    FROM productos p
                    INNER JOIN promociones_productos pp ON p.id_producto = pp.id_producto
                    WHERE pp.id_promocion = ?
                ";
                $stmt_stock_promocion = $conn->prepare($query_stock_promocion);
                $stmt_stock_promocion->bind_param("i", $id_promocion_impactada);
                $stmt_stock_promocion->execute();
                $result_stock_promocion = $stmt_stock_promocion->get_result();

                if ($row = $result_stock_promocion->fetch_assoc()) {
                    $nuevo_stock_promocion = intval($row['stock_minimo']);

                    // Actualizar el stock de la promoción impactada
                    $query_actualizar_promocion = "UPDATE productos SET stock = ? WHERE id_producto = ?";
                    $stmt_actualizar_promocion = $conn->prepare($query_actualizar_promocion);
                    $stmt_actualizar_promocion->bind_param("ii", $nuevo_stock_promocion, $id_promocion_impactada);

                    if (!$stmt_actualizar_promocion->execute()) {
                        throw new Exception('Error al actualizar el stock de la promoción impactada');
                    }

                    $stmt_actualizar_promocion->close();
                }

                $stmt_stock_promocion->close();
            }

            $stmt_promociones_impactadas->close();
        }
        $stmt_promocion->close();
        $stmt_producto->close();
    }

    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Venta realizada con éxito']);
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
