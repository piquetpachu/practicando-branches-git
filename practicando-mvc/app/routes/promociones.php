<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../controllers/PromocionController.php';

$database = new Database();
$db = $database->conectar();
$controller = new PromocionController($db);

$requestUri = explode('/', $_SERVER['REQUEST_URI']);
$id = is_numeric(end($requestUri)) ? intval(end($requestUri)) : null;

$input = json_decode(file_get_contents("php://input"), true);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->obtenerPromociones();
        break;

    case 'POST':
        $controller->crearPromocion($input);
        break;

    case 'PUT':
        if ($id) {
            $controller->actualizarPromocion($id, $input);
        } else {
            http_response_code(400);
            echo json_encode(["mensaje" => "ID requerido para actualizar"]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $controller->eliminarPromocion($id);
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
