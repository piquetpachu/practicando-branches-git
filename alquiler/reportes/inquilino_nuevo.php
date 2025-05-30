<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO inquilinos (nombre_completo, dni, telefono, email, direccion_origen, marca_vehiculo, modelo_vehiculo, patente_vehiculo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['nombre_completo'],
        $_POST['dni'],
        $_POST['telefono'],
        $_POST['email'],
        $_POST['direccion_origen'],
        $_POST['marca_vehiculo'],
        $_POST['modelo_vehiculo'],
        $_POST['patente_vehiculo']
    ]);
    echo "Inquilino registrado.";
}
?>

<form method="POST">
    <input type="text" name="nombre_completo" placeholder="Nombre completo" required><br>
    <input type="text" name="dni" placeholder="DNI"><br>
    <input type="text" name="telefono" placeholder="Teléfono"><br>
    <input type="email" name="email" placeholder="Email"><br>
    <input type="text" name="direccion_origen" placeholder="Dirección de origen"><br>
    <input type="text" name="marca_vehiculo" placeholder="Marca del vehículo"><br>
    <input type="text" name="modelo_vehiculo" placeholder="Modelo del vehículo"><br>
    <input type="text" name="patente_vehiculo" placeholder="Patente"><br>
    <button type="submit">Guardar</button>
</form>
