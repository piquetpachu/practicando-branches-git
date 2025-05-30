<?php
require_once 'clases/Persona.php';

require_once 'api/CrudGenerico.php';
require_once 'api/conexion.php';
$metodo = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');
$servicios = new CrudGenerico((new conexion())->getConnection(), 'servicio');
switch ($metodo) {
    case 'GET':
        $resultados = $servicios->obtenerTodos();
        echo json_encode($resultados);
        break;
    case 'POST':
        $datos = json_decode(file_get_contents('php://input'), true);
        if ($servicios->insertar($datos)) {
            echo json_encode(['status' => 'success', 'message' => 'Servicio agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el servicio']);
        }
        break;
    case 'PUT':
        $datos = json_decode(file_get_contents('php://input'), true);
        $id = $datos['id'];
        unset($datos['id']);
        if ($servicios->actualizar($id, $datos)) {
            echo json_encode(['status' => 'success', 'message' => 'Servicio actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el servicio']);
        }
        break;
    case 'DELETE':
        parse_str(file_get_contents("php://input"), $datos);
        if ($servicios->eliminar($datos['id'])) {
            echo json_encode(['status' => 'success', 'message' => 'Servicio eliminado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el servicio']);
        }
        break;
    default:
        echo "Método no reconocido<br>";
}




