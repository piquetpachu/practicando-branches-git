<?php
// Agregar esto al principio de tu archivo backend/index.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Preflight request
}


// si se instaló con composer
require 'vendor/autoload.php';
// o si se instaló manualmente mediante un archivo zip
// require 'flight/Flight.php';
flight::register('db', 'PDO', array('mysql:host=localhost;dbname=gestion_estetica', 'root', ''));

Flight::route('GET /', function() {
  $sentencia = Flight::db()->prepare("SELECT * FROM servicio");
  $sentencia->execute();
$servicios = $sentencia->fetchAll(PDO::FETCH_ASSOC);
  Flight::json($servicios);
});


Flight::route('POST /crear', function(){
    $titulo = Flight::request()->data->titulo;
    $descripcion = Flight::request()->data->descripcion;
    $precio = Flight::request()->data->precio;
    $descuento = Flight::request()->data->descuento;
    $imagen = Flight::request()->data->imagen;
    $sentencia = Flight::db()->prepare("INSERT INTO servicio (titulo, descripcion, precio, descuento, imagen) VALUES (:titulo, :descripcion, :precio, :descuento, :imagen)");
    $sentencia->bindParam(':titulo', $titulo);
    $sentencia->bindParam(':descripcion', $descripcion);
    $sentencia->bindParam(':precio', $precio);
    $sentencia->bindParam(':descuento', $descuento);
    $sentencia->bindParam(':imagen', $imagen);
    $sentencia->execute();
    Flight::json(['mensaje' => 'servicio creado']);
});

Flight::route('DELETE /borrar', function(){
    $id = Flight::request()->data->id;
    $sentencia = Flight::db()->prepare("DELETE FROM servicio WHERE id = :id");
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();
    Flight::json(['mensaje' => 'servicio borrado']);
});

Flight::route('PUT /actualizar', function(){
    $id = Flight::request()->data->id;
    $titulo = Flight::request()->data->titulo;
    $descripcion = Flight::request()->data->descripcion;
    $precio = Flight::request()->data->precio;
    $descuento = Flight::request()->data->descuento;
    $imagen = Flight::request()->data->imagen;
    $sentencia = Flight::db()->prepare("UPDATE servicio SET titulo = :titulo, descripcion = :descripcion, precio = :precio, descuento = :descuento, imagen = :imagen WHERE id = :id");
    $sentencia->bindParam(':id', $id);
    $sentencia->bindParam(':titulo', $titulo);
    $sentencia->bindParam(':descripcion', $descripcion);
    $sentencia->bindParam(':precio', $precio);
    $sentencia->bindParam(':descuento', $descuento);
    $sentencia->bindParam(':imagen', $imagen);
    $sentencia->execute();
    Flight::json(['mensaje' => 'servicio actualizado']);
});

//ver un solo registro
Flight::route('GET /ver/@id', function($id){
    $sentencia = Flight::db()->prepare("SELECT * FROM servicio WHERE id = :id");
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();
    $servicios = $sentencia->fetchAll(PDO::FETCH_ASSOC);
    Flight::json($servicios);
});
// if (class_exists('Flight')) {
//   echo "Flight cargado correctamente";
// } else {
//   echo "Flight no encontrado";
// }

Flight::start();