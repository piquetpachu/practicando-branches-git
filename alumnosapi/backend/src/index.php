<?php
 require_once __DIR__ . '/../vendor/autoload.php';

$router = new \Bramus\Router\Router();

$router->get('/', function() {
    echo "hola soy una api ";
});

$router->run();