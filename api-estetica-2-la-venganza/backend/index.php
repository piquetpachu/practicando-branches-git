<?php


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

Flight::route('/json', function() {
  Flight::json(['hello' => 'world']);
});
// if (class_exists('Flight')) {
//   echo "Flight cargado correctamente";
// } else {
//   echo "Flight no encontrado";
// }

Flight::start();