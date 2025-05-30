<?php
require_once '../conexion.php';
// $sentencia = $conn->prepare("SELECT * FROM servicio");
// $sentencia->execute();
// $result = $sentencia->fetchAll(PDO::FETCH_ASSOC);
// echo json_encode($result);
class servicio {
    private $conn;

    public function __construct() {
        $this->conn = (new conexion())->getConnection();
    }

    public function getServicios() {
        $sentencia = $this->conn->prepare("SELECT * FROM servicio");
        $sentencia->execute();
        $result = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }
    public function PostServicio($titulo, $precio) {
        $sentencia = $this->conn->prepare("INSERT INTO servicio (titulo, precio) VALUES (:titulo, :precio)");
        $sentencia->bindParam(':titulo', $titulo);
        $sentencia->bindParam(':precio', $precio);
        if ($sentencia->execute()) {
            return json_encode(['status' => 'success', 'message' => 'Servicio agregado correctamente']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Error al agregar el servicio']);
        }
    }
    public function PutServicio($id, $titulo, $precio) {
        $sentencia = $this->conn->prepare("UPDATE servicio SET titulo = :titulo, precio = :precio WHERE id = :id");
        $sentencia->bindParam(':id', $id);
        $sentencia->bindParam(':titulo', $titulo);
        $sentencia->bindParam(':precio', $precio);
        if ($sentencia->execute()) {
            return json_encode(['status' => 'success', 'message' => 'Servicio actualizado correctamente']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Error al actualizar el servicio']);
        }
    }
    public function DeleteServicio($id) {
        $sentencia = $this->conn->prepare("DELETE FROM servicio WHERE id = :id");
        $sentencia->bindParam(':id', $id);
        if ($sentencia->execute()) {
            return json_encode(['status' => 'success', 'message' => 'Servicio eliminado correctamente']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Error al eliminar el servicio']);
        }
    }
}