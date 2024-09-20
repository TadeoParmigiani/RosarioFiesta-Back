<?php
require_once '../config/db.php'; 

// Decodifico los datos JSON 
$data = json_decode(file_get_contents('php://input'), true);

// Verifico si los datos se han decodificado correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Error en los datos JSON']);
    exit;
}


$nombre = isset($data['nombre']) ? $data['nombre'] : '';
$descripcion = isset($data['descripcion']) ? $data['descripcion'] : '';
$precio = isset($data['precio']) ? $data['precio'] : 0.0;
$categoria = isset($data['categoria']) ? $data['categoria'] : 3; 
$stock = isset($data['stock']) ? $data['stock'] : 0;
$imagen = isset($data['img']) ? $data['img'] : '';
$estado = isset($data['estado']) ? $data['estado'] : 'activo'; 




$stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, id_categoria, stock, img, estado_producto) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Verifico si la preparación de la consulta fue exitosa
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

// Asocio los parámetros a la consulta preparada
$stmt->bind_param("ssdisss", $nombre, $descripcion, $precio, $categoria, $stock, $imagen, $estado);


$stmt->execute();

// Verificar si se realizó la inserción correctamente
if ($stmt->affected_rows > 0) {
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

    echo json_encode(['success' => false, 'error' => 'No se pudo insertar el producto']);
}


$stmt->close();
$conn->close();
?>
