<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';

$database = new Database();
$db = $database->conectar();
$controller = new UsuarioController($db);

$uri = explode('/', $_SERVER['REQUEST_URI']);
$input = json_decode(file_get_contents("php://input"), true);
$id = is_numeric(end($uri)) ? intval(end($uri)) : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->listarUsuarios();
        break;

    case 'POST':
        if (in_array("login", $uri)) {
            $controller->login($input);
        } elseif (in_array("logout", $uri)) {
            session_start();
            session_destroy();
            echo json_encode(["mensaje" => "Sesión cerrada"]);
        } else {
            $controller->registrar($input);
        }
        break;

    case 'DELETE':
        if ($id) {
            $controller->eliminarUsuario($id);
        } else {
            http_response_code(400);
            echo json_encode(["mensaje" => "ID requerido"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["mensaje" => "Método no permitido"]);
        break;
}
