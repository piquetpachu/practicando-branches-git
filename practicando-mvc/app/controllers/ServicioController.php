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

    public function crearServicio($data) {
        $servicio = new Servicio($this->conn);
        $servicio->titulo = $data['titulo'];
        $servicio->descripcion = $data['descripcion'];
        $servicio->precio = $data['precio'];
        $servicio->imagen = $data['imagen'];
        $servicio->id_categoria = $data['id_categoria'];

        if ($servicio->crear()) {
            echo json_encode(["mensaje" => "Servicio creado correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al crear el servicio"]);
        }
    }

    public function actualizarServicio($id, $data) {
        $servicio = new Servicio($this->conn);
        $servicio->id = $id;
        $servicio->titulo = $data['titulo'];
        $servicio->descripcion = $data['descripcion'];
        $servicio->precio = $data['precio'];
        $servicio->imagen = $data['imagen'];
        $servicio->id_categoria = $data['id_categoria'];

        if ($servicio->actualizar()) {
            echo json_encode(["mensaje" => "Servicio actualizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al actualizar el servicio"]);
        }
    }

    public function eliminarServicio($id) {
        $servicio = new Servicio($this->conn);
        $servicio->id = $id;

        if ($servicio->eliminar()) {
            echo json_encode(["mensaje" => "Servicio eliminado"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al eliminar el servicio"]);
        }
    }
}
