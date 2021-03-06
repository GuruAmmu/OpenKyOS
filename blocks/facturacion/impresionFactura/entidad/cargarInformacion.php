<?php

namespace facturacion\impresionFactura\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

include_once 'Redireccionador.php';
class FormProcessor
{
    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public $miSql;
    public $conexion;
    public $esteRecursoDB;
    public function __construct($lenguaje, $sql)
    {
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

        /**
         * 1.Procesar Información Beneficiarios
         */

        $this->procesarInformacionBeneficiario();

        /**
         * 2.Validar Numeros de Factura Actuales
         */

        $this->validarNumerosFacturasActuales();

        /**
         * 3.Registrar Proceso
         */

        $this->registroProceso();

        if (!is_null($this->proceso)) {
            Redireccionador::redireccionar("ExitoRegistroProceso", $this->proceso);
        } else {
            Redireccionador::redireccionar("ErrorRegistroProceso");
        }
    }
    public function registroProceso()
    {

        if ($_REQUEST['correo'] == '1') {
            $datos_adicionales = implode(";", $this->Beneficiarios) . '&correo';
        } elseif ($_REQUEST['correo'] == '0') {
            $datos_adicionales = implode(";", $this->Beneficiarios);
        }

        $arreglo_registro = array(
            'inicio' => $this->Beneficiarios[0],
            'final' => end($this->Beneficiarios),
            'datos_adicionales' => $datos_adicionales,
            'urbanizaciones' => implode("<br>", $this->Urbanizaciones),
        );

        $cadenaSql = $this->miSql->getCadenaSql('registrarProceso', $arreglo_registro);

        $this->proceso = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0]['id_proceso'];
    }

    public function validarNumerosFacturasActuales()
    {

        $departamento_validar = ['FVM', 'FVS'];

        foreach ($departamento_validar as $key => $value) {

            $cadenaSql = $this->miSql->getCadenaSql('consultarNumeracionFactura', $value);

            $numeracion_actual = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0]['numeracion'];

            switch ($value) {
                case 'FVS':

                    $numero_beneficiarios_facturar = $this->contarBeneficiarioPorDepartamento('70');
                    $limite = 1445;

                    break;

                case 'FVM':
                    $numero_beneficiarios_facturar = $this->contarBeneficiarioPorDepartamento('23');
                    $limite = 130316;
                    break;

            }

            if (!is_null($numeracion_actual) && $numeracion_actual < $limite) {

                if (($limite - $numeracion_actual) < $numero_beneficiarios_facturar) {

                    Redireccionador::redireccionar("ErrorNumeroBeneficiariosFacturar");
                }

            } else if (!($numeracion_actual < $limite)) {

                Redireccionador::redireccionar("ErrorNumeracionFacturacion", $value . " " . $limite);

            }

        }

    }

    public function contarBeneficiarioPorDepartamento($departamento)
    {
        $i = 0;
        foreach ($this->BeneficiariosValidar as $key => $value) {

            if ($value['departamento'] == $departamento) {

                $i++;

            }

        }

        return $i;

    }

    public function procesarInformacionBeneficiario()
    {
        $arreglo = array(
            'departamento' => $_REQUEST['departamento'],
            'municipio' => $_REQUEST['municipio'],
            'urbanizacion' => $_REQUEST['urbanizacion'],
            'beneficiario' => $_REQUEST['beneficiario'],
        )
        ;

        $cadenaSql = $this->miSql->getCadenaSql('consultaGeneralInformacion');
        $Beneficiarios = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        $cadenaSql = $this->miSql->getCadenaSql('consultaGeneralInformacionUrbanizaciones');
        $Urbanizaciones = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        if ($Beneficiarios == false) {

            Redireccionador::redireccionar('SinResultado');
        }

        foreach ($Beneficiarios as $key => $value) {
            $this->Beneficiarios[] = trim($value['id_beneficiario']);
        }

        foreach ($Beneficiarios as $key => $value) {
            $this->BeneficiariosValidar[] = array(
                'id_beneficiario' => trim($value['id_beneficiario']),
                'departamento' => $value['departamento'],

            );
        }

        foreach ($Urbanizaciones as $key => $value) {
            $this->Urbanizaciones[] = trim($value['urbanizacion']);
        }
    }
}

$miProcesador = new FormProcessor($this->lenguaje, $this->sql);
