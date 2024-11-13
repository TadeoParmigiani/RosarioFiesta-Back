<?php

include '../config/db.php'; 
session_start();

$response = array();

if (isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];

    // Consulta para obtener los datos del cliente usando el id_usuario
    $stmt = $conn->prepare("SELECT nombre, apellido, email, dni, telefono FROM clientes WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Datos del cliente encontrados
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();
    } else {
        // Cliente no encontrado
        $response['success'] = false;
        $response['message'] = 'Cliente no encontrado.';
    }
} else {
    // Usuario no está logueado
    $response['success'] = false;
    $response['message'] = 'Usuario no logueado.';
}

// Devuelve la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);

?>
