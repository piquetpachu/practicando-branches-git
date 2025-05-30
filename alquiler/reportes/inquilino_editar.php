<?php
require_once 'conexion.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM inquilinos WHERE id = ?");
$stmt->execute([$id]);
$inquilino = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE inquilinos SET nombre_completo=?, dni=?, telefono=?, email=?, direccion_origen=?, marca_vehiculo=?, modelo_vehiculo=?, patente_vehiculo=?
            WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['nombre_completo'], $_POST['dni'], $_POST['telefono'], $_POST['email'],
        $_POST['direccion_origen'], $_POST['marca_vehiculo'], $_POST['modelo_vehiculo'],
        $_POST['patente_vehiculo'], $id
    ]);
    echo "Actualizado.";
}
?>

<form method="POST">
    <input type="text" name="nombre_completo" value="<?= $inquilino['nombre_completo'] ?>" required><br>
    <input type="text" name="dni" value="<?= $inquilino['dni'] ?>"><br>
    <input type="text" name="telefono" value="<?= $inquilino['telefono'] ?>"><br>
    <input type="email" name="email" value="<?= $inquilino['email'] ?>"><br>
    <input type="text" name="direccion_origen" value="<?= $inquilino['direccion_origen'] ?>"><br>
    <input type="text" name="marca_vehiculo" value="<?= $inquilino['marca_vehiculo'] ?>"><br>
    <input type="text" name="modelo_vehiculo" value="<?= $inquilino['modelo_vehiculo'] ?>"><br>
    <input type="text" name="patente_vehiculo" value="<?= $inquilino['patente_vehiculo'] ?>"><br>
    <button type="submit">Guardar cambios</button>
</form>
