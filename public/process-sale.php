<?php
// Conexión a la base de datos
require_once '../config/db.php'; 
session_start(); 

// Validar que el request sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtengo el cuerpo del request en formato JSON
$data = json_decode(file_get_contents('php://input'), true);

// Obtener los datos enviados
$nombre = $data['cliente']['nombre'] ?? null;
$apellido = $data['cliente']['apellido'] ?? null;
$email = $data['cliente']['email'] ?? null;
$dni = $data['cliente']['dni'] ?? null;
$telefono = $data['cliente']['telefono'] ?? null;
$metodo_pago = $data['metodo_pago'] ?? null;
$productos = $data['productos'] ?? null; 

// Validar que todos los campos obligatorios estén presentes
if (!$nombre || !$apellido || !$email || !$dni || !$telefono || !$metodo_pago || !$productos) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

// Obtener el id_usuario desde la sesión
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit();
}

// Iniciar una transacción
$conn->begin_transaction();

try {
    // Verificar si el cliente ya existe por su correo electrónico
    $query_verificar_cliente = "SELECT id_cliente FROM clientes WHERE email = ?";
    $stmt_verificar_cliente = $conn->prepare($query_verificar_cliente);
    $stmt_verificar_cliente->bind_param("s", $email);
    $stmt_verificar_cliente->execute();
    $stmt_verificar_cliente->store_result();

    if ($stmt_verificar_cliente->num_rows > 0) {
        // Si el cliente ya existe, obtener su ID
        $stmt_verificar_cliente->bind_result($cliente_id);
        $stmt_verificar_cliente->fetch();
        $stmt_verificar_cliente->close();
    } else {
        // Si no existe, insertar el nuevo cliente
        $query_cliente = "INSERT INTO clientes (nombre, apellido, email, dni, telefono, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_cliente =  $conn->prepare($query_cliente);
        $stmt_cliente->bind_param("sssssi", $nombre, $apellido, $email, $dni, $telefono, $id_usuario);

        if (!$stmt_cliente->execute()) {
            throw new Exception('Error al insertar el cliente');
        }

        // Obtener el ID del cliente recién insertado
        $cliente_id = $stmt_cliente->insert_id;
        $stmt_cliente->close();
    }

    // Calcular el total de la venta
    $total = 0;
    foreach ($productos as $producto) {
        $total += $producto['quantity'] * $producto['price'];
    }

    // Insertar los datos de la venta en la tabla 'ventas'
    $query_venta = "INSERT INTO ventas (id_cliente, metodo_pago, estado, fecha, total) VALUES (?, ?, 'Pendiente', NOW(), ?)";
    $stmt_venta =  $conn->prepare($query_venta);
    $stmt_venta->bind_param("isd", $cliente_id, $metodo_pago, $total);

    if (!$stmt_venta->execute()) {
        throw new Exception('Error al insertar la venta');
    }

    // Obtener el ID de la venta recién insertada
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

        $stmt_producto->close();
        $stmt_actualizar_stock->close();
    }

    // confirmar la transacción
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Venta realizada con éxito']);
    
} catch (Exception $e) {
    // En caso de error, revertir la transacción
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

}

// Cerrar la conexión a la base de datos
$conn->close();
?>
