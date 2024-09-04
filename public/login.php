<?php
// Incluir el archivo de configuración para la base de datos
include '../config/db.php'; 

// Obtener los datos enviados por el formulario
$email = $_POST['email'];
$password = $_POST['password'];

// Preparar y ejecutar la consulta para obtener el usuario
$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$response = array();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verificar la contraseña
    if (password_verify($password, $user['contraseña'])) {
        // Inicio de sesión exitoso
        $response['success'] = true;
        // Opcional: iniciar una sesión o hacer otras acciones aquí
        session_start();
        $_SESSION['id_usuario'] = $user['id_usuario'];  // Suponiendo que 'id_usuario' es el identificador único del usuario
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

// Devolver la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
