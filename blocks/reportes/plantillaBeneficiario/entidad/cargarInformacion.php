<?php

namespace reportes\plantillaBeneficiario\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

$ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
$host = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site") . "/plugin/html2pfd/";

require_once $ruta . "/plugin/PHPExcel/Classes/PHPExcel.php";

// require_once $ruta . "/plugin/PHPExcel/Classes/PHPExcel/Reader/Excel2007.php";

require_once $ruta . "/plugin/PHPExcel/Classes/PHPExcel/IOFactory.php";

include_once 'Redireccionador.php';
class FormProcessor
{
    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public $miSql;
    public $conexion;
    public $esteRecursoDB;
    public $rutaURL;
    public $rutaAbsoluta;
    public function __construct($lenguaje, $sql)
    {
        date_default_timezone_set('America/Bogota');

        $this->miConfigurador = \Configurador::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->lenguaje = $lenguaje;
        $this->miSql = $sql;

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

        $_REQUEST['tiempo'] = time();

        /**
         * 1.
         * Cargar Archivo en el Directorio
         */

        $this->cargarArchivos();

        /**
         * 2.
         * Cargar Informacion Hoja de Calculo
         */

        $this->cargarInformacionHojaCalculo();

        /**
         * 3.
         * Validar Duplicidad Plantilla
         */

        $this->validarDuplicidad();

        /**
         * 4.
         * Validar que no hayan nulos
         */

        $this->validarNulo();

        /**
         * 5.
         * Validar que no  Valores Númericos
         */

        $this->validarNumeros();

        /**
         * 6.
         * Validar Existencia Beneficiarios
         */

        if ($_REQUEST['funcionalidad'] == 3) {
            $this->validarBeneficiariosExistentes();

            $this->transformacionValoresNulos();
        } else {
            $this->validarBeneficiariosExistentesRegistro();
        }

        /**
         * 7.
         * Procesar Información Beneficiarios
         */

        $this->procesarInformacionBeneficiario();

        /**
         * 8.
         * Actualizar o Registrar beneficiarios
         */

        $this->informacionBeneficiario();
    }

    /**
     * Funcionalidades Específicas
     */
    public function informacionBeneficiario()
    {

        foreach ($this->informacion_registrar as $key => $value) {
            if ($_REQUEST['funcionalidad'] == 3) {
                $cadenaSql = $this->miSql->getCadenaSql('actualizarBeneficiario', $value);
                $this->resultado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "registro");
            } else {
                $cadenaSql = $this->miSql->getCadenaSql('registrarBeneficiarioPotencial', $value);
                $this->resultado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "registro");
            }
        }

        if ($this->resultado != true) {
            Redireccionador::redireccionar("ErrorActualizacion");
        } else {
            Redireccionador::redireccionar("ExitoRegistroProceso");
        }
    }
    public function validarNulo()
    {
        foreach ($this->datos_beneficiario as $key => $value) {

            if ($value['estrato'] === 0) {

                Redireccionador::redireccionar("ErrorCreacion");

            }

            if (is_null($value['identificacion_beneficiario']) || $value['identificacion_beneficiario'] == 'NULL') {

                Redireccionador::redireccionar("ErrorCreacion");

            }
        }
    }
    public function procesarInformacionBeneficiario()
    {
        $a = 0;
        foreach ($this->datos_beneficiario as $key => $value) {

            //Validacion Geolocalizacion
            if (is_numeric($value['latitud']) && is_numeric($value['longitud'])) {
                $geolocalizacion = $value['latitud'] . "," . $value['longitud'];
            } else {
                $geolocalizacion = null;
            }

            // Funcionalidad 3 es Actualización de Registros
            if ($_REQUEST['funcionalidad'] == 3) {
                $cadenaSql = $this->miSql->getCadenaSql('consultarInformacionBeneficiario', $value['identificacion_beneficiario']);
                $consulta = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

                $this->informacion_registrar[] = array(
                    'id_beneficiario' => $consulta['id_beneficiario'],
                    'identificacion_beneficiario' => $value['identificacion_beneficiario'],
                    'tipo_beneficiario' => $value['tipo_beneficiario'],
                    'tipo_documento' => $value['tipo_documento'],
                    'nomenclatura' => $consulta['nomenclatura'],
                    'nombre_beneficiario' => $value['nombre'],
                    'primer_apellido' => $value['primer_apellido'],
                    'segundo_apellido' => $value['segundo_apellido'],
                    'genero_beneficiario' => $value['genero'],
                    'edad_beneficiario' => $value['edad'],
                    'nivel_estudio' => $value['nivel_estudio'],
                    'correo' => $value['correo'],
                    'direccion' => $value['direccion'],
                    'manzana' => $value['manzana'],
                    'torre' => $value['torre'],
                    'bloque' => $value['bloque'],
                    'interior' => $value['interior'],
                    'lote' => $value['lote'],
                    'apartamento' => $value['casa_apto'],
                    'telefono' => $value['telefono'],
                    'departamento' => $value['departamento'],
                    'municipio' => $value['municipio'],
                    'piso' => $value['piso'],
                    'minvi' => $value['minvivienda'],
                    'barrio' => $value['barrio'],
                    'id_proyecto' => $value['id_proyecto'],
                    'proyecto' => $value['proyecto'],
                    'estrato' => $value['estrato'],
                    'geolocalizacion' => $geolocalizacion,
                );
            } else {

                $cadenaSql = $this->miSql->getCadenaSql('codificacion', $value['id_proyecto']);
                $resultado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

                if ($resultado) {
                    $_REQUEST['consecutivo'] = $resultado[0]['abr_benf'];
                    $_REQUEST['abr_urb'] = $resultado[0]['abr_urb'];
                    $_REQUEST['abr_mun'] = $resultado[0]['abr_mun'];
                } else {
                    $_REQUEST['consecutivo'] = "ND";
                    $_REQUEST['abr_urb'] = "ND";
                    $_REQUEST['abr_mun'] = "ND";
                }

                $numeroCaracteres = 5;
                $numeroBusqueda = strlen($_REQUEST['consecutivo']);

                $valor['string'] = $_REQUEST['consecutivo'];
                $valor['longitud'] = $numeroCaracteres - $numeroBusqueda - 1;

                $cadenaSql = $this->miSql->getCadenaSql('consultarConsecutivo', $valor);
                $resultado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

                if ($resultado) {
                    $consecutivo = explode($_REQUEST['consecutivo'], $resultado[0]['id_beneficiario']);
                    $nuevoConsecutivo = $consecutivo[1] + 1 + $a;

                    if (strlen($_REQUEST['consecutivo']) == 1) {
                        if ($nuevoConsecutivo < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '000' . $nuevoConsecutivo;
                        } else if ($nuevoConsecutivo < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '00' . $nuevoConsecutivo;
                        } else if ($nuevoConsecutivo < 1000) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $nuevoConsecutivo;
                        } else {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . $nuevoConsecutivo;
                        }
                    } else if (strlen($_REQUEST['consecutivo']) == 2) {
                        if ($nuevoConsecutivo < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '00' . $nuevoConsecutivo;
                        } else if ($nuevoConsecutivo < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $nuevoConsecutivo;
                        } else {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . $nuevoConsecutivo;
                        }
                    } else if (strlen($_REQUEST['consecutivo']) == 3) {
                        if ($nuevoConsecutivo < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $nuevoConsecutivo;
                        } else if ($nuevoConsecutivo < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . $nuevoConsecutivo;
                        } else {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . $nuevoConsecutivo;
                        }
                    } else {
                        $nuevoConsecutivo = $_REQUEST['consecutivo'] . $nuevoConsecutivo;
                    }

                    $beneficiarioPotencial['id_beneficiario'] = $nuevoConsecutivo;
                } else {
                    $b = 1 + $a;

                    if (strlen($_REQUEST['consecutivo']) == 1) {
                        if ($b < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '000' . $b;
                        } else if ($b > 9 && $b < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '00' . $b;
                        } else if ($b > 99 && $b < 1000) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $b;
                        } elseif ($b > 999 && $b < 10000) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '' . $b;
                        }
                    } else if (strlen($_REQUEST['consecutivo']) == 2) {
                        if ($b < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '00' . $b;
                        } else if ($b > 9 && $b < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $b;
                        } else if ($b > 99 && $b < 1000) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '' . $b;
                        }
                    } else if (strlen($_REQUEST['consecutivo']) == 3) {
                        if ($b < 10) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '0' . $b;
                        } else if ($b > 9 && $b < 100) {
                            $nuevoConsecutivo = $_REQUEST['consecutivo'] . '' . $b;
                        }
                    } else if (strlen($_REQUEST['consecutivo']) == 4) {
                        $nuevoConsecutivo = $_REQUEST['consecutivo'] . '' . $b;
                    }

                    $beneficiarioPotencial['id_beneficiario'] = $nuevoConsecutivo;
                }

                $a++;

                $this->informacion_registrar[] = array(
                    'id_beneficiario' => $beneficiarioPotencial['id_beneficiario'],
                    'tipo_beneficiario' => $value['tipo_beneficiario'],
                    'tipo_documento' => $value['tipo_documento'],
                    'identificacion_beneficiario' => $value['identificacion_beneficiario'],
                    'nombre_beneficiario' => $value['nombre'],
                    'primer_apellido' => $value['primer_apellido'],
                    'segundo_apellido' => $value['segundo_apellido'],
                    'genero_beneficiario' => $value['genero'],
                    'edad_beneficiario' => $value['edad'],
                    'nivel_estudio' => $value['nivel_estudio'],
                    'correo' => $value['correo'],
                    'direccion' => $value['direccion'],
                    'manzana' => $value['manzana'],
                    'interior' => $value['interior'],
                    'bloque' => $value['bloque'],
                    'torre' => $value['torre'],
                    'apartamento' => $value['casa_apto'],
                    'lote' => $value['lote'],
                    'telefono' => $value['telefono'],
                    'departamento' => $value['departamento'],
                    'municipio' => $value['municipio'],
                    'estrato' => $value['estrato'],
                    'id_proyecto' => $value['id_proyecto'],
                    'proyecto' => $value['proyecto'],
                    'piso' => $value['piso'],
                    'minvi' => $value['minvivienda'],
                    'barrio' => $value['barrio'],
                    'nomenclatura' => $_REQUEST['abr_mun'] . "_" . $_REQUEST['abr_urb'] . "_" . $value['identificacion_beneficiario'],
                    'geolocalizacion' => $geolocalizacion,
                );
            }
        }
    }

    public function validarDuplicidad()
    {

        $conteo_identificaciones = array_count_values($this->identificaciones);

        foreach ($conteo_identificaciones as $key => $value) {

            if ($value > 1) {
                Redireccionador::redireccionar("ErrorCreacion");

            }

        }

    }

    public function transformacionValoresNulos()
    {

        foreach ($this->datos_beneficiario as $key => $value) {

            foreach ($value as $llave => $valor) {

                if ($valor == 'NULL') {

                    $valor = null;

                }

                $value[$llave] = $valor;

                $this->datos_beneficiario[$key] = $value;

            }

        }

    }

    public function validarNumeros()
    {
        foreach ($this->datos_beneficiario as $key => $value) {

            if (!is_null($value['longitud']) && $value['longitud'] != '') {
                if (!is_numeric($value['longitud'])) {

                    Redireccionador::redireccionar("ErrorCreacion");
                } elseif ($value['longitud'] < -77 || $value['longitud'] > -73) {

                    Redireccionador::redireccionar("ErrorCreacion");
                }
            } else {

                Redireccionador::redireccionar("ErrorCreacion");

            }

            if (!is_null($value['latitud']) && $value['latitud'] != '') {
                if (!is_numeric($value['latitud'])) {

                    Redireccionador::redireccionar("ErrorCreacion");
                } elseif ($value['latitud'] > 10 || $value['latitud'] < 6) {

                    Redireccionador::redireccionar("ErrorCreacion");
                }
            } else {

                Redireccionador::redireccionar("ErrorCreacion");

            }

        }

    }

    public function validarBeneficiariosExistentes()
    {
        foreach ($this->datos_beneficiario as $key => $value) {

            $cadenaSql = $this->miSql->getCadenaSql('consultarExitenciaBeneficiario', $value['identificacion_beneficiario']);

            $consulta = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

            if (is_null($consulta)) {
                Redireccionador::redireccionar("ErrorCreacion");
                exit();
            }
        }
    }
    public function validarBeneficiariosExistentesRegistro()
    {
        foreach ($this->datos_beneficiario as $key => $value) {

            $cadenaSql = $this->miSql->getCadenaSql('consultarExitenciaBeneficiario', $value['identificacion_beneficiario']);
            $consulta = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

            if (!is_null($consulta)) {
                Redireccionador::redireccionar("ErrorCreacion");
                exit();
            }
        }
    }
    public function cargarInformacionHojaCalculo()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        if (file_exists($this->archivo['ruta_archivo'])) {

            $hojaCalculo = \PHPExcel_IOFactory::createReader($this->tipo_archivo);
            $informacion = $hojaCalculo->load($this->archivo['ruta_archivo']);

            $informacion_general = $hojaCalculo->listWorksheetInfo($this->archivo['ruta_archivo']);

            {
                $total_filas = $informacion_general[0]['totalRows'];
            }

            if ($total_filas > 1001) {
                Redireccionador::redireccionar("ErrorNoCargaInformacionHojaCalculo");
            }

            for ($i = 2; $i <= $total_filas; $i++) {

                $datos_beneficiario[$i]['departamento'] = $informacion->setActiveSheetIndex()->getCell('A' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['municipio'] = $informacion->setActiveSheetIndex()->getCell('B' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['id_proyecto'] = $informacion->setActiveSheetIndex()->getCell('C' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['proyecto'] = $informacion->setActiveSheetIndex()->getCell('D' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['tipo_beneficiario'] = $informacion->setActiveSheetIndex()->getCell('E' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['tipo_documento'] = $informacion->setActiveSheetIndex()->getCell('F' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['identificacion_beneficiario'] = trim($informacion->setActiveSheetIndex()->getCell('G' . $i)->getCalculatedValue());

                $this->identificaciones[] = trim($informacion->setActiveSheetIndex()->getCell('G' . $i)->getCalculatedValue());

                $datos_beneficiario[$i]['nombre'] = $informacion->setActiveSheetIndex()->getCell('H' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['primer_apellido'] = $informacion->setActiveSheetIndex()->getCell('I' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['segundo_apellido'] = $informacion->setActiveSheetIndex()->getCell('J' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['genero'] = (!is_null($informacion->setActiveSheetIndex()->getCell('K' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('K' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['edad'] = (!is_null($informacion->setActiveSheetIndex()->getCell('L' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('L' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['nivel_estudio'] = (!is_null($informacion->setActiveSheetIndex()->getCell('M' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('M' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['correo'] = $informacion->setActiveSheetIndex()->getCell('N' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['telefono'] = (!is_null($informacion->setActiveSheetIndex()->getCell('O' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('O' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['direccion'] = $informacion->setActiveSheetIndex()->getCell('P' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['manzana'] = $informacion->setActiveSheetIndex()->getCell('Q' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['bloque'] = $informacion->setActiveSheetIndex()->getCell('R' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['torre'] = $informacion->setActiveSheetIndex()->getCell('S' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['casa_apto'] = $informacion->setActiveSheetIndex()->getCell('T' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['interior'] = $informacion->setActiveSheetIndex()->getCell('U' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['lote'] = $informacion->setActiveSheetIndex()->getCell('V' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['piso'] = (!is_null($informacion->setActiveSheetIndex()->getCell('W' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('W' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['minvivienda'] = (!is_null($informacion->setActiveSheetIndex()->getCell('X' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('X' . $i)->getCalculatedValue() : 'FALSE';

                $datos_beneficiario[$i]['barrio'] = $informacion->setActiveSheetIndex()->getCell('Y' . $i)->getCalculatedValue();

                $datos_beneficiario[$i]['estrato'] = (!is_null($informacion->setActiveSheetIndex()->getCell('Z' . $i)->getCalculatedValue())) ? $informacion->setActiveSheetIndex()->getCell('Z' . $i)->getCalculatedValue() : 0;

                $datos_beneficiario[$i]['longitud'] = str_replace(',', '.', $informacion->setActiveSheetIndex()->getCell('AA' . $i)->getCalculatedValue());
                $datos_beneficiario[$i]['latitud'] = str_replace(',', '.', $informacion->setActiveSheetIndex()->getCell('AB' . $i)->getCalculatedValue());

            }

            //var_dump($datos_beneficiario);exit;

            $this->datos_beneficiario = $datos_beneficiario;

            unlink($this->archivo['ruta_archivo']);
        } else {
            Redireccionador::redireccionar("ErrorNoCargaInformacionHojaCalculo");
        }
    }
    public function cargarArchivos()
    {
        $archivo_datos = '';
        $archivo = $_FILES['archivo_informacion'];

        if ($archivo['error'] == 0) {

            switch ($archivo['type']) {
                case 'application/vnd.oasis.opendocument.spreadsheet':
                    $this->tipo_archivo = 'OOCalc';
                    break;

                case 'application/vnd.ms-excel':
                    $this->tipo_archivo = 'Excel5';
                    break;

                case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                    $this->tipo_archivo = 'Excel2007';
                    break;

                default:
                    Redireccionador::redireccionar("ErrorFormatoArchivo");
                    break;
            }

            $this->prefijo = substr(md5(uniqid(time())), 0, 6);
            /*
             * obtenemos los datos del Fichero
             */
            $tamano = $archivo['size'];
            $tipo = $archivo['type'];
            $nombre_archivo = str_replace(" ", "_", $archivo['name']);
            /*
             * guardamos el fichero en el Directorio
             */
            $ruta_absoluta = $this->rutaAbsoluta . "/entidad/archivos_validar/" . $this->prefijo . "_" . $nombre_archivo;

            $ruta_relativa = $this->rutaURL . " /entidad/archivos_validar/" . $this->prefijo . "_" . $nombre_archivo;

            $archivo['rutaDirectorio'] = $ruta_absoluta;

            if (!copy($archivo['tmp_name'], $ruta_absoluta)) {

                Redireccionador::redireccionar("ErrorCargarArchivo");
            }

            $this->archivo = array(
                'ruta_archivo' => str_replace("//", "/", $ruta_absoluta),
                'nombre_archivo' => $archivo['name'],
            );
        } else {
            Redireccionador::redireccionar("ErrorArchivoNoValido");
        }
    }
}

$miProcesador = new FormProcessor($this->lenguaje, $this->sql);
