<?php
require_once 'conexion.php';
$stmt = $pdo->query("SELECT * FROM inquilinos");
?>

<h2>Listado de inquilinos</h2>
<table border="1">
    <tr>
        <th>Nombre</th><th>DNI</th><th>Teléfono</th><th>Acciones</th>
    </tr>
    <?php while ($row = $stmt->fetch()): ?>
    <tr>
        <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
        <td><?= $row['dni'] ?></td>
        <td><?= $row['telefono'] ?></td>
        <td>
            <a href="inquilino_editar.php?id=<?= $row['id'] ?>">Editar</a> |
            <a href="inquilino_borrar.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Borrar?')">Borrar</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
