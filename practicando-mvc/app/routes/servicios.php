<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../controllers/ServicioController.php';

$database = new Database();
$db = $database->conectar();

$controller = new ServicioController($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller->obtenerServicios();
}
