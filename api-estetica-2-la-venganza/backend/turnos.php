<?php

// Obtener todos los turnos (con info de usuario y servicio)
Flight::route('GET /turnos', function () {
    try {
        $stmt = Flight::db()->prepare("
            SELECT 
                turno.id,
                turno.fecha_hora,
                usuario.nombre AS usuario,
                servicio.titulo AS servicio,
                servicio.precio,
                servicio.descuento
            FROM turno
            JOIN usuario ON turno.usuario_id = usuario.id
            JOIN servicio ON turno.servicio_id = servicio.id
            ORDER BY turno.fecha_hora DESC
        ");
        $stmt->execute();
        Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        Flight::json(['error' => $e->getMessage()], 500);
    }
});

// Obtener un turno por ID
Flight::route('GET /turnos/@id', function ($id) {
    try {
        $stmt = Flight::db()->prepare("SELECT * FROM turno WHERE id = ?");
        $stmt->execute([$id]);
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($turno) {
            Flight::json($turno);
        } else {
            Flight::json(['error' => 'Turno no encontrado'], 404);
        }
    } catch (PDOException $e) {
        Flight::json(['error' => $e->getMessage()], 500);
    }
});

// Crear un nuevo turno
Flight::route('POST /turnos', function () {
    $data = Flight::request()->data;

    if (!isset($data['fecha_hora'], $data['usuario_id'], $data['servicio_id'])) {
        Flight::json(['error' => 'Faltan campos obligatorios'], 400);
        return;
    }

    try {
        $stmt = Flight::db()->prepare("INSERT INTO turno (fecha_hora, usuario_id, servicio_id) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['fecha_hora'],
            $data['usuario_id'],
            $data['servicio_id']
        ]);
        Flight::json(['message' => 'Turno creado con éxito', 'id' => Flight::db()->lastInsertId()]);
    } catch (PDOException $e) {
        Flight::json(['error' => $e->getMessage()], 500);
    }
});

// Actualizar un turno
Flight::route('PUT /turnos/@id', function ($id) {
    $data = Flight::request()->data;

    try {
        $stmt = Flight::db()->prepare("UPDATE turno SET fecha_hora = ?, usuario_id = ?, servicio_id = ? WHERE id = ?");
        $stmt->execute([
            $data['fecha_hora'],
            $data['usuario_id'],
            $data['servicio_id'],
            $id
        ]);
        Flight::json(['message' => 'Turno actualizado']);
    } catch (PDOException $e) {
        Flight::json(['error' => $e->getMessage()], 500);
    }
});

// Eliminar un turno
Flight::route('DELETE /turnos/@id', function ($id) {
    try {
        $stmt = Flight::db()->prepare("DELETE FROM turno WHERE id = ?");
        $stmt->execute([$id]);
        Flight::json(['message' => 'Turno eliminado']);
    } catch (PDOException $e) {
        Flight::json(['error' => $e->getMessage()], 500);
    }
});
