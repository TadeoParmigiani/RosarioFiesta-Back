<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Validar si 'id_cliente' está presente
if (isset($_GET['id_cliente'])) {
    $id_cliente = (int)$_GET['id_cliente'];

    // Consulta para obtener el cliente
    $query = "SELECT * FROM clientes WHERE id_cliente = $id_cliente";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        echo json_encode($cliente);
    } else {
        echo json_encode(['error' => 'Cliente no encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID de cliente no proporcionado']);
}
?>
