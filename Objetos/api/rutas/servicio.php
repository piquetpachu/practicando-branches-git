<?php
require_once '../modelo/servicio.php';
switch ($metodo){
    case 'GET':
        echo "Método GET utilizado<br>";
        echo "Método HTTP utilizado: " . $metodo . "<br>";
        $servicio = new servicio();
        $servicios = $servicio->getServicios();
        echo $servicios;
        echo '<br>';
        break;
    case 'POST':
        echo "Método POST utilizado<br>";
        $servicio = new servicio();
        $servicios = $servicio->PostServicio('Terapia de Masaje', 50.00);
        echo $servicios;
        $serviciosArray = json_decode($servicios, true);
        if ($serviciosArray['status'] === 'success') {
            echo "Servicio agregado: " . $serviciosArray['message'] . "<br>";
        } else {
            echo "Error al agregar el servicio: " . $serviciosArray['message'] . "<br>";
        }

        echo '<br>';
        break;
    case 'PUT':
        echo "Método PUT utilizado<br>";
        $servicio = new servicio();
        $servicios = $servicio->PutServicio(1, 'Terapia de Masaje Actualizada', 60.00);
        echo $servicios;
        $serviciosArray = json_decode($servicios, true);
        if ($serviciosArray['status'] === 'success') {
            echo "Servicio actualizado: " . $serviciosArray['message'] . "<br>";
        } else {
            echo "Error al actualizar el servicio: " . $serviciosArray['message'] . "<br>";
        }
        echo '<br>';
        break;
    case 'DELETE':
        echo "Método DELETE utilizado<br>";
        $servicio = new servicio();
        $servicios = $servicio->DeleteServicio(14);
        echo $servicios;
        $serviciosArray = json_decode($servicios, true);
        if ($serviciosArray['status'] === 'success') {
            echo "Servicio eliminado: " . $serviciosArray['message'] . "<br>";
        } else {
            echo "Error al eliminar el servicio: " . $serviciosArray['message'] . "<br>";
        }
        break;
    default:
        echo "Método no reconocido<br>";
    }