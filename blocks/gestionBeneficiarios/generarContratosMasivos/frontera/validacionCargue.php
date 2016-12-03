<?php
namespace gestionBeneficiarios\generarContratosMasivos\frontera;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}
/**
 * IMPORTANTE: Este formulario está utilizando jquery.
 * Por tanto en el archivo ready.php se declaran algunas funciones js
 * que lo complementan.
 */
class Registrador {
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
    public function seleccionarForm() {
        $esteBloque = $this->miConfigurador->getVariableConfiguracion("esteBloque");
        $miPaginaActual = $this->miConfigurador->getVariableConfiguracion("pagina");
        // Conexion a Base de Datos
        $conexion = "interoperacion";
        $esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        // Rescatar los datos de este bloque

        // ---------------- SECCION: Parámetros Globales del Formulario ----------------------------------

        $atributosGlobales['campoSeguro'] = 'true';

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
            $esteCampo = 'Agrupacion';
            $atributos['id'] = $esteCampo;
            $atributos['leyenda'] = "Cargue Masivo Contratos";
            echo $this->miFormulario->agrupacion('inicio', $atributos);
            unset($atributos);
            {

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "validacion";
                $atributos["estilo"] = "marcoBotones";
                $atributos["estiloEnLinea"] = "display:block;";
                echo $this->miFormulario->division("inicio", $atributos);
                unset($atributos);
                {

                    {
                        // ------------------Division para los botones-------------------------
                        $atributos['id'] = 'divMensaje';
                        $atributos['estilo'] = 'textoIzquierda';
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);
                        // -------------Control texto-----------------------
                        $esteCampo = 'mostrarMensaje';
                        $atributos["tamanno"] = '';
                        $atributos["etiqueta"] = '';
                        $mensaje = 'Cargar Formato para Validación:<br>
                        1. No exita contrato generado con la identificaciones cargadas.<br>
                        2. Exista la información del beneficiario a generar contrato.<br>';
                        $atributos["mensaje"] = $mensaje;
                        $atributos["estilo"] = 'information'; // information,warning,error,validation
                        $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
                        echo $this->miFormulario->campoMensaje($atributos);
                        unset($atributos);
                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);
                    }

                    $esteCampo = "archivo_validacion"; // Código documento
                    $atributos["id"] = $esteCampo;
                    $atributos["nombre"] = $esteCampo;
                    $atributos["tipo"] = "file";
                    $atributos["obligatorio"] = true;
                    $atributos["etiquetaObligatorio"] = false;
                    $atributos["tabIndex"] = $tab++;
                    $atributos["columnas"] = 1;
                    $atributos["anchoCaja"] = "9";
                    $atributos["estilo"] = "textoDerecha";
                    $atributos["anchoEtiqueta"] = "3";
                    $atributos["tamanno"] = 500000;
                    $atributos["validar"] = " ";
                    $atributos["estilo"] = "file";
                    $atributos["etiqueta"] = $this->lenguaje->getCadena($esteCampo);
                    $atributos["bootstrap"] = true;
                    $tab++;
                    // $atributos ["valor"] = $valorCodificado;
                    $atributos = array_merge($atributos);
                    echo $this->miFormulario->campoCuadroTexto($atributos);
                    unset($atributos);

                    // ------------------Division para los botones-------------------------
                    $atributos["id"] = "botones";
                    $atributos["estilo"] = "marcoBotones";
                    $atributos["estiloEnLinea"] = "display:block;";
                    echo $this->miFormulario->division("inicio", $atributos);
                    unset($atributos);
                    {
                        $esteCampo = 'botonValidacion';
                        $atributos["id"] = $esteCampo;
                        $atributos["tabIndex"] = $tab;
                        $atributos["tipo"] = 'boton';
                        // submit: no se coloca si se desea un tipo button genérico
                        $atributos['submit'] = true;
                        $atributos["simple"] = true;
                        $atributos["estiloMarco"] = '';
                        $atributos["estiloBoton"] = 'default';
                        $atributos["block"] = false;
                        // verificar: true para verificar el formulario antes de pasarlo al servidor.
                        $atributos["verificar"] = '';
                        $atributos["tipoSubmit"] = 'jquery'; // Dejar vacio para un submit normal, en este caso se ejecuta la función submit declarada en ready.js
                        $atributos["valor"] = $this->lenguaje->getCadena($esteCampo);
                        $atributos['nombreFormulario'] = $esteBloque['nombre'];
                        $tab++;

                        // Aplica atributos globales al control
                        $atributos = array_merge($atributos, $atributosGlobales);
                        echo $this->miFormulario->campoBotonBootstrapHtml($atributos);
                        unset($atributos);
                    }
                    // ------------------Fin Division para los botones-------------------------
                    echo $this->miFormulario->division("fin");
                    unset($atributos);

                }
                echo $this->miFormulario->division("fin");
                unset($atributos);

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "cargue";
                $atributos["estilo"] = "marcoBotones";
                $atributos["estiloEnLinea"] = "display:block;";
                echo $this->miFormulario->division("inicio", $atributos);
                unset($atributos);
                {

                    {
                        // ------------------Division para los botones-------------------------
                        $atributos['id'] = 'divMensaje';
                        $atributos['estilo'] = 'textoIzquierda';
                        echo $this->miFormulario->division("inicio", $atributos);

                        // -------------Control texto-----------------------
                        $esteCampo = 'mostrarMensaje';
                        $atributos["tamanno"] = '';
                        $atributos["etiqueta"] = '';
                        $mensaje = 'Cargar Formato Información Contratos:<br>
                      	Recordar que no se generará ningun contrato si exite algún error de validación en el formato.<br>
                      	Antes de cargar la información verifique el formato en la sección de Validación.';
                        $atributos["mensaje"] = $mensaje;
                        $atributos["estilo"] = 'information'; // information,warning,error,validation
                        $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
                        echo $this->miFormulario->campoMensaje($atributos);
                        unset($atributos);

                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);
                    }
                    $esteCampo = "archivo_contratos"; // Código documento
                    $atributos["id"] = $esteCampo;
                    $atributos["nombre"] = $esteCampo;
                    $atributos["tipo"] = "file";
                    $atributos["obligatorio"] = true;
                    $atributos["etiquetaObligatorio"] = false;
                    $atributos["tabIndex"] = $tab++;
                    $atributos["columnas"] = 1;
                    $atributos["anchoCaja"] = "9";
                    $atributos["estilo"] = "textoDerecha";
                    $atributos["anchoEtiqueta"] = "3";
                    $atributos["tamanno"] = 500000;
                    $atributos["validar"] = " ";
                    $atributos["estilo"] = "file";
                    $atributos["etiqueta"] = $this->lenguaje->getCadena($esteCampo);
                    $atributos["bootstrap"] = true;
                    $tab++;
                    // $atributos ["valor"] = $valorCodificado;
                    $atributos = array_merge($atributos);
                    echo $this->miFormulario->campoCuadroTexto($atributos);
                    unset($atributos);

                    // ------------------Division para los botones-------------------------
                    $atributos["id"] = "botones";
                    $atributos["estilo"] = "marcoBotones";
                    $atributos["estiloEnLinea"] = "display:block;";
                    echo $this->miFormulario->division("inicio", $atributos);
                    unset($atributos);
                    {
                        $esteCampo = 'botonCargar';
                        $atributos["id"] = $esteCampo;
                        $atributos["tabIndex"] = $tab;
                        $atributos["tipo"] = 'boton';
                        // submit: no se coloca si se desea un tipo button genérico
                        $atributos['submit'] = true;
                        $atributos["simple"] = true;
                        $atributos["estiloMarco"] = '';
                        $atributos["estiloBoton"] = 'default';
                        $atributos["block"] = false;
                        // verificar: true para verificar el formulario antes de pasarlo al servidor.
                        $atributos["verificar"] = '';
                        $atributos["tipoSubmit"] = 'jquery'; // Dejar vacio para un submit normal, en este caso se ejecuta la función submit declarada en ready.js
                        $atributos["valor"] = $this->lenguaje->getCadena($esteCampo);
                        $atributos['nombreFormulario'] = $esteBloque['nombre'];
                        $tab++;

                        // Aplica atributos globales al control
                        $atributos = array_merge($atributos, $atributosGlobales);
                        echo $this->miFormulario->campoBotonBootstrapHtml($atributos);
                        unset($atributos);
                    }
                    // ------------------Fin Division para los botones-------------------------
                    echo $this->miFormulario->division("fin");
                    unset($atributos);

                }
                echo $this->miFormulario->division("fin");
                unset($atributos);
            }

            echo $this->miFormulario->agrupacion('fin');
            unset($atributos);
            {
                /**
                 * En algunas ocasiones es útil pasar variables entre las diferentes páginas.
                 * SARA permite realizar esto a través de tres
                 * mecanismos:
                 * (a). Registrando las variables como variables de sesión. Estarán disponibles durante toda la sesión de usuario. Requiere acceso a
                 * la base de datos.
                 * (b). Incluirlas de manera codificada como campos de los formularios. Para ello se utiliza un campo especial denominado
                 * formsara, cuyo valor será una cadena codificada que contiene las variables.
                 * (c) a través de campos ocultos en los formularios. (deprecated)
                 */

                // En este formulario se utiliza el mecanismo (b) para pasar las siguientes variables:

                // Paso 1: crear el listado de variables

                // $valorCodificado = "action=" . $esteBloque["nombre"];

                $valorCodificado = "action=" . $esteBloque["nombre"];
                $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
                $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
                $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
                $valorCodificado .= "&opcion=cargarRequisitos";

                /**
                 * SARA permite que los nombres de los campos sean dinámicos.
                 * Para ello utiliza la hora en que es creado el formulario para
                 * codificar el nombre de cada campo.
                 */
                $valorCodificado .= "&campoSeguro=" . $_REQUEST['tiempo'];
                // Paso 2: codificar la cadena resultante
                $valorCodificado = $this->miConfigurador->fabricaConexiones->crypto->codificar($valorCodificado);

                $atributos["id"] = "formSaraData"; // No cambiar este nombre
                $atributos["tipo"] = "hidden";
                $atributos['estilo'] = '';
                $atributos["obligatorio"] = false;
                $atributos['marco'] = true;
                $atributos["etiqueta"] = "";
                $atributos["valor"] = $valorCodificado;
                echo $this->miFormulario->campoCuadroTexto($atributos);
                unset($atributos);
            }
        }

        // ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
        // Se debe declarar el mismo atributo de marco con que se inició el formulario.
        $atributos['marco'] = true;
        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->formulario($atributos);
    }
    public function mensaje() {
        // var_dump($_REQUEST);
        $atributos["mensaje"] = "";
        switch ($_REQUEST['mensaje']) {
            case 'inserto':

                if (isset($_REQUEST['alfresco']) && $_REQUEST['alfresco'] > 0) {
                    $estilo_mensaje = 'warning';
                    $atributos["mensaje"] = '<br>Errores de Gestor Documental:' . $_REQUEST['alfresco'] . "Avíse al administrador del sistemas";
                } else {
                    $estilo_mensaje = 'success';
                    $atributos["mensaje"] = 'Requisitos Correctamente Subidos.';
                }
                break;

            case 'noinserto':
                $estilo_mensaje = 'error';     // information,warning,error,validation
                $atributos["mensaje"] = 'Error al validar los Requisitos.<br>Verifique los Documentos.';
                break;

            case 'insertoInformacionContrato':
                $estilo_mensaje = 'success';     // information,warning,error,validation
                $atributos["mensaje"] = 'Se ha registrado la información de contrato con exito.<br>Habilitado la Opcion de Descargar Contrato';
                break;

            case 'noInsertoInformacionContrato':
                $estilo_mensaje = 'error';     // information,warning,error,validation
                $atributos["mensaje"] = 'Error al registrar información del contrato';
                break;

            case 'verifico':
                $estilo_mensaje = 'success';     // information,warning,error,validation
                $atributos["mensaje"] = 'Documento Verificado';
                break;

            case 'noverifico':
                $estilo_mensaje = 'warning';     // information,warning,error,validation
                $atributos["mensaje"] = 'Atención, fallo en actualización.';
                break;

            case 'novalido':
                $estilo_mensaje = 'error';     // information,warning,error,validation
                $atributos["mensaje"] = 'Tipo de Archivo no Válido.';
                break;

            default:
                // code...
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
}

$miSeleccionador = new Registrador($this->lenguaje, $this->miFormulario, $this->sql);

$miSeleccionador->seleccionarForm();

?>

