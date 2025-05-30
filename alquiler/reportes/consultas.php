<?php
require_once 'conexion.php';

function obtenerDepartamentosLibres($pdo) {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE estado = 'libre'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function alquileresActivos($pdo) {
    $stmt = $pdo->query("SELECT a.*, d.numero AS departamento FROM alquileres a
                         JOIN departamentos d ON a.id_departamento = d.id
                         WHERE a.estado = 'en curso'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function inquilinosConDeuda($pdo) {
    $stmt = $pdo->query("SELECT i.nombre_completo, p.monto, p.estado 
                         FROM pagos p
                         JOIN alquileres a ON p.id_alquiler = a.id
                         JOIN inquilinos i ON a.id_inquilino = i.id
                         WHERE p.estado IN ('Debe', 'Parcial')");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ingresosPorDia($pdo, $inicio, $fin) {
    $sql = "SELECT DATE(fecha_pago) AS dia, SUM(monto) AS total
            FROM pagos
            WHERE fecha_pago BETWEEN ? AND ?
            GROUP BY DATE(fecha_pago)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inicio, $fin]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
