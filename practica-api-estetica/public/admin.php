
<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../api/login/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Admin - Agregar Servicio</title>
  </head>
  <body>
    <div id="nav-container"></div>
    <h2>Agregar servicio</h2>
    <form id="formServicio">
      <input type="text" name="titulo" placeholder="Título" required /><br />
      <textarea name="descripcion" placeholder="Descripción" required></textarea
      ><br />
      <input
        type="number"
        name="precio"
        step="0.01"
        placeholder="Precio"
        required
      /><br />
      <button type="submit">Agregar</button>
    </form>

    <p id="mensaje"></p>
    <h2>Servicios actuales</h2>
    <ul id="listaServicios"></ul>

    

      <div id="footer-container"></div>
    <script src="js/agregarServicio.js"></script>
    <script src="js/listarEditarEliminar.js"></script>
    <script src="js/incluirNavFooter.js"></script>
  </body>
</html>
