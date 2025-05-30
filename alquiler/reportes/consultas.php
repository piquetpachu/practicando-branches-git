<?php
require_once 'conexion.php';

/**
 * Retrieves all apartments that are currently available.
 *
 * Returns an array of records from the "departamentos" table where the "estado" is 'libre'.
 *
 * @return array List of available apartments as associative arrays.
 */
function obtenerDepartamentosLibres($pdo) {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE estado = 'libre'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/****
 * Retrieves all active rental agreements with associated apartment numbers.
 *
 * Returns an array of active rentals (estado 'en curso'), including all rental fields and the corresponding apartment number.
 *
 * @return array List of active rentals with apartment details.
 */
function alquileresActivos($pdo) {
    $stmt = $pdo->query("SELECT a.*, d.numero AS departamento FROM alquileres a
                         JOIN departamentos d ON a.id_departamento = d.id
                         WHERE a.estado = 'en curso'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieves tenants with outstanding or partial payments.
 *
 * Returns a list of tenants who have at least one payment marked as 'Debe' (owed) or 'Parcial' (partial), including their full name, payment amount, and payment status.
 *
 * @return array List of tenants with debt, each entry containing 'nombre_completo', 'monto', and 'estado'.
 */
function inquilinosConDeuda($pdo) {
    $stmt = $pdo->query("SELECT i.nombre_completo, p.monto, p.estado 
                         FROM pagos p
                         JOIN alquileres a ON p.id_alquiler = a.id
                         JOIN inquilinos i ON a.id_inquilino = i.id
                         WHERE p.estado IN ('Debe', 'Parcial')");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieves the total payment amounts grouped by day within a specified date range.
 *
 * @param string $inicio Start date in 'YYYY-MM-DD' format.
 * @param string $fin End date in 'YYYY-MM-DD' format.
 * @return array List of days and their corresponding total payment amounts.
 */
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
