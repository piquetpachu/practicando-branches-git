<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'] ?? 'ayudante';

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nombre, $email, $contrasena, $rol])) {
        echo "Usuario registrado correctamente.";
    } else {
        echo "Error al registrar.";
    }
}
?>

<form method="POST">
    <label>Nombre: <input type="text" name="nombre" required></label><br>
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Contraseña: <input type="password" name="contrasena" required></label><br>
    <label>Rol:
        <select name="rol">
            <option value="dueno">Dueño</option>
            <option value="ayudante" selected>Ayudante</option>
        </select>
    </label><br>
    <button type="submit">Registrar</button>
</form>
