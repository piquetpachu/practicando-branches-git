<?php
require_once '../config/conexion.php';
require_once '../model/consultas.php';
$metodo = $_SERVER['REQUEST_METHOD'];
switch ($metodo) {
    case 'GET':
        // echo "Método GET no implementado";
        obtenerDepartamentosLibres($pdo);
        
        break;
    case 'POST':
        echo "metodo post";
        break;
    default:
        # code...
        break;
}