<?php 
class Persona {
    public $nombre, $edad;
    public $apellidoPadre, $apellidoMadre;
    /**
     * Sets the person's first name, converting it to lowercase.
     *
     * @param string $nombre The first name to assign.
     */
    public function SetNombre($nombre){
        $this->nombre = strtolower($nombre);
    }
    /**
     * Returns the person's first name with each word capitalized.
     *
     * @return string The formatted first name.
     */
    public function GetNombre(){
        return ucwords($this->nombre);
    }
    /**
     * Sets the paternal and maternal surnames in lowercase.
     *
     * @param string $apellidoPadre The paternal surname.
     * @param string $apellidoMadre The maternal surname.
     */
    public function SetApellido($apellidoPadre,$apellidoMadre){
        $this->apellidoPadre = strtolower($apellidoPadre);
        $this->apellidoMadre = strtolower($apellidoMadre);
    }
    /**
     * Returns the full surname with each word capitalized.
     *
     * Combines the paternal and maternal surnames, separated by a space, and capitalizes the first letter of each word.
     *
     * @return string The formatted full surname.
     */
    public function GetApellido(){
        return ucwords($this->apellidoPadre . " " . $this->apellidoMadre);
    }
}

class Peruano extends Persona {
    public $departamento, $ciudad;

    /**
     * Sets the parental surnames using the base class method and outputs a confirmation message.
     *
     * @param string $apellidoPadre Father's surname.
     * @param string $apellidoMadre Mother's surname.
     */
    public function SetApellido($apellidoPadre, $apellidoMadre)
    {
        parent::SetApellido($apellidoPadre, $apellidoMadre);
        echo "Los apellidos se asignaron con Exito!! <br>";
    }
}

class Chileno extends Persona {
    public $region, $comuna;

    /****
     * Sets the surnames by assigning the lowercase of the mother's surname to the father's surname property and vice versa.
     *
     * @param string $apellidoPadre The father's surname.
     * @param string $apellidoMadre The mother's surname.
     */
    public function SetApellido($apellidoPadre,$apellidoMadre){
        $this->apellidoPadre = strtolower($apellidoMadre);
        $this->apellidoMadre = strtolower($apellidoPadre);
    }
}

class Argentino extends Persona {
    public $provincia, $ciudad;
}