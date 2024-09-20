<?php
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_POST['id_usuario'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $dni = $_POST['dni'];

    if ($conn) {
        $sql = "UPDATE usuario SET nombre = ?, email = ?, dni = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $nombre, $email, $dni, $idUsuario);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la conexión a la base de datos.']);
    }
}
?>
