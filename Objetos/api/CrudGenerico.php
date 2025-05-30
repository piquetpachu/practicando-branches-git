<?php
require_once 'conexion.php';
class CrudGenerico {
    private $pdo;
    private $tabla;

    /**
     * Initializes the CrudGenerico instance with a PDO connection and target table name.
     *
     * @param \PDO $pdo PDO database connection.
     * @param string $tabla Name of the database table for CRUD operations.
     */
    public function __construct($pdo, $tabla) {
        $this->pdo = $pdo;
        $this->tabla = $tabla;
    }

    /**
     * Inserts a new record into the associated table using the provided data.
     *
     * @param array $datos Associative array where keys are column names and values are the corresponding data to insert.
     * @return bool True on successful insertion, false otherwise.
     */
    public function insertar($datos) {
        $columnas = implode(', ', array_keys($datos));
        $valores = ':' . implode(', :', array_keys($datos));

        $sql = "INSERT INTO {$this->tabla} ($columnas) VALUES ($valores)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($datos);
    }

    /****
     * Retrieves all records from the associated database table.
     *
     * @return array List of records as associative arrays.
     */
    public function obtenerTodos() {
        $sql = "SELECT * FROM {$this->tabla}";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /****
     * Retrieves a single record from the table by the specified ID column.
     *
     * @param mixed $id The value to match in the ID column.
     * @param string $columnaId The name of the ID column to search by. Defaults to 'id'.
     * @return array|null The fetched record as an associative array, or null if not found.
     */
    public function obtenerPorId($id, $columnaId = 'id') {
        $sql = "SELECT * FROM {$this->tabla} WHERE $columnaId = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Updates a record in the table by ID with the provided data.
     *
     * @param mixed $id The value of the ID column identifying the record to update.
     * @param array $datos Associative array of column-value pairs to update.
     * @param string $columnaId The name of the ID column (defaults to 'id').
     * @return bool True on success, false on failure.
     */
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

    /****
     * Deletes a record from the table by the specified ID column and value.
     *
     * @param mixed $id Value of the record's identifier to delete.
     * @param string $columnaId Name of the column to use as the identifier (defaults to 'id').
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function eliminar($id, $columnaId = 'id') {
        $sql = "DELETE FROM {$this->tabla} WHERE $columnaId = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
