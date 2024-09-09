<?php
require_once '../config/db.php';

if ($db && $db instanceof mysqli) {
    echo "Conexión a la base de datos exitosa.";
} else {
    echo "Error en la conexión a la base de datos.";
}
?>
