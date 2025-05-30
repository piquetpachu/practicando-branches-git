<?php
require_once '../conexion.php';
// $sentencia = $conn->prepare("SELECT * FROM servicio");
// $sentencia->execute();
// $result = $sentencia->fetchAll(PDO::FETCH_ASSOC);
// echo json_encode($result);
class servicio {
    private $conn;

    /**
     * Initializes the servicio class with a database connection.
     *
     * Establishes a connection to the database using the conexion class and stores it for use in CRUD operations.
     */
    public function __construct() {
        $this->conn = (new conexion())->getConnection();
    }

    /**
     * Retrieves all records from the `servicio` table and returns them as a JSON-encoded array.
     *
     * @return string JSON-encoded array of all servicios.
     */
    public function getServicios() {
        $sentencia = $this->conn->prepare("SELECT * FROM servicio");
        $sentencia->execute();
        $result = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }
    /**
     * Inserts a new record into the servicio table with the specified title and price.
     *
     * @param string $titulo The title of the service to add.
     * @param float $precio The price of the service to add.
     * @return string JSON-encoded status message indicating success or failure.
     */
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
    /**
     * Updates an existing servicio record with new title and price.
     *
     * @param int $id The ID of the servicio to update.
     * @param string $titulo The new title for the servicio.
     * @param float $precio The new price for the servicio.
     * @return string JSON-encoded status message indicating success or failure.
     */
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
    /**
     * Deletes a service record from the database by its ID.
     *
     * @param mixed $id The unique identifier of the service to delete.
     * @return string JSON-encoded status message indicating success or failure.
     */
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