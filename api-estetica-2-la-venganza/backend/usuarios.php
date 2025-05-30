<?php
// Login genérico
Flight::route('POST /login', function () {
    $db = Flight::db();
    $data = Flight::request()->data;

    $usuario = $data['usuario'] ?? '';
    $contrasena = $data['contrasena'] ?? '';

    // Buscar en admin
    $stmt = $db->prepare("SELECT * FROM admin WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($contrasena, $admin['contrasena'])) {
        Flight::json(['rol' => 'admin', 'id' => $admin['id'], 'usuario' => $admin['usuario']]);
        return;
    }

    // Buscar en usuarios (sin hash)
    $stmt = $db->prepare("SELECT * FROM usuario WHERE nombre = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $contrasena === 'usuario123') { // Cambiar lógica si agregás contraseña real
        Flight::json(['rol' => 'usuario', 'id' => $user['id'], 'nombre' => $user['nombre']]);
        return;
    }

    Flight::halt(401, json_encode(['error' => 'Credenciales inválidas']));
});

// Registro de nuevos usuarios (tabla usuario)
Flight::route('POST /register', function () {
    $db = Flight::db();
    $data = Flight::request()->data;

    $nombre = $data['nombre'] ?? '';
    $email = $data['email'] ?? '';

    if (empty($nombre) || empty($email)) {
        Flight::halt(400, json_encode(['error' => 'Faltan campos requeridos']));
    }

    $stmt = $db->prepare("INSERT INTO usuario (nombre, email) VALUES (?, ?)");
    $stmt->execute([$nombre, $email]);

    Flight::json(['success' => true, 'id' => $db->lastInsertId()]);
});
