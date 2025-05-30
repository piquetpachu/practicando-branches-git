<?php 
class Persona {
    public $nombre, $edad;
    public $apellidoPadre, $apellidoMadre;
    public function SetNombre($nombre){
        $this->nombre = strtolower($nombre);
    }
    public function GetNombre(){
        return ucwords($this->nombre);
    }
    public function SetApellido($apellidoPadre,$apellidoMadre){
        $this->apellidoPadre = strtolower($apellidoPadre);
        $this->apellidoMadre = strtolower($apellidoMadre);
    }
    public function GetApellido(){
        return ucwords($this->apellidoPadre . " " . $this->apellidoMadre);
    }
}

class Peruano extends Persona {
    public $departamento, $ciudad;

    public function SetApellido($apellidoPadre, $apellidoMadre)
    {
        parent::SetApellido($apellidoPadre, $apellidoMadre);
        echo "Los apellidos se asignaron con Exito!! <br>";
    }
}

class Chileno extends Persona {
    public $region, $comuna;

    public function SetApellido($apellidoPadre,$apellidoMadre){
        $this->apellidoPadre = strtolower($apellidoMadre);
        $this->apellidoMadre = strtolower($apellidoPadre);
    }
}

class Argentino extends Persona {
    public $provincia, $ciudad;
}