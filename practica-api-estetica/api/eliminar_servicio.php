<?php
include '../conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["error" => "ID faltante"]);
    exit;
}

$sql = $conexion->prepare("DELETE FROM servicios WHERE id = ?");
$result = $sql->execute([$data['id']]);

echo json_encode(["success" => $result]);
