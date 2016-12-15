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
        //var_dump($_REQUEST);
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
        {

            // URL base
            $url = $this->miConfigurador->getVariableConfiguracion("host");
            $url .= $this->miConfigurador->getVariableConfiguracion("site");
            $url .= "/index.php?";

            // Variables para Con
            $cadenaACodificar = "pagina=" . $this->miConfigurador->getVariableConfiguracion("pagina");
            $cadenaACodificar .= "&procesarAjax=true";
            $cadenaACodificar .= "&action=index.php";
            $cadenaACodificar .= "&bloqueNombre=" . $esteBloque["nombre"];
            $cadenaACodificar .= "&bloqueGrupo=" . $esteBloque["grupo"];
            $cadenaACodificar .= "&funcion=ejecutarProcesos";

            // Codificar las variables
            $enlace = $this->miConfigurador->getVariableConfiguracion("enlace");
            $cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url($cadenaACodificar, $enlace);

            // URL Consultar Proyectos
            $urlEjecutarProceso = $url . $cadena;

            //echo $enlace;exit;

        }
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

                $esteCampo = 'seleccion_proceso';
                $atributos['nombre'] = $esteCampo;
                $atributos['id'] = $esteCampo;
                $atributos['etiqueta'] = $this->lenguaje->getCadena($esteCampo);
                $atributos["etiquetaObligatorio"] = true;
                $atributos['tab'] = $tab++;
                $atributos['anchoEtiqueta'] = 1;
                $atributos['evento'] = '';
                if (isset($_REQUEST[$esteCampo])) {
                    $atributos['seleccion'] = $_REQUEST[$esteCampo];
                } else {
                    $atributos['seleccion'] = '1';
                }
                $atributos['deshabilitado'] = false;
                $atributos['columnas'] = 1;
                $atributos['tamanno'] = 1;
                $atributos['ajax_function'] = "";
                $atributos['ajax_control'] = $esteCampo;
                $atributos['estilo'] = "bootstrap";
                $atributos['limitar'] = false;
                $atributos['anchoCaja'] = 3;
                $atributos['miEvento'] = '';
                $atributos['validar'] = 'required';
                $atributos['cadena_sql'] = 'required';
                $matrizItems = array(
                    array(
                        '1',
                        'Validar Formato Información Contrato',
                    ),
                    array(
                        '2',
                        'Cargar y Crear Contratos',
                    ),
                    array(
                        '3',
                        'Consultar Estado Generación Contratos',
                    ),
                );
                $atributos['matrizItems'] = $matrizItems;
                // Aplica atributos globales al control
                $atributos = array_merge($atributos, $atributosGlobales);
                echo $this->miFormulario->campoCuadroListaBootstrap($atributos);
                unset($atributos);

                $esteCampo = 'proceso';
                $atributos["id"] = $esteCampo; // No cambiar este nombre
                $atributos["tipo"] = "hidden";
                $atributos['estilo'] = '';
                $atributos["obligatorio"] = false;
                $atributos['marco'] = true;
                $atributos["etiqueta"] = "";
                if (isset($_REQUEST[$esteCampo])) {
                    $atributos['valor'] = $_REQUEST[$esteCampo];
                } else {
                    $atributos['valor'] = '1';
                }
                $atributos = array_merge($atributos, $atributosGlobales);
                echo $this->miFormulario->campoCuadroTexto($atributos);
                unset($atributos);

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
                        {

                            {
                                // URL base
                                $url = $this->miConfigurador->getVariableConfiguracion("host");
                                $url .= $this->miConfigurador->getVariableConfiguracion("site");
                                $url .= '/archivos/generacionMasiva/plantillas/';
                                $url .= 'Plantilla_Contratos_Masivos.xls';

                            }

                            // -------------Control texto-----------------------
                            $esteCampo = 'mostrarMensaje';
                            $atributos["tamanno"] = '';
                            $atributos["etiqueta"] = '';
                            $mensaje = 'Cargar Formato para Validación:<br>
                                                1. No exista contrato generado con la identificaciones cargadas.<br>
                                                2. Exista la información del beneficiario a generar contrato.<br>
                                                3. El múmero de beneficiarios a cargar no puede sobre pasar a 500.<br>
                                                4. Formatos permitidos:<br>
                                                    &nbsp;&nbsp;&nbsp;- BIFF 5-8 (.xls) Excel 95<br>
                                                    &nbsp;&nbsp;&nbsp;- Office Open XML (.xlsx) Excel 2007 o mayores<br>
                                                    &nbsp;&nbsp;&nbsp;- Open Document Format/OASIS (.ods)<br><br>
                                        Link de Descarga de Plantilla : <b><a target="_blank" href="' . $url . '">Plantilla Cargue Masivo</a></b><br>
                                                ';

                            $atributos["mensaje"] = $mensaje;
                            $atributos["estilo"] = 'information'; // information,warning,error,validation
                            $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
                            echo $this->miFormulario->campoMensaje($atributos);
                            unset($atributos);
                        }
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
                echo "</div>";
                echo $this->miFormulario->division("fin");
                unset($atributos);

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "cargue";
                $atributos["estilo"] = "marcoBotones";
                $atributos["estiloEnLinea"] = "display:none;";
                echo $this->miFormulario->division("inicio", $atributos);
                unset($atributos);
                {

                    {
                        // ------------------Division para los botones-------------------------
                        $atributos['id'] = 'divMensaje';
                        $atributos['estilo'] = 'textoIzquierda';
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);
                        {
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
                        }
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
                echo "</div>";
                echo $this->miFormulario->division("fin");
                unset($atributos);

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "consulta";
                $atributos["estilo"] = "marcoBotones";
                $atributos["estiloEnLinea"] = "display:none;";
                echo $this->miFormulario->division("inicio", $atributos);
                unset($atributos);
                {

                    {
                        // ------------------Division para los botones-------------------------
                        $atributos['id'] = 'divMensaje';
                        $atributos['estilo'] = 'marcoBotones';
                        echo $this->miFormulario->division("inicio", $atributos);
                        unset($atributos);
                        {
                            // -------------Control texto-----------------------
                            $esteCampo = 'mostrarMensaje';
                            $atributos["tamanno"] = '';
                            $atributos["etiqueta"] = '';
                            $mensaje = 'Consulta de Estado Generación de Contratos';
                            $atributos["mensaje"] = $mensaje;
                            $atributos["estilo"] = 'information'; // information,warning,error,validation
                            $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
                            echo $this->miFormulario->campoMensaje($atributos);
                            unset($atributos);
                            {

                                echo '<table id="example" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th><center>Proceso<center></th>
                                            <th><center>Estado<center></th>
                                            <th><center>Archivo Descarga<center></th>
                                            <th><center>Numéro de Contrato Inicial<center></th>
                                            <th><center>Numéro de Contrato Final<center></th>
                                        </tr>
                                    </thead>
                                           <tfoot>
                                        <tr>
                                            <th><center>Proceso<center></th>
                                            <th><center>Estado<center></th>
                                            <th><center>Archivo Descarga<center></th>
                                            <th><center>Numéro de Contrato Inicial<center></th>
                                            <th><center>Numéro de Contrato Final<center></th>
                                        </tr>
                                    </tfoot>
                                  </table>';
                            }

                        }
                        // ------------------Fin Division para los botones-------------------------
                        echo $this->miFormulario->division("fin");
                        unset($atributos);
                    }

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
                $valorCodificado .= "&opcion=gestionarContratos";

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

                if (isset($_REQUEST['mensajeModal'])) {

                    $this->mensajeModal();

                }
            }
        }

        // ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
        // Se debe declarar el mismo atributo de marco con que se inició el formulario.
        $atributos['marco'] = true;
        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->formulario($atributos);
    }

    public function mensajeModal() {

        switch ($_REQUEST['mensajeModal']) {

            case 'errorFormatoArchivo':
                $mensaje = "Error<br>Formato Archivo Invalido";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'errorArchivoNoValido':
                $mensaje = "Error<br>Archivo No Valido";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'errorCargarArchivo':
                $mensaje = "Error<br>Al Cargar Archivo";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'errorCargarInformacion':
                $mensaje = "Error<br>Al Cargar Informacion Hoja de Cálculo";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'errorInformacionCargar':
                $mensaje = "Error<br>Existen Inconsistencias en la Información a Cargar.<br>Para más Informacion Visualizar Log : <a  target='_blank' href='" . base64_decode($_REQUEST['log']) . "'>Link Log Errores</a>";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'exitoInformacion':
                $mensaje = "Exito<br>Información Correctamente Validada y sin Errores.<br>Dirigirse a la Opcion \"Crear y Cargar Contratos\" ";
                $atributos['estiloLinea'] = 'success';     //success,error,information,warning
                break;

            case 'errorCreacionContratos':
                $mensaje = "Error<br>Existen Inconsistencias en la Información a Cargar.<br>Para más Informacion Validar el Archivo en el Opción \"Validar Formato de Información Contrato\"";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning
                break;

            case 'exitoRegistroProceso':
                $mensaje = "Exito<br>Se ha Registrado con exito el <b>Proceso # " . $_REQUEST['proceso'] . "</b>.<br>Para más Informacion consulte la Opción \"Consulta Estado de Generación Contratos\"";
                $atributos['estiloLinea'] = 'success';     //success,error,information,warning
                break;

            case 'errorRegistroProceso':

                $mensaje = "Error<br>Error en el Registro del Proceso";
                $atributos['estiloLinea'] = 'error';     //success,error,information,warning

                break;
        }

        // ----------------INICIO CONTROL: Ventana Modal Beneficiario Eliminado---------------------------------

        $atributos['tipoEtiqueta'] = 'inicio';
        $atributos['titulo'] = 'Mensaje';
        $atributos['id'] = 'mensajeModal';
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

