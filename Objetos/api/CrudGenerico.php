<?php
require_once 'conexion.php';
class CrudGenerico {
    private $pdo;
    private $tabla;

    public function __construct($pdo, $tabla) {
        $this->pdo = $pdo;
        $this->tabla = $tabla;
    }

    // CREATE
    public function insertar($datos) {
        $columnas = implode(', ', array_keys($datos));
        $valores = ':' . implode(', :', array_keys($datos));

        $sql = "INSERT INTO {$this->tabla} ($columnas) VALUES ($valores)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($datos);
    }

    // READ
    public function obtenerTodos() {
        $sql = "SELECT * FROM {$this->tabla}";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id, $columnaId = 'id') {
        $sql = "SELECT * FROM {$this->tabla} WHERE $columnaId = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function actualizar($id, $datos, $columnaId = 'id') {
        $set = '';
        foreach ($datos as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ', ');

        $sql = "UPDATE {$this->tabla} SET $set WHERE $columnaId = :id";
        $stmt = $this->pdo->prepare($sql);
        $datos['id'] = $id;
        return $stmt->execute($datos);
    }

    // DELETE
    public function eliminar($id, $columnaId = 'id') {
        $sql = "DELETE FROM {$this->tabla} WHERE $columnaId = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
