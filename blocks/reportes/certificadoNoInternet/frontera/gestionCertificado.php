<?php

namespace reportes\certificadoNoInternet\frontera;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

class GestionarContrato {
    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public $miSql;
    public $ruta;
    public $rutaURL;
    public function __construct($lenguaje, $formulario, $sql) {
        $this->miConfigurador = \Configurador::singleton();

        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');

        $this->lenguaje = $lenguaje;

        $this->miFormulario = $formulario;

        $this->miSql = $sql;

        $esteBloque = $this->miConfigurador->configuracion['esteBloque'];

        $this->ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");

        if (!isset($esteBloque["grupo"]) || $esteBloque["grupo"] == "") {
            $ruta .= "/blocks/" . $esteBloque["nombre"] . "/";
            $this->rutaURL .= "/blocks/" . $esteBloque["nombre"] . "/";
        } else {
            $this->ruta .= "/blocks/" . $esteBloque["grupo"] . "/" . $esteBloque["nombre"] . "/";
            $this->rutaURL .= "/blocks/" . $esteBloque["grupo"] . "/" . $esteBloque["nombre"] . "/";
        }
    }
    public function formulario() {

        $esteBloque = $this->miConfigurador->getVariableConfiguracion("esteBloque");
        $miPaginaActual = $this->miConfigurador->getVariableConfiguracion("pagina");
        // Conexion a Base de Datos
        $conexion = "interoperacion";
        $esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        // Consulta información

        $cadenaSql = $this->miSql->getCadenaSql('consultaInformacionCertificado');
        $infoCertificado = $esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        //var_dump($infoCertificado);

        // Rescatar los datos de este bloque

        // ---------------- SECCION: Parámetros Globales del Formulario ----------------------------------

        {
            $atributosGlobales['campoSeguro'] = 'true';
        }

        $_REQUEST['tiempo'] = time();
        // -------------------------------------------------------------------------------------------------

        // ---------------- SECCION: Parámetros Generales del Formulario ----------------------------------
        $esteCampo = $esteBloque['nombre'];
        $atributos['id'] = $esteCampo;
        $atributos['nombre'] = $esteCampo;
        // Si no se coloca, entonces toma el valor predeterminado 'application/x-www-form-urlencoded'
        $atributos['tipoFormulario'] = 'multipart/form-data';
        // Si no se coloca, entonces toma el valor predeterminado 'POST'
        $atributos['metodo'] = 'POST';
        // Si no se coloca, entonces toma el valor predeterminado 'index.php' (Recomendado)
        $atributos['action'] = 'index.php';
        $atributos['titulo'] = $this->lenguaje->getCadena($esteCampo);
        // Si no se coloca, entonces toma el valor predeterminado.
        $atributos['estilo'] = '';
        $atributos['marco'] = true;
        $tab = 1;
        // ---------------- FIN SECCION: de Parámetros Generales del Formulario ----------------------------

        // ----------------INICIAR EL FORMULARIO ------------------------------------------------------------
        $atributos['tipoEtiqueta'] = 'inicio';
        echo $this->miFormulario->formulario($atributos);

        {
            {
                $esteCampo = 'Agrupacion';
                $atributos['id'] = $esteCampo;
                $atributos['leyenda'] = "Certificado de No Internet";
                echo $this->miFormulario->agrupacion('inicio', $atributos);
                unset($atributos);

                {

                    $this->mensaje();

                    // ------------------Division para los botones-------------------------
                    $atributos["id"] = "botones";
                    $atributos["estilo"] = "marcoBotones";
                    $atributos["estiloEnLinea"] = "display:block;";
                    echo $this->miFormulario->division("inicio", $atributos);
                    unset($atributos);

                    // Acordar Roles

                    {

                        $url = $this->miConfigurador->getVariableConfiguracion("host");
                        $url .= $this->miConfigurador->getVariableConfiguracion("site");
                        $url .= "/index.php?";

                        // ------------------Division para los botones-------------------------
                        $atributos["id"] = "botones_sin";
                        $atributos["estilo"] = "marcoBotones";
                        $atributos["estiloEnLinea"] = "display:block;";
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);

                        {
                            $valorCodificado = "action=" . $esteBloque["nombre"];
                            $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
                            $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
                            $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
                            $valorCodificado .= "&id_beneficiario=" . $_REQUEST['id_beneficiario'];
                            $valorCodificado .= "&opcion=generarCertificacion";

                            $enlace = $this->miConfigurador->getVariableConfiguracion("enlace");
                            $cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url($valorCodificado, $enlace);

                            $urlpdfNoFirmas = $url . $cadena;

                            echo "<b><a id='link_b' href='" . $urlpdfNoFirmas . "'>Documento Certificado Sin Firma</a></b>";

                        }

                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);

                        // ------------------Division para los botones-------------------------
                        $atributos["id"] = "botones_pdf";
                        $atributos["estilo"] = "marcoBotones";
                        $atributos["estiloEnLinea"] = "display:block;";
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);

                        {
                            echo "<b><a id='link_a' target='_blank' href='" . $infoCertificado['ruta_documento'] . "'>Documento Certificado Con Firma</a></b>";
                        }

                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);

                        // ------------------Division para los botones-------------------------
                        $atributos["id"] = "botones_editar";
                        $atributos["estilo"] = "marcoBotones";
                        $atributos["estiloEnLinea"] = "display:block;";
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);

                        {

                            $valorCodificado = "actionBloque=" . $esteBloque["nombre"];
                            $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
                            $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
                            $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
                            $valorCodificado .= "&id_beneficiario=" . $_REQUEST['id_beneficiario'];
                            $valorCodificado .= "&opcion=editarInformacionCertificacion";

                            $enlace = $this->miConfigurador->getVariableConfiguracion("enlace");
                            $cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url($valorCodificado, $enlace);

                            $urlpdfNoFirmas = $url . $cadena;

                            echo "<b><a id='link_b' href='" . $urlpdfNoFirmas . "'>Editar Información Certficado</a></b>";

                        }

                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);

                    }

                    // ------------------Fin Division para los botones-------------------------
                    echo $this->miFormulario->division("fin");
                    unset($atributos);
                }
                echo $this->miFormulario->agrupacion('fin');
                unset($atributos);
            }

        }
        if (isset($_REQUEST['mensaje_modal'])) {

            $this->mensajeModal();

        }

        // ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
        // Se debe declarar el mismo atributo de marco con que se inició el formulario.
        $atributos['marco'] = true;
        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->formulario($atributos);
    }
    public function mensaje() {

        switch ($_REQUEST['mensaje']) {

            case 'insertoInformacionCertificado':
                $estilo_mensaje = 'success';     // information,warning,error,validation
                $atributos["mensaje"] = '<b>Certificado Disponible</b>';
                break;

            case 'noinsertoInformacionCertificado':
                $estilo_mensaje = 'error';     // information,warning,error,validation
                $atributos["mensaje"] = '<b>Oh No!!!! <br>Error en generar el certificado<b>';
                break;

        }
        // ------------------Division para los botones-------------------------
        $atributos['id'] = 'divMensaje';
        $atributos['estilo'] = 'marcoBotones';
        echo $this->miFormulario->division("inicio", $atributos);

        // -------------Control texto-----------------------
        $esteCampo = 'mostrarMensaje';
        $atributos["tamanno"] = '';
        $atributos["etiqueta"] = '';
        $atributos["estilo"] = $estilo_mensaje;
        $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
        echo $this->miFormulario->campoMensaje($atributos);
        unset($atributos);

        // ------------------Fin Division para los botones-------------------------
        echo $this->miFormulario->division("fin");
        unset($atributos);

    }

    public function mensajeModal() {

        switch ($_REQUEST['mensaje_modal']) {

            case 'actualizoInformacionCertificado':
                $mensaje = "Exito en la actualización información del certificado";
                $atributos['estiloLinea'] = 'success';     //success,error,information,warning
                break;

        }

        // ----------------INICIO CONTROL: Ventana Modal Beneficiario Eliminado---------------------------------
        if (isset($mensaje)) {
            $atributos['tipoEtiqueta'] = 'inicio';
            $atributos['titulo'] = 'Mensaje';
            $atributos['id'] = 'mensaje';
            echo $this->miFormulario->modal($atributos);
            unset($atributos);

            // ----------------INICIO CONTROL: Mapa--------------------------------------------------------
            echo '<div style="text-align:center;">';

            echo '<p><h5>' . $mensaje . '</h5></p>';

            echo '</div>';

            // ----------------FIN CONTROL: Mapa--------------------------------------------------------

            echo '<div style="text-align:center;">';

            echo '</div>';

            $atributos['tipoEtiqueta'] = 'fin';
            echo $this->miFormulario->modal($atributos);
            unset($atributos);
        }

    }

}

$miSeleccionador = new GestionarContrato($this->lenguaje, $this->miFormulario, $this->sql);

$miSeleccionador->formulario();

?>
