<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(["mensaje" => "No estás logueado"]);
    exit;
}
if ($_SESSION['usuario']['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["mensaje" => "Acceso denegado: solo administradores"]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../controllers/ServicioController.php';

$database = new Database();
$db = $database->conectar();
$controller = new ServicioController($db);

// Detectar ID en la URL si existe
$requestUri = explode('/', $_SERVER['REQUEST_URI']);
$id = isset($requestUri[count($requestUri) - 1]) && is_numeric(end($requestUri)) ? intval(end($requestUri)) : null;

// Leer el cuerpo JSON
$input = json_decode(file_get_contents("php://input"), true);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->obtenerServicios();
        break;

    case 'POST':
        $controller->crearServicio($input);
        break;

    case 'PUT':
        if ($id) {
            $controller->actualizarServicio($id, $input);
        } else {
            http_response_code(400);
            echo json_encode(["mensaje" => "ID requerido para actualizar"]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $controller->eliminarServicio($id);
        } else {
            http_response_code(400);
            echo json_encode(["mensaje" => "ID requerido para eliminar"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["mensaje" => "Método no permitido"]);
        break;
}
