<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rb.php';
R::setup( 'mysql:host=localhost;dbname=aplicacion_alumnos', 'root', '' );

$router = new \Bramus\Router\Router();
$router->get('/', function() {
    header('Access-Control-Allow-Origin: *');
    header(('Content-Type: application/json'));
    $alumnos = R::find('alumnos');
    echo json_encode(R::exportAll($alumnos));
});

$router->run();