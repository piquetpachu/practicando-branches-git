<?php
require_once (__DIR__ . '/../config/conexion.php');
require_once (__DIR__ . '/../model/consultas.php');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/estetica/practicando-branches-git/alquilerapi/';
$ruta = str_replace($basePath, '', $uri);

$partes = explode('/', trim($ruta, '/'));
$recurso = $partes[0] ?? null;

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($recurso) {
    case 'departamentos_libres':
        if ($metodo === 'GET') {
            obtenerDepartamentosLibres($pdo);
        }
        break;
        
    case 'alquileres_activos':
        if ($metodo === 'GET') {
            echo json_encode(alquileresActivos($pdo));
        }
        break;
        
    case 'inquilinos_deuda':
        if ($metodo === 'GET') {
            echo json_encode(inquilinosConDeuda($pdo));
        }
        break;

    case 'ingresos_dia':
        if ($metodo === 'GET') {
            $inicio = $_GET['inicio'] ?? null;
            $fin = $_GET['fin'] ?? null;
            if ($inicio && $fin) {
                echo json_encode(ingresosPorDia($pdo, $inicio, $fin));
            } else {
                echo json_encode(["error" => "Faltan parámetros 'inicio' o 'fin'"]);
            }
        }
        break;

    case 'registrar_inquilino':
        if ($metodo === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            if ($datos) {
                $id = registrarInquilino($pdo, $datos);
                echo json_encode(["mensaje" => "Inquilino registrado", "id" => $id]);
            } else {
                echo json_encode(["error" => "Datos inválidos"]);
            }
        }
        break;

    default:
        echo json_encode(["error" => "Ruta no válida"]);
        break;
}
