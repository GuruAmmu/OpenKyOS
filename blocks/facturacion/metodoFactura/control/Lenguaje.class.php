<?php
namespace facturacion\metodoFactura;
if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

class Lenguaje {

    private $idioma;

    private $miConfigurador;

    private $nombreBloque;

    public function __construct() {

        $this->miConfigurador = \Configurador::singleton();

        $esteBloque = $this->miConfigurador->getVariableConfiguracion("esteBloque");
        $this->nombreBloque = $esteBloque["nombre"];
        $this->ruta = $this->miConfigurador->getVariableConfiguracion("rutaBloque");

        if ($this->miConfigurador->getVariableConfiguracion("idioma")) {
            $idioma = $this->miConfigurador->getVariableConfiguracion("idioma");
        } else {
            $idioma = "es_es";
        }

        include $this->ruta . "frontera/locale/" . $idioma . "/Mensaje.php";

    }

    public function getCadena($opcion = "") {

        $opcion = trim($opcion);
        if (isset($this->idioma[$opcion])) {
            return $this->idioma[$opcion];
        } else {
            return $this->idioma["noDefinido"];
        }

    }
}

?>

