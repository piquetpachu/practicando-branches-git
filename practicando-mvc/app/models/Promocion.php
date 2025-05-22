<?php
class Promocion {
    private $conn;
    private $tabla = "promociones";

    public $id;
    public $titulo;
    public $descripcion;
    public $descuento_porcentaje;
    public $fecha_inicio;
    public $fecha_fin;
    public $imagen;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM $this->tabla ORDER BY fecha_inicio DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear() {
        $query = "INSERT INTO $this->tabla 
                  (titulo, descripcion, descuento_porcentaje, fecha_inicio, fecha_fin, imagen)
                  VALUES (:titulo, :descripcion, :descuento, :inicio, :fin, :imagen)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':descuento', $this->descuento_porcentaje);
        $stmt->bindParam(':inicio', $this->fecha_inicio);
        $stmt->bindParam(':fin', $this->fecha_fin);
        $stmt->bindParam(':imagen', $this->imagen);

        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE $this->tabla SET
                  titulo = :titulo,
                  descripcion = :descripcion,
                  descuento_porcentaje = :descuento,
                  fecha_inicio = :inicio,
                  fecha_fin = :fin,
                  imagen = :imagen
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':descuento', $this->descuento_porcentaje);
        $stmt->bindParam(':inicio', $this->fecha_inicio);
        $stmt->bindParam(':fin', $this->fecha_fin);
        $stmt->bindParam(':imagen', $this->imagen);
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
