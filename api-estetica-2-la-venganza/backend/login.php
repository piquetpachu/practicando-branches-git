<?php
require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;



$key = "CLAVE_SECRETA_123"; // usá algo más seguro en producción

// Login con JWT
Flight::route('POST /login', function () use ($key) {
    $db = Flight::db();
    $data = Flight::request()->data;
    $usuario = $data['usuario'] ?? '';
    $contrasena = $data['contrasena'] ?? '';

    // Buscar admin
    $stmt = $db->prepare("SELECT * FROM admin WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($contrasena, $admin['contrasena'])) {
        $payload = [
            "id" => $admin['id'],
            "rol" => "admin",
            "usuario" => $admin['usuario'],
            "exp" => time() + 3600 // 1 hora de validez
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        Flight::json(["token" => $jwt]);
        return;
    }

    // Buscar usuario (sin contraseña aún)
    $stmt = $db->prepare("SELECT * FROM usuario WHERE nombre = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $contrasena === 'usuario123') {
        $payload = [
            "id" => $user['id'],
            "rol" => "usuario",
            "usuario" => $user['nombre'],
            "exp" => time() + 3600
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        Flight::json(["token" => $jwt]);
        return;
    }

    Flight::halt(401, json_encode(["error" => "Credenciales inválidas"]));
});

// Ruta protegida como ejemplo
Flight::route('GET /protegido', function () use ($key) {
    $authHeader = Flight::request()->getHeader("Authorization");
    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        Flight::halt(401, json_encode(["error" => "No autorizado"]));
    }

    $jwt = str_replace("Bearer ", "", $authHeader);
    try {
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        Flight::json(["msg" => "Bienvenido " . $decoded->usuario, "rol" => $decoded->rol]);
    } catch (Exception $e) {
        Flight::halt(401, json_encode(["error" => "Token inválido"]));
    }
});
