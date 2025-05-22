<?php
require_once __DIR__ . '/../models/Promocion.php';

class PromocionController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPromociones() {
        $promo = new Promocion($this->conn);
        $resultado = $promo->listar();
        $promociones = [];

        while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $promociones[] = $fila;
        }

        echo json_encode($promociones);
    }

    public function crearPromocion($data) {
        $promo = new Promocion($this->conn);
        $promo->titulo = $data['titulo'];
        $promo->descripcion = $data['descripcion'];
        $promo->descuento_porcentaje = $data['descuento_porcentaje'];
        $promo->fecha_inicio = $data['fecha_inicio'];
        $promo->fecha_fin = $data['fecha_fin'];
        $promo->imagen = $data['imagen'] ?? null;

        if ($promo->crear()) {
            echo json_encode(["mensaje" => "Promoción creada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al crear promoción"]);
        }
    }

    public function actualizarPromocion($id, $data) {
        $promo = new Promocion($this->conn);
        $promo->id = $id;
        $promo->titulo = $data['titulo'];
        $promo->descripcion = $data['descripcion'];
        $promo->descuento_porcentaje = $data['descuento_porcentaje'];
        $promo->fecha_inicio = $data['fecha_inicio'];
        $promo->fecha_fin = $data['fecha_fin'];
        $promo->imagen = $data['imagen'] ?? null;

        if ($promo->actualizar()) {
            echo json_encode(["mensaje" => "Promoción actualizada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al actualizar promoción"]);
        }
    }

    public function eliminarPromocion($id) {
        $promo = new Promocion($this->conn);
        $promo->id = $id;

        if ($promo->eliminar()) {
            echo json_encode(["mensaje" => "Promoción eliminada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al eliminar promoción"]);
        }
    }
}
