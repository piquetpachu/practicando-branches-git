<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Preflight para CORS
}
require 'vendor/autoload.php';
require 'db.php';             // Conexión DB
require 'servicios.php';      // Rutas de servicios
require 'usuarios.php';       // Si ya lo tienes
require 'promociones.php';    // Si ya lo tienes
require 'estadisticas.php';   // Si ya lo tienes
require 'login.php';    // Si ya lo tienes
require 'turnos.php';    // Si ya lo tienes

Flight::start();
