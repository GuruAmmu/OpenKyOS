<?php
namespace reportes\plantillaResultadoCom\entidad;
if (!isset($GLOBALS["autorizado"])) {
    include "index.php";
    exit();
}
class Redireccionador {
    public static function redireccionar($opcion, $valor = "") {

        $miConfigurador = \Configurador::singleton();

        switch ($opcion) {

            case "ErrorFormatoArchivo":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorFormatoArchivo';
                break;

            case "ErrorArchivoNoValido":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorArchivoNoValido';
                break;

            case "ErrorCargarArchivo":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorCargarArchivo';
                break;

            case "ErrorNoCargaInformacionHojaCalculo":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorCargarInformacion';
                break;

            case "ErrorInformacionCargar":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorInformacionCargar';
                $variable .= '&log=' . $valor;
                break;

            case "ExitoInformacion":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=exitoInformacion';
                break;

            case "ErrorCreacionContratos":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorCreacionContratos';
                break;

            case "ExitoRegistroProceso":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=exitoRegistroProceso';
                $variable .= '&proceso=' . $valor;
                break;

            case "ErrorRegistroProceso":
                $variable = 'pagina=plantillaResultadoCom';
                $variable .= '&mensajeModal=errorRegistroProceso';
                break;
                
                case "ErrorActualizacion":
                	$variable = 'pagina=plantillaResultadoCom';
                	$variable .= '&mensajeModal=errorActualizacion';
                	break;

        }
        foreach ($_REQUEST as $clave => $valor) {
            unset($_REQUEST[$clave]);
        }

        $url = $miConfigurador->configuracion["host"] . $miConfigurador->configuracion["site"] . "/index.php?";
        $enlace = $miConfigurador->configuracion['enlace'];
        $variable = $miConfigurador->fabricaConexiones->crypto->codificar($variable);
        $_REQUEST[$enlace] = $enlace . '=' . $variable;
        $redireccion = $url . $_REQUEST[$enlace];

        echo "<script>location.replace('" . $redireccion . "')</script>";

        exit();
    }
}
?>
