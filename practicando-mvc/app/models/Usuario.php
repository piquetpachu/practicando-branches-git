<?php
class Usuario {
    private $conn;
    private $tabla = "usuarios";

    public $id;
    public $usuario;
    public $contrasena;
    public $rol;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT id, usuario, rol FROM $this->tabla ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear() {
        $query = "INSERT INTO $this->tabla (usuario, contrasena, rol) 
                  VALUES (:usuario, :contrasena, :rol)";
        $stmt = $this->conn->prepare($query);

        $this->contrasena = password_hash($this->contrasena, PASSWORD_DEFAULT);

        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':contrasena', $this->contrasena);
        $stmt->bindParam(':rol', $this->rol);

        return $stmt->execute();
    }

    public function login() {
        $query = "SELECT * FROM $this->tabla WHERE usuario = :usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function eliminar() {
        $query = "DELETE FROM $this->tabla WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
