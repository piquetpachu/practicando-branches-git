<?php
header('Content-Type: application/json');
include __DIR__ . '/../../conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener todas las categorías
        $sql = "SELECT * FROM categorias";
        $stmt = $conexion->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($categorias);
        break;

    case 'POST':
        // Agregar nueva categoría
        $nombre = $_POST['nombre'] ?? '';

        if (!empty($nombre)) {
            $sql = "INSERT INTO categorias (nombre) VALUES (?)";
            $stmt = $conexion->prepare($sql);
            $success = $stmt->execute([$nombre]);

            echo json_encode(["success" => $success]);
        } else {
            echo json_encode(["error" => "El nombre es obligatorio"]);
        }
        break;

    case 'PUT':
        // Editar categoría existente
        parse_str(file_get_contents("php://input"), $putData);

        $id = $_GET['id'] ?? null;
        $nombre = $putData['nombre'] ?? '';

        if ($id && !empty($nombre)) {
            $sql = "UPDATE categorias SET nombre = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $success = $stmt->execute([$nombre, $id]);

            echo json_encode(["success" => $success]);
        } else {
            echo json_encode(["error" => "ID y nombre requeridos"]);
        }
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
