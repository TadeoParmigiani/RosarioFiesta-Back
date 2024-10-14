<?php

require_once '../config/db.php';

// Obtengo los datos enviados por el formulario en JSON
$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'];
$apellido = $data['apellido'];
$email = $data['email'];
$telefono = $data['telefono'];
$fecha = $data['fecha'];
$mensaje = $data['mensaje'];

// Preparo la consulta para insertar datos
$stmt = $conn->prepare("INSERT INTO mensajes (nombre, apellido, email, telefono, fecha_nacimiento, mensaje) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nombre, $apellido, $email, $telefono, $fecha, $mensaje);

// Ejecuto la consulta y verifico el resultado
$response = array();

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Datos guardados correctamente.';
} else {
    $response['success'] = false;
    $response['message'] = 'Error al guardar los datos: ' . $conn->error;
}

// Cierro la consulta y la conexión
$stmt->close();
$conn->close();

// Devuelvo la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
