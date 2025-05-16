<?php
$host = "localhost";
$db = "web_salon";
$user = "root";
$pass = "";  // tu contraseña si usás XAMPP o WAMP

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
