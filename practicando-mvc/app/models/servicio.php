<?php
class Servicio {
    private $conn;
    private $tabla = "servicios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM $this->tabla";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
