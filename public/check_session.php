<?php
session_start();
header('Content-Type: application/json');


$response = [
    'authenticated' => false,
    'tipo_usuario' => null
];

// Verifico si el usuario ha iniciado sesión
if (isset($_SESSION['id_usuario'])) {
    
    include_once('../config/db.php');

    
    if ($conn) {
        
        $id_usuario = $_SESSION['id_usuario'];

        // Consulto para obtener el tipo de usuario
        $query = "SELECT tipo_usuario FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            // Usuario autenticado, devolver tipo de usuario
            $response['authenticated'] = true;
            $response['tipo_usuario'] = $row['tipo_usuario'];
        }

        $stmt->close();
    } else {
        
        $response['error'] = "Error de conexión a la base de datos.";
    }
}

// Devuelvo la respuesta en formato JSON
echo json_encode($response);
?>
