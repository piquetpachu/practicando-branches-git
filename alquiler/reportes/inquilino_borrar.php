<?php
require_once 'conexion.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM inquilinos WHERE id = ?");
$stmt->execute([$id]);
header('Location: inquilinos_lista.php');
