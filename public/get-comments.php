<?php
require_once '../config/db.php';

$sql = "SELECT CONCAT(nombre, ' ', apellido) AS nombre, email, telefono, fecha_nacimiento, mensaje FROM mensajes";
$result = $conn->query($sql);

$mensaje = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $mensaje[] = $row;
    }
} else {
    $mensaje['status'] = 'error';
    $mensaje['message'] = 'No hay mensajes para mostrar.';
}

// Devuelvo los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($mensaje);
?>