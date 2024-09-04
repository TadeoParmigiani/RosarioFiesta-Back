// logout.php
<?php
session_start();
session_unset(); // Limpiar todas las variables de sesión
session_destroy(); 
exit;
?>
