<?php
require_once __DIR__ . '/../models/Categoria.php';

class CategoriaController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerCategorias() {
        $categoria = new Categoria($this->conn);
        $resultado = $categoria->listar();
        $categorias = [];

        while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $categorias[] = $fila;
        }

        echo json_encode($categorias);
    }

    public function crearCategoria($data) {
        $categoria = new Categoria($this->conn);
        $categoria->nombre = $data['nombre'];

        if ($categoria->crear()) {
            echo json_encode(["mensaje" => "Categoría creada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al crear categoría"]);
        }
    }

    public function actualizarCategoria($id, $data) {
        $categoria = new Categoria($this->conn);
        $categoria->id = $id;
        $categoria->nombre = $data['nombre'];

        if ($categoria->actualizar()) {
            echo json_encode(["mensaje" => "Categoría actualizada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al actualizar categoría"]);
        }
    }

    public function eliminarCategoria($id) {
        $categoria = new Categoria($this->conn);
        $categoria->id = $id;

        if ($categoria->eliminar()) {
            echo json_encode(["mensaje" => "Categoría eliminada"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al eliminar categoría"]);
        }
    }
}
