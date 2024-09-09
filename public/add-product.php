<?php
require_once '../config/db.php'; // Asegúrate de que la ruta al archivo db.php sea correcta

// Decodificar los datos JSON enviados por el cliente
$data = json_decode(file_get_contents('php://input'), true);

// Verificar si los datos se han decodificado correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Error en los datos JSON']);
    exit;
}

// Asignación de variables con validación básica
$nombre = isset($data['nombre']) ? $data['nombre'] : '';
$descripcion = isset($data['descripcion']) ? $data['descripcion'] : '';
$precio = isset($data['precio']) ? $data['precio'] : 0.0;
$categoria = isset($data['categoria']) ? $data['categoria'] : 3; // Asegúrate de que este valor sea el id correcto
$stock = isset($data['stock']) ? $data['stock'] : 0;
$imagen = isset($data['img']) ? $data['img'] : '';
$estado = isset($data['estado']) ? $data['estado'] : 'activo'; // Define un estado por defecto, como 'activo'



// Preparar la consulta de inserción
$stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, id_categoria, stock, img, estado_producto) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Verificar si la preparación de la consulta fue exitosa
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

// Asociar parámetros a la consulta preparada
$stmt->bind_param("ssdisss", $nombre, $descripcion, $precio, $categoria, $stock, $imagen, $estado);

// Ejecutar la consulta
$stmt->execute();

// Verificar si la consulta afectó filas (es decir, si se realizó la inserción correctamente)
if ($stmt->affected_rows > 0) {
    // Responder con los datos del nuevo producto
    echo json_encode(['success' => true, 'product' => [
        'id_producto' => $stmt->insert_id,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'categoria' => $categoria,
        'stock' => $stock,
        'img' => $imagen,
        'estado_producto' => $estado,
    ]]);
} else {
    // Responder con un mensaje de error si no se pudo insertar
    echo json_encode(['success' => false, 'error' => 'No se pudo insertar el producto']);
}

// Cerrar la consulta y la conexión a la base de datos
$stmt->close();
$conn->close();
?>
