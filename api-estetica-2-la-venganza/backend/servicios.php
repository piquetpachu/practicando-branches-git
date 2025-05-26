<?php



// si se instaló con composer

// o si se instaló manualmente mediante un archivo zip
// require 'flight/Flight.php';


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

