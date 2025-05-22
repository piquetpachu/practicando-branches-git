<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

$database = new Database();
$db = $database->conectar();
$controller = new CategoriaController($db);

// Extraer ID de la URL si existe
$requestUri = explode('/', $_SERVER['REQUEST_URI']);
$id = is_numeric(end($requestUri)) ? intval(end($requestUri)) : null;

$input = json_decode(file_get_contents("php://input"), true);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->obtenerCategorias();
        break;

    case 'POST':
        $controller->crearCategoria($input);
        break;

    case 'PUT':
        if ($id) {
            $controller->actualizarCategoria($id, $input);
        } else {
            http_response_code(400);
            echo json_encode(["mensaje" => "ID requerido para actualizar"]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $controller->eliminarCategoria($id);
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
