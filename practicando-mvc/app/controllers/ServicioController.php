<?php
require_once __DIR__ . '/../models/Servicio.php';

class ServicioController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerServicios() {
        $servicio = new Servicio($this->conn);
        $resultado = $servicio->listar();

        $servicios = [];
        while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $servicios[] = $fila;
        }

        echo json_encode($servicios);
    }
}
