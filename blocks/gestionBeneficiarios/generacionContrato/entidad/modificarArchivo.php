<?php

namespace gestionBeneficiarios\generacionContrato\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

include_once "core/auth/SesionSso.class.php";

include_once 'Redireccionador.php';
require_once 'sincronizar.php';
class Alfresco {

    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public $miSql;
    public $conexion;
    public $archivos_datos;
    public $esteRecursoDB;
    public $datos_contrato;
    public $rutaURL;
    public $rutaAbsoluta;
    public $miSesionSso;
    public function __construct($lenguaje, $sql) {
        $this->miConfigurador = \Configurador::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->lenguaje = $lenguaje;
        $this->miSql = $sql;
        $this->sincronizacion = new Sincronizar($lenguaje, $sql);
        $this->miSesionSso = \SesionSso::singleton();

        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");

        $this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");

        if (!isset($_REQUEST["bloqueGrupo"]) || $_REQUEST["bloqueGrupo"] == "") {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloque"] . "/";
        } else {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
        }
        // Conexion a Base de Datos
        $conexion = "interoperacion";
        $this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        if ($_REQUEST['tipologia'] == 128) {

            if ($_REQUEST['verificar'] == 'true') {
                $this->verificarArchivoContrato();
            }

            if ($_REQUEST['actualizar'] == 'true') {
                foreach ($_FILES as $key => $values) {
                    if ($_FILES[$key]['size'] == 0) {
                        Redireccionador::redireccionar("noverifico", $_REQUEST['id_beneficiario']);
                    }
                }
                $this->asociarCodigoDocumentoContrato();
                $this->cargarArchivos();

                $this->sincronizacion->sincronizarAlfresco($_REQUEST['id_beneficiario'], $this->archivos_datos[0]);
                $this->actualizarLocalContrato();

            }
        } else {

            if ($_REQUEST['verificar'] == 'true') {
                $this->verificarArchivo();
            }

            if ($_REQUEST['actualizar'] == 'true') {
                foreach ($_FILES as $key => $values) {
                    if ($_FILES[$key]['size'] == 0) {
                        Redireccionador::redireccionar("noverifico", $_REQUEST['id_beneficiario']);
                    }
                }
                $this->asociarCodigoDocumento();
                $this->cargarArchivos();
                $this->sincronizacion->sincronizarAlfresco($_REQUEST['id_beneficiario'], $this->archivos_datos[0]);
                $this->actualizarLocal();
            }
        }

        $this->actualizarEstadoContrato();

        if ($this->verificacion) {
            Redireccionador::redireccionar("verifico", $_REQUEST['id_beneficiario']);
        } else {
            Redireccionador::redireccionar("noverifico", $_REQUEST['id_beneficiario']);
        }
    }

    public function actualizarEstadoContrato() {

        $arreglo = array(
            'perfil_beneficiario' => $_REQUEST['tipo_beneficiario'],
            'id_beneficiario' => $_REQUEST['id_beneficiario'],

        );
        $cadenaSql = $this->miSql->getCadenaSql('consultarValidacionRequisitos', $arreglo);
        $requisitos = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        $cadenaSql = $this->miSql->getCadenaSql('consultaInformacionBeneficiario');
        $beneficiario = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $cadenaSql = $this->miSql->getCadenaSql('consultaContratoInfo');
        $contrato = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $_REQUEST['numero_contrato'] = $contrato['numero_contrato'];

        {

            $contrato['comisionador'] = ($contrato['comisionador'] == 't') ? 1 : (($contrato['analista'] == 'f') ? 0 : NULL);

            $contrato['supervisor'] = ($contrato['supervisor'] == 't') ? 1 : (($contrato['analista'] == 'f') ? 0 : NULL);

            $contrato['analista'] = ($contrato['analista'] == 't') ? 1 : (($contrato['analista'] == 'f') ? 0 : NULL);

            $resultadoContrato = $contrato['comisionador'] * $contrato['supervisor'] * $contrato['analista'];
            if ($resultadoContrato) {

                $cambiarEstadoCN = true;
            }
        }

        if ($requisitos) {
            foreach ($requisitos as $key => $value) {

                $value['comisionador'] = ($value['comisionador'] == 't') ? 1 : (($value['analista'] == 'f') ? 0 : NULL);
                $value['supervisor'] = ($value['supervisor'] == 't') ? 1 : (($value['analista'] == 'f') ? 0 : NULL);
                $value['analista'] = ($value['analista'] == 't') ? 1 : (($value['analista'] == 'f') ? 0 : NULL);

                $resultado = $value['comisionador'] * $value['supervisor'] * $value['analista'];

                if ($beneficiario['minvi'] == 't' && $resultado && !is_null($value['nombre_documento'])) {

                    switch ($value['codigo']) {
                        case '001':
                            $cambiarEstadoCB = true;

                            break;

                        case '900':
                            $cambiarEstadoCNI = true;

                            break;

                    }

                }

                if ($beneficiario['minvi'] == 'f' && $resultado && !is_null($value['nombre_documento'])) {
                    $cambiarEstado = true;

                } else {

                    $EstadoFaltante = true;
                }

            }

            if (isset($cambiarEstadoCB) && isset($cambiarEstadoCNI) && isset($cambiarEstadoCN) && $beneficiario['minvi'] == 't') {

                $cadenaSql = $this->miSql->getCadenaSql('actualizarEstadoContrato');

                $this->actualizarContrato = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

                $cadenaSql = $this->miSql->getCadenaSql('actualizarServicio', $this->actualizarContrato['id']);

                $this->actualizarServicio = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");

                $this->estruturarComisionamiento();

            } elseif (!isset($EstadoFaltante) && isset($cambiarEstadoCN) && $beneficiario['minvi'] == 'f') {

                $cadenaSql = $this->miSql->getCadenaSql('actualizarEstadoContrato');

                $this->actualizarContrato = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

                $cadenaSql = $this->miSql->getCadenaSql('actualizarServicio', $this->actualizarContrato['id']);

                $this->actualizarServicio = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");

                $this->estruturarComisionamiento();

            }

        }

    }

    public function estruturarComisionamiento() {
        include_once "estruturarComisionamiento.php";
    }
    public function verificarArchivo() {
        $respuesta = $this->miSesionSso->getParametrosSesionAbierta();
        $rol = $respuesta['description'][0];

        $cadenaSql = $this->miSql->getCadenaSql('buscarRol', $rol);
        $rolDes = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        $datos = array(
            'archivo' => $_REQUEST['id_archivo'],
            'rol' => $rolDes[0][0],
        );

        $cadenaSql = $this->miSql->getCadenaSql('verificarArchivo', $datos);
        $this->verificacion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");
    }
    public function verificarArchivoContrato() {
        $respuesta = $this->miSesionSso->getParametrosSesionAbierta();
        $rol = $respuesta['description'][0];
        // $cadenaSql = $this->miSql->getCadenaSql ( 'pruebas' );
        // $pruebas = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
        // $rol = $pruebas [0] [0];

        $cadenaSql = $this->miSql->getCadenaSql('buscarRol', $rol);
        $rolDes = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        $datos = array(
            'archivo' => $_REQUEST['id_archivo'],
            'rol' => $rolDes[0][0],
        );

        $cadenaSql = $this->miSql->getCadenaSql('verificarArchivoContrato', $datos);
        $this->verificacion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");
    }
    public function actualizarLocal() {
        $cadenaSql = $this->miSql->getCadenaSql('actualizarLocal', $this->archivos_datos[0]);
        $this->verificacion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");
    }
    public function actualizarLocalContrato() {
        $cadenaSql = $this->miSql->getCadenaSql('actualizarLocalContrato', $this->archivos_datos[0]);

        $this->verificacion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");
    }
    public function asociarCodigoDocumento() {
        foreach ($_FILES as $key => $value) {
            $cadenaSql = $this->miSql->getCadenaSql('consultarParametro', $key);
            $id_parametro = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");
            $_FILES[$key]['tipo_documento'] = $id_parametro[0]['id_parametro'];
            $_FILES[$key]['descripcion_documento'] = $id_parametro[0]['codigo'] . '_' . $id_parametro[0]['descripcion'];
        }
    }
    public function asociarCodigoDocumentoContrato() {
        foreach ($_FILES as $key => $value) {
            $cadenaSql = $this->miSql->getCadenaSql('consultarParametroContrato', $key);
            $id_parametro = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

            $_FILES[$key]['tipo_documento'] = $id_parametro[0]['id_parametro'];
            $_FILES[$key]['descripcion_documento'] = $id_parametro[0]['codigo'] . '_' . $id_parametro[0]['descripcion'];
        }
    }
    public function cargarArchivos() {
        foreach ($_FILES as $key => $archivo) {
            if ($_FILES[$key]['size'] != 0) {
                $this->prefijo = substr(md5(uniqid(time())), 0, 6);
                $exten = pathinfo($archivo['name']);

                $allowed = array('image/jpeg', 'image/png', 'image/psd', 'image/bmp', 'application/pdf');

                if (!in_array($_FILES[$key]['type'], $allowed)) {

                    Redireccionador::redireccionar("ErrorCargarFicheroDirectorio");
                    exit();
                }

                if (isset($exten['extension']) == false) {
                    $exten['extension'] = 'txt';
                }

                $tamano = $archivo['size'];
                $tipo = $archivo['type'];
                $nombre_archivo = str_replace(" ", "_", $archivo['descripcion_documento']);
                $doc =  $nombre_archivo . "_" . $this->prefijo . '.' . $exten['extension'];
                /*
                 * guardamos el fichero en el Directorio
                 */
                $ruta_absoluta = $this->miConfigurador->configuracion['raizDocumento'] . "/archivos/" . $doc;
                $ruta_relativa = $this->miConfigurador->configuracion['host'] . $this->miConfigurador->configuracion['site'] . "/archivos/" . $doc;
                $archivo['rutaDirectorio'] = $ruta_absoluta;
                if (!copy($archivo['tmp_name'], $ruta_absoluta)) {

                    Redireccionador::redireccionar("ErrorCargarFicheroDirectorio");
                    exit();
                }
                $archivo_datos[] = array(
                    'ruta_archivo' => $ruta_relativa,
                    'rutaabsoluta' => $ruta_absoluta,
                    'nombre_archivo' => $doc,
                    'campo' => $key,
                    'tipo_documento' => $archivo['tipo_documento'],
                    'id_archivo' => $_REQUEST['id_archivo'],
                );
            }
        }
        $this->archivos_datos = $archivo_datos;
    }
}

$miProcesador = new Alfresco($this->lenguaje, $this->sql);
?>

