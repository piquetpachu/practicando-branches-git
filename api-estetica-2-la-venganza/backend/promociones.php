<?php

// GET todas las promociones
Flight::route('GET /promociones', function () {
    $stmt = Flight::db()->prepare("SELECT * FROM promocion");
    $stmt->execute();
    Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
});

// GET una promoción por id
Flight::route('GET /promociones/@id', function ($id) {
    $stmt = Flight::db()->prepare("SELECT * FROM promocion WHERE id = ?");
    $stmt->execute([$id]);
    Flight::json($stmt->fetch(PDO::FETCH_ASSOC));
});

// POST crear promoción
Flight::route('POST /promociones', function () {
    $data = Flight::request()->data;
    $stmt = Flight::db()->prepare("INSERT INTO promocion (titulo, descripcion, descuento, imagen) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data->titulo, $data->descripcion, $data->descuento, $data->imagen]);
    Flight::json(['mensaje' => 'Promoción creada correctamente']);
});

// PUT actualizar promoción
Flight::route('PUT /promociones/@id', function ($id) {
    $data = Flight::request()->data;
    $stmt = Flight::db()->prepare("UPDATE promocion SET titulo = ?, descripcion = ?, descuento = ?, imagen = ? WHERE id = ?");
    $stmt->execute([$data->titulo, $data->descripcion, $data->descuento, $data->imagen, $id]);
    Flight::json(['mensaje' => 'Promoción actualizada']);
});

// DELETE eliminar promoción
Flight::route('DELETE /promociones/@id', function ($id) {
    $stmt = Flight::db()->prepare("DELETE FROM promocion WHERE id = ?");
    $stmt->execute([$id]);
    Flight::json(['mensaje' => 'Promoción eliminada']);
});
