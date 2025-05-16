<?php
session_start();
include __DIR__ . '/../../conexion.php';

$usuario = $_POST['usuario'];
$contrasena = $_POST['contrasena'];

$sql = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$sql->execute([$usuario]);
$user = $sql->fetch();

if ($user && $user['contrasena'] == $contrasena) { // luego usar password_verify
    $_SESSION['usuario'] = $user['usuario'];
    $_SESSION['rol'] = $user['rol'];
    header("Location: ../../public/admin.php");
} else {
    echo "Credenciales inválidas";
}
?>
