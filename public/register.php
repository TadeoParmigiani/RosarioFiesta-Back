<?php
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Agarro los datos del formulario
    $fullName = htmlspecialchars(trim($_POST['fullName']), ENT_QUOTES, 'UTF-8');
    $dni = htmlspecialchars(trim($_POST['dni']), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    
    if (!$fullName || !$dni || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    // Compruebor si el email ya existe en la base de datos
    $checkEmailQuery = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
    $checkEmailQuery->bind_param("s", $email);
    $checkEmailQuery->execute();
    $result = $checkEmailQuery->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este correo electrónico ya está registrado.']);
        exit;
    }

    // Hashear la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insertor el nuevo usuario en la base de datos
    $query = $conn->prepare("INSERT INTO usuario (nombre, dni, email, contraseña) VALUES (?, ?, ?, ?)");
    $query->bind_param("ssss", $fullName, $dni, $email, $hashedPassword);

    if ($query->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro exitoso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario.']);
    }

    
    $query->close();
    $checkEmailQuery->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
}
?>
