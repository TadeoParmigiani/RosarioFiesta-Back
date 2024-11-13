<?php
session_start();

$response = array();

// Verifica si la sesión está activa y si el usuario está logueado
if (isset($_SESSION['id_usuario'])) {
    $response['id_usuario'] = $_SESSION['id_usuario'];
} else {
    $response['error'] = 'No se encontró sesión activa.';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
