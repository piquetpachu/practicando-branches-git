<?php
require_once (__DIR__.'/../config/conexion.php');

function obtenerDepartamentosLibres($pdo) {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE estado = 'libre'");
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $promociones[] = $fila;
        }

        echo json_encode($promociones);
}

function alquileresActivos($pdo) {
    $stmt = $pdo->query("SELECT i.nombre_completo,a.*, d.numero AS departamento FROM alquileres a
                         JOIN departamentos d ON a.id_departamento = d.id
                         JOIN inquilinos i ON i.id = a.id_inquilino
                         WHERE a.estado = 'en curso'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function inquilinosConDeuda($pdo) {
    $stmt = $pdo->query("SELECT i.nombre_completo, p.monto, p.estado, p.fecha_pago
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
function registrarInquilino($pdo, $datos) {
    $sql = "INSERT INTO inquilinos (
                nombre_completo, dni, telefono, email, direccion_origen, 
                marca_vehiculo, modelo_vehiculo, patente_vehiculo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $datos['nombre_completo'],
        $datos['dni'],
        $datos['telefono'],
        $datos['email'],
        $datos['direccion_origen'],
        $datos['marca_vehiculo'],
        $datos['modelo_vehiculo'],
        $datos['patente_vehiculo']
    ]);

    return $pdo->lastInsertId(); // Puedes retornar el ID si lo necesitás
}


?>
