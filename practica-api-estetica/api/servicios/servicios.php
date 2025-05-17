<?php
include __DIR__ . '/../../conexion.php';
header('Content-Type: application/json');

$sql = "SELECT 
  s.titulo,
  s.descripcion,
  s.precio,
  s.imagen,
  c.nombre
FROM servicios s
LEFT JOIN categorias c ON c.id = s.id_categoria
";
$servicios = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($servicios);
