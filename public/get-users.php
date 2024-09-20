<?php
require_once '../config/db.php'; 


$query = "SELECT id_usuario, nombre, email, dni FROM usuario ";
$result = $conn->query($query);



if ($result->num_rows > 0) {
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron usuarios']);
}

$conn->close();
?>