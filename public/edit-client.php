<?php
include_once '../config/db.php';

$id = $_POST['id_cliente'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$dni = $_POST['dni'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];

// Asegúrate de validar y sanitizar los datos en un entorno de producción
$query = "UPDATE clientes SET nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ? WHERE id_cliente = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('sssssi', $nombre, $apellido, $dni, $email, $telefono, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Cliente actualizado correctamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cliente.']);
}
?>
