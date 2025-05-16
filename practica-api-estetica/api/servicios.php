<?php
include '../conexion.php';
header('Content-Type: application/json');

$servicios = $conexion->query("SELECT * FROM servicios")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($servicios);
