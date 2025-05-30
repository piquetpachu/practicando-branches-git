<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rb.php';
R::setup( 'mysql:host=localhost;dbname=aplicacion_alumnos', 'root', '' );

$router = new \Bramus\Router\Router();

$router->options('.*', function() { 
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type', 'Authorization', 'X-Requested-With');
    exit();
});

$router->get('/', function() {
    header('Access-Control-Allow-Origin: *');
    header(('Content-Type: application/json'));
    $alumnos = R::find('alumnos');
    echo json_encode(R::exportAll($alumnos));
});

$router->post('/', function() {
    $data = json_decode(file_get_contents('php://input'), true);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type : application/json');
    print_r($data);

});

$router->run();