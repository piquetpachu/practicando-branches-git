<?php
session_start();        // Inicia o reanuda la sesión
session_unset();        // Borra todas las variables de sesión
session_destroy();      // Destruye la sesión
header("Location: login.php"); // Redirige al login
exit();                 // Importante: detiene la ejecución del script
?>
