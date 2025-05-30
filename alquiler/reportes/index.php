<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h2>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h2>
<p>Rol: <?= $_SESSION['usuario_rol'] ?></p>

<ul>
    <li><a href="ver_alquileres.php">Ver alquileres</a></li>
    <li><a href="ver_pagos.php">Ver pagos</a></li>
    <?php if ($_SESSION['usuario_rol'] === 'dueno'): ?>
        <li><a href="reportes.php">Reportes</a></li>
        <li><a href="registro.php">Registrar nuevo usuario</a></li>
    <?php endif; ?>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
?>
<?php
require_once 'consultas.php';

$accion = $_GET['accion'] ?? null;
$resultado = [];

if ($accion) {
    switch ($accion) {
        case 'libres':
            $resultado = obtenerDepartamentosLibres($pdo);
            break;
        case 'activos':
            $resultado = alquileresActivos($pdo);
            break;
        case 'deudas':
            $resultado = inquilinosConDeuda($pdo);
            break;
        case 'ingresos':
            $inicio = $_GET['inicio'] ?? date('Y-m-01');
            $fin = $_GET['fin'] ?? date('Y-m-d');
            $resultado = ingresosPorDia($pdo, $inicio, $fin);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Alquileres</title>
</head>
<body>
    <h1>Panel de Reportes</h1>

    <form method="get">
        <select name="accion">
            <option value="libres">Departamentos libres</option>
            <option value="activos">Alquileres activos</option>
            <option value="deudas">Inquilinos con deuda</option>
            <option value="ingresos">Ingresos por día</option>
        </select>

        <label>Desde: <input type="date" name="inicio"></label>
        <label>Hasta: <input type="date" name="fin"></label>

        <button type="submit">Consultar</button>
    </form>

    <?php if ($resultado): ?>
        <table border="1" cellpadding="5" style="margin-top:20px;">
            <thead>
                <tr>
                    <?php foreach (array_keys($resultado[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultado as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?= htmlspecialchars($valor) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($accion): ?>
        <p>No se encontraron resultados.</p>
    <?php endif; ?>
</body>
</html>
