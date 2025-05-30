<?php
class conexion {
    private $conn;

    /**
     * Initializes a new PDO connection to the 'gestion_estetica' MySQL database.
     *
     * Attempts to connect to the database on localhost using the root user with no password.
     * Sets the PDO error mode to throw exceptions. If the connection fails, outputs an error message.
     */
    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=localhost;dbname=gestion_estetica", "root", "");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }
    }

    /**
     * Returns the current PDO database connection instance.
     *
     * @return PDO The active PDO connection.
     */
    public function getConnection() {
        return $this->conn;
    }
}