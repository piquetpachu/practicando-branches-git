<?php
class Servicio {
    private $conn;
    private $tabla = "servicios";

    public $id;
    public $titulo;
    public $descripcion;
    public $precio;
    public $imagen;
    public $id_categoria;

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
        $query = "INSERT INTO $this->tabla (titulo, descripcion, precio, imagen, id_categoria)
                  VALUES (:titulo, :descripcion, :precio, :imagen, :id_categoria)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':id_categoria', $this->id_categoria);
        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE $this->tabla SET 
                  titulo = :titulo,
                  descripcion = :descripcion,
                  precio = :precio,
                  imagen = :imagen,
                  id_categoria = :id_categoria
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':id_categoria', $this->id_categoria);
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
