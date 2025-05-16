<?php
include '../conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['titulo'], $data['descripcion'], $data['precio'])) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$sql = $conexion->prepare("UPDATE servicios SET titulo = ?, descripcion = ?, precio = ? WHERE id = ?");
$result = $sql->execute([$data['titulo'], $data['descripcion'], $data['precio'], $data['id']]);

echo json_encode(["success" => $result]);
