<?php
session_start(); // al inicio del archivo

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarUsuarios() {
        $usuario = new Usuario($this->conn);
        $resultado = $usuario->listar();

        $usuarios = [];
        while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $usuarios[] = $fila;
        }

        echo json_encode($usuarios);
    }

    public function registrar($data) {
        if (!isset($data['usuario']) || !isset($data['contrasena'])) {
            http_response_code(400);
            echo json_encode(["mensaje" => "Faltan campos"]);
            return;
        }

        $usuario = new Usuario($this->conn);
        $usuario->usuario = $data['usuario'];
        $usuario->contrasena = $data['contrasena'];
        $usuario->rol = $data['rol'] ?? 'cliente';

        if ($usuario->crear()) {
            echo json_encode(["mensaje" => "Usuario registrado"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al registrar usuario"]);
        }
    }


public function login($data) {
    if (!isset($data['usuario']) || !isset($data['contrasena'])) {
        http_response_code(400);
        echo json_encode(["mensaje" => "Faltan campos"]);
        return;
    }

    $usuario = new Usuario($this->conn);
    $usuario->usuario = $data['usuario'];
    $registro = $usuario->login();

    if ($registro && password_verify($data['contrasena'], $registro['contrasena'])) {
        unset($registro['contrasena']);

        $_SESSION['usuario'] = $registro; // Guardamos al usuario

        echo json_encode(["mensaje" => "Login correcto", "usuario" => $registro]);
    } else {
        http_response_code(401);
        echo json_encode(["mensaje" => "Usuario o contraseña incorrectos"]);
    }
}

    public function eliminarUsuario($id) {
        $usuario = new Usuario($this->conn);
        $usuario->id = $id;

        if ($usuario->eliminar()) {
            echo json_encode(["mensaje" => "Usuario eliminado"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error al eliminar usuario"]);
        }
    }
}
