<?php
header("Content-Type: application/json");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

if (isset($uri[1]) && $uri[1] === 'servicios') {
    require __DIR__ . '/routes/servicios.php';
} else {
    http_response_code(404);
    echo json_encode(["mensaje" => "Ruta no encontrada"]);
}
