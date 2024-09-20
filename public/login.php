<?php

include '../config/db.php'; 

// Obtengo los datos enviados por el formulario
$email = $_POST['email'];
$password = $_POST['password'];


$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$response = array();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verifico la contraseña
    if (password_verify($password, $user['contraseña'])) {
        // Inicio de sesión exitoso
        $response['success'] = true;
       
        session_start();
        $_SESSION['id_usuario'] = $user['id_usuario']; 
        $_SESSION['email'] = $user['email'];
    } else {
        // Contraseña incorrecta
        $response['success'] = false;
        $response['message'] = 'Contraseña incorrecta.';
    }
} else {
    // Usuario no encontrado
    $response['success'] = false;
    $response['message'] = 'No existe un usuario con ese correo electrónico.';
}

// Devuelvo la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
