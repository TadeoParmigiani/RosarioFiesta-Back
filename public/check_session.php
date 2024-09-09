<?php
session_start();
header('Content-Type: application/json');

// Inicializa la respuesta
$response = [
    'authenticated' => false,
    'tipo_usuario' => null
];

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['id_usuario'])) {
    
    include_once('../config/db.php');

    
    if ($conn) {
        // Obtener el ID de usuario de la sesión
        $id_usuario = $_SESSION['id_usuario'];

        // Consulta para obtener el tipo de usuario
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

        // Cerrar la declaración
        $stmt->close();
    } else {
        // Manejo de error si la conexión a la BD falla
        $response['error'] = "Error de conexión a la base de datos.";
    }
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
?>
