<?php
class Categoria {
    private $conn;
    private $tabla = "categorias";

    public $id;
    public $nombre;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM $this->tabla";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear() {
        $query = "INSERT INTO $this->tabla (nombre) VALUES (:nombre)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $this->nombre);
        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE $this->tabla SET nombre = :nombre WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM $this->tabla WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
