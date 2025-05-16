<?php
include '../conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['titulo'], $data['descripcion'], $data['precio'])) {
    echo json_encode(["error" => "Faltan datos"]);
    exit;
}

$sql = $conexion->prepare("INSERT INTO servicios (titulo, descripcion, precio) VALUES (?, ?, ?)");
$ok = $sql->execute([$data['titulo'], $data['descripcion'], $data['precio']]);

echo json_encode(["success" => $ok]);
