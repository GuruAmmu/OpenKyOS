<?php
namespace gestionBeneficiarios\generarContratosMasivos\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

$ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
$host = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site") . "/plugin/html2pfd/";

include $ruta . "/plugin/html2pdf/html2pdf.class.php";

class GenerarDocumento {
    public $miConfigurador;
    public $elementos;
    public $miSql;
    public $conexion;
    public $contenidoPagina;
    public $rutaURL;
    public $rutaAbsoluta;
    public $nombreContrato;
    public $esteRecursoDB;
    public $esteRecursoDBPR;
    public $clausulas;
    public $beneficiario;
    public $esteRecursoOP;
    public $miSesionSso;
    public $info_usuario;
    public $nombre_contrato;
    public $miProceso;
    public $ruta_archivos;
    public function __construct($sql, $proceso, $ruta_archivos) {
        $this->miConfigurador = \Configurador::singleton();
        $this->miSesionSso = \SesionSso::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->miSql = $sql;
        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");
        $this->miProceso = $proceso;
        $this->ruta_archivos = $ruta_archivos;

        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");
        $esteBloque = $this->miConfigurador->configuracion['esteBloque'];

        if (!isset($esteBloque["grupo"]) || $esteBloque["grupo"] == "") {

            $this->rutaURL .= "/blocks/" . $esteBloque["nombre"] . "/";
        } else {
            $this->rutaURL .= "/blocks/" . $esteBloque["grupo"] . "/" . $esteBloque["nombre"] . "/";
        }

        //Conexion a Base de Datos
        $conexion = "interoperacion";
        $this->esteRecursoDBPR = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        $this->nombre = base64_decode($this->miProceso['nombre_archivo']);

        $this->nombre = explode("-", $this->nombre);

        /**
         *  1. Información de Beneficiario
         **/

        $this->obtenerInformacionBeneficiario();

        /**
         *  2. Estructuración Documentos
         **/

        foreach ($this->beneficiario as $key => $value) {

            $this->estruturaDocumento($value);
            $this->asosicarNombreArchivo($value);
            $this->crearPDF();
            unset($this->contenidoPagina);
            $this->contenidoPagina = NULL;
            unset($this->nombreContrato);
            $this->nombreContrato = NULL;
            $value = NULL;

        }

    }

    public function asosicarNombreArchivo($beneficiario) {
        $this->nombreContrato = '';
        foreach ($this->nombre as $key => $value) {

            $this->nombreContrato .= $beneficiario[$value] . "_";
            $value = NULL;

        }

        $prefijo = substr(md5(uniqid(time())), 0, 6);
        $this->nombreContrato .= $prefijo . ".pdf";

    }

    public function obtenerInformacionBeneficiario() {
        $arreglo = array(
            'Inicio' => $this->miProceso['parametro_inicio'],
            'Fin' => $this->miProceso['parametro_fin'],
        );

        $cadenaSql = $this->miSql->getCadenaSql('ConsultaBeneficiarios', $arreglo);
        $this->beneficiario = $this->esteRecursoDBPR->ejecutarAcceso($cadenaSql, "busqueda");

    }

    public function crearPDF() {
        ob_start();
        $html2pdf = new \HTML2PDF('P', 'LEGAL', 'es', true, 'UTF-8', array(
            2,
            2,
            2,
            10,
        ));
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->WriteHTML($this->contenidoPagina);
        $html2pdf->Output($this->ruta_archivos . "/" . $this->nombreContrato, 'F');

    }
    public function estruturaDocumento($beneficiario) {

        $cadenaSql = $this->miSql->getCadenaSql('consultarTipoDocumento', "Cédula de Ciudadanía");
        $CodigoCedula = $this->esteRecursoDBPR->ejecutarAcceso($cadenaSql, "busqueda");
        $CodigoCedula = $CodigoCedula[0];

        $cadenaSql = $this->miSql->getCadenaSql('consultarTipoDocumento', "Tarjeta de Identidad");
        $CodigoTargeta = $this->esteRecursoDBPR->ejecutarAcceso($cadenaSql, "busqueda");
        $CodigoTargeta = $CodigoTargeta[0];
        {

            $anexo_dir = '';

            if ($beneficiario['manzana'] != '0' && $beneficiario['manzana'] != '') {
                $anexo_dir .= " Manzana  #" . $beneficiario['manzana'] . " - ";
            }
            if ($beneficiario['bloque'] != '0' && $beneficiario['bloque'] != '') {
                $anexo_dir .= " Bloque #" . $beneficiario['bloque'] . " - ";
            }
            if ($beneficiario['torre'] != '0' && $beneficiario['torre'] != '') {
                $anexo_dir .= " Torre #" . $beneficiario['torre'] . " - ";
            }
            if ($beneficiario['casa_apartamento'] != '0' && $beneficiario['casa_apartamento'] != '') {
                $anexo_dir .= " Casa/Apartamento #" . $beneficiario['casa_apartamento'];
            }
            if ($beneficiario['interior'] != '0' && $beneficiario['interior'] != '') {
                $anexo_dir .= " Interior #" . $beneficiario['interior'];
            }
            if ($beneficiario['lote'] != '0' && $beneficiario['lote'] != '') {
                $anexo_dir .= " Lote #" . $beneficiario['lote'];
            }
            if ($beneficiario['piso'] != '0' && $beneficiario['piso'] != '') {
                $anexo_dir .= " Piso #" . $beneficiario['piso'];
            }
        }
        {
            $cedula = ($beneficiario['tipo_documento'] == $CodigoCedula['codigo']) ? '<b>(X)</b>' : '';
            $targeta = ($beneficiario['tipo_documento'] == $CodigoTargeta['codigo']) ? '<b>(X)</b>' : '';
        }

        {

            $cadenaSql = $this->miSql->getCadenaSql('consultarParametroParticular', $beneficiario['medio_pago']);
            $medioPago = $this->esteRecursoDBPR->ejecutarAcceso($cadenaSql, "busqueda")[0];

            //var_dump($medioPago);
            $cadenaSql = $this->miSql->getCadenaSql('consultarParametroParticular', $beneficiario['tipo_pago']);
            $tipoPago = $this->esteRecursoDBPR->ejecutarAcceso($cadenaSql, "busqueda")[0];
            //var_dump($tipoPago);exit;

            $medio_virtual = ($medioPago['descripcion'] == 'Virtual') ? "X" : " ";

            $medio_efectivo = ($medioPago['descripcion'] == 'Efectivo') ? "X" : " ";

            $tipo_prepago = ($tipoPago['descripcion'] == 'Prepago') ? "X" : " ";

            $tipo_pospago = ($tipoPago['descripcion'] == 'Pospago') ? "X" : " ";

            $tipo_anticipado = ($tipoPago['descripcion'] == 'Anticipado') ? "X" : " ";

        }

        $telefono = ($beneficiario['telefono'] != '0') ? $beneficiario['telefono'] : " ";

        {

            $tipo_vip = ($beneficiario['estrato'] == 1) ? "<b>X</b>" : "";
            $tipo_residencial_1 = ($beneficiario['estrato'] == 2) ? (($beneficiario['estrato_socioeconomico'] == 1) ? "<b>X</b>" : "") : "";
            $tipo_residencial_2 = ($beneficiario['estrato'] == 2) ? (($beneficiario['estrato_socioeconomico'] == 2) ? "<b>X</b>" : "") : "";

        }

        {

            $fecha = explode("-", $beneficiario['fecha_contrato']);

        }

        {

            $contenidoPagina = "
                            <style type=\"text/css\">
                                table {

                                    font-family:Helvetica, Arial, sans-serif; /* Nicer font */

                                    border-collapse:collapse; border-spacing: 3px;
                                }
                                td, th {
                                    border: 1px solid #CCC;
                                    height: 13px;
                                } /* Make cells a bit taller */

                                th {

                                    font-weight: bold; /* Make sure they're bold */
                                    text-align: center;
                                    font-size:10px;
                                }
                                td {

                                    text-align: left;

                                }

                                draw {

                                  transform:scale(2.0);

                                }
                            </style>



                        <page backtop='35mm' backbottom='10mm' backleft='10mm' backright='10mm' footer='page'>
                            <page_header>
                            <br>
                            <br>
                                    <table  style='width:100%;' >
                                        <tr>
                                                <td align='center' style='width:100%;border=none;' >
                                                <img<img src='" . $this->rutaURL . "frontera/css/imagen/logos_contrato.png'  width='500' height='35'>
                                                </td>
                                                <tr>
                                                <td style='width:100%;border:none;text-align:center;font-size:9px;'>><b>CONTRATO DE APORTE N° 0681 DE 2015<br>CORPORACIÓN POLITÉCNICA NACIONAL DE COLOMBIA</b></td>
                                                </tr>
                                                <tr>
                                                <td style='width:100%;border:none;text-align:center;font-size:9px;'><br><br><br><b>CONTRATO DE APORTE N° 0681 DE 2015<br>CORPORACIÓN POLITÉCNICA NACIONAL DE COLOMBIA</b></td>
                                                </tr>
                                                <tr>
                                                <td style='width:100%;border:none;text-align:center;font-size:9px;'><b>PROYECTO CONEXIONES DIGITALES II</b></td>
                                                </tr>
                                        </tr>
                                    </table>

                        </page_header>";

            $contenidoPagina .= "
<br>
<br>
    <table style='width:100%;'>
                        <tr>
                            <td style='width:100%;text-align=center;border:none'>
                                    <table style='width:100%;'>
                                        <tr>
                                            <td style='width:100%;border:none;text-align:center'><b>CONTRATO DE PRESTACIÓN DE SERVICIOS DE COMUNICACIONES</b></td>
                                        </tr>
                                    </table>
                            </td>
                            <td style='width:5%;text-align=center;border:none'> </td>
                            <td style='width:30%;text-align=center;border:none'></td>
                        </tr>
                    </table>
                    <br>
                    <table style='width:100%;'>
                        <tr>
                            <td style='width:35%;text-align=center;border:none'> </td>
                            <td style='width:30%;text-align=center;border:none'>
                                    <table style='width:100%;'>
                                        <tr>
                                            <td style='width:25%;text-align=center;'>Fecha</td>
                                            <td style='width:25%;text-align=center;'><b>" . $fecha[2] . "</b></td>
                                            <td style='width:25%;text-align=center;'><b>" . $fecha[1] . "</b></td>
                                            <td style='width:25%;text-align=center;'><b>" . $fecha[0] . "</b></td>
                                        </tr>
                                    </table>
                            </td>
                            <td style='width:5%;text-align=center;border:none'> </td>
                            <td style='width:30%;text-align=center;border:none'>
                                <table style='width:100%;'>
                                        <tr>
                                            <td style='width:50%;text-align=center;'>N° Contrato</td>
                                            <td style='width:50%;text-align=center;'>" . $beneficiario['numero_contrato'] . "</td>
                                        </tr>
                                    </table>
                             </td>
                        </tr>
                    </table>
                    <br>
                    <P style='text-align:justify;font-size:10px'>
  Entre LA CORPORACIÓN POLITÉCNICA NACIONAL DE COLOMBIA, en adelante POLITÉCNICA, entidad sin ánimo de lucro, domiciliada en la ciudad de Bogotá D.C, por una parte, y por la otra, la persona identificada como USUARIO, cuyos datos son los registrados a continuación, quien ha leído y aceptado en todos sus términos el presente documento y sus respectivos anexos, hemos convenido celebrar el presente CONTRATO DE SERVICIOS DE COMUNICACIONES, con el objeto de establecer las condiciones técnicas, jurídicas y económicas que regirán la prestación al USUARIO de servicios de Internet fijo, en condiciones de calidad y eficiencia, el cual se regirá por lo dispuesto en el Anexo Técnico del Contrato de Aporte N° 681 de 2015 y al documento de recomendaciones de la CRC “CONEXIONES DIGITALES Esquema para la implementación de subsidios e Incentivos para el acceso a Internet de Última milla”, la ley 1341/09, Resolución 3066/11 de la CRC, la regulación y reglamentación que expidan la CRC, el Min TIC y la SuperIndustria y Comercio, según su competencia, condiciones y anexos del presente contrato, en el marco del Contrato de Aporte N° 681 de 2015. y en las normas que la modifiquen o deroguen.
                    </P>
                    <table style='width:100%;'>
                        <tr>
                            <td rowspan='9' style='width:15%;text-align=center;'><b>DATOS USUARIO</b></td>
                            <td style='width:15%;text-align=center;'><b>Nombres</b></td>
                            <td colspan='3' style='width:70%;text-align=center;'><b>" . $beneficiario['nombres'] . " " . $beneficiario['primer_apellido'] . " " . $beneficiario['segundo_apellido'] . "</b></td>
                        </tr>
                        <tr>
                            <td style='width:15%;text-align=center;'><b>Número Identificación</b></td>
                            <td colspan='3' style='width:15%;text-align=center;'><b>" . number_format($beneficiario['numero_identificacion'], 0, '', '.') . "</b></td>
                        </tr>
                        <tr>
                            <td style='width:15%;text-align=center;'><b>Dirección Domicilio</b></td>
                            <td colspan='3' style='width:70%;text-align=center;'>" . $beneficiario['direccion_domicilio'] . " " . $anexo_dir . "</td>
                        </tr>
                         <tr>
                            <td style='width:15%;text-align=center;'><b>Departamento</b></td>
                            <td style='width:10%;text-align=center;'>" . $beneficiario['departamento'] . "</td>
                            <td style='width:10%;text-align=center;'><b>Municipio</b></td>
                            <td style='width:10%;text-align=center;'>" . $beneficiario['municipio'] . "</td>
                         </tr>
                         <tr>
                            <td style='width:15%;text-align=center;'><b>Urbanización</b></td>
                            <td colspan='3'style='width:70%;text-align=center;'>" . $beneficiario['urbanizacion'] . "</td>
                        </tr>
                        <tr>
                            <td style='width:15%;text-align=center;'><b>Estrato</b></td>
                            <td style='text-align=center;'>VIP (" . $tipo_vip . ")</td>
                            <td style='text-align=center;'>1 Residencial (" . $tipo_residencial_1 . ")</td>
                            <td style='text-align=center;'>2 Residencial (" . $tipo_residencial_2 . ")</td>
                        </tr>
                        <tr>
                           <td style='width:15%;text-align=center;'><b>Barrio</b></td>
                           <td colspan='3'style='width:70%;text-align=center;'>" . $beneficiario['barrio'] . " </td>
                        </tr>
                         <tr>
                            <td style='width:15%;text-align=center;'><b>Telefono</b></td>
                            <td style='width:10%;text-align=center;'>" . $telefono . "</td>
                            <td style='width:10%;text-align=center;'><b>Celular</b></td>
                            <td style='width:10%;text-align=center;'>" . $beneficiario['celular'] . "</td>
                        </tr>
                        <tr>
                            <td style='width:15%;text-align=center;'><b>Correo Electrónico</b></td>
                            <td colspan='3'style='width:70%;text-align=center;'>" . $beneficiario['correo'] . "</td>
                        </tr>



                    </table>
                    <table style='width:100%;'>
                        <tr>
                            <td rowspan='2' style='width:15%;text-align=center;'><b>DATOS SERVICIO</b></td>
                            <td style='width:30%;text-align=center;'><b>Velocidad Internet</b></td>
                            <td style='width:15%;text-align=right;'>" . $beneficiario['velocidad_internet'] . " MB</td>
                            <td style='width:20%;text-align=center;'><b>Vigencia Servicio</b></td>
                            <td style='width:20%;text-align=center;'><b>15 Meses</b></td>
                        </tr>
                        <tr>
                            <td style='width:30%;text-align=center;'><b>Valor Mensual Servicio Básico </b></td>
                            <td style='width:15%;text-align=center;'><b>$ " . $beneficiario['valor_tarificacion'] . "</b></td>
                            <td style='width:20%;text-align=center;'><b>Valor Total</b></td>
                            <td style='width:20%;text-align=center;'><b>$ " . $beneficiario['valor_tarificacion'] * 15 . "</b></td>
                        </tr>
                     </table>
                      <table style='width:100%;'>
                         <tr>
                            <td rowspan='3' style='width:15%;text-align=center;'><b>DATOS FACTURACIÓN</b></td>
                            <td style='width:35%;text-align=center;'><b>Forma de Pago</b></td>
                            <td style='width:5%;text-align=center;'>Prepago (<b>" . $tipo_prepago . "</b>)</td>
                            <td style='width:5%;text-align=center;'>Postpago (<b>" . $tipo_pospago . "</b>)</td>
                            <td style='width:5%;text-align=center;'>Anticipado (<b>" . $tipo_anticipado . "</b>)</td>
                        </tr>
                        <tr>
                            <td style='width:35%;text-align=center;'><b>Mecanismos de Pago</b></td>
                            <td style='width:5%;text-align=center;'>Virtual (<b>" . $medio_virtual . "</b>)</td>
                            <td  colspan='2'  style='width:5%;text-align=center;'>Efectivo (<b>" . $medio_efectivo . "</b>)</td>
                        </tr>
                        <tr>
                            <td style='width:35%;text-align=center;'><b>TOTAL A PAGAR FACTURA MENSUAL</b></td>
                            <td  colspan='3' style='width:50%;text-align=center;'><b>$ " . $beneficiario['valor_tarificacion'] . "</b></td>
                        </tr>
                        </table>

                    <table style='width:100%;'>
                        <tr>
                            <td text-align=center;'><b>DECLARACIONES DEL USUARIO</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
                         1. Que comprendo que el equipo de cómputo y el módem se entregan en comodato.<br>
2. Que me responsabilizo a hacer buen uso y manipular correctamente el equipo entregado y me hago responsable por el deterioro por mal uso o pérdida del mismo.<br>
3. Que conozco que el mal uso de los equipos entregados puede acarrear la suspensión temporal o total del servicio.<br>
4. Que  me comprometo a no transferir el equipo a ubicación o persona diferente a la consignada en el presente documento.<br>
5. Que al finalizar el contrato de servicio devolveré a la Corporación Politécnica el equipo entregado y los accesorios que permitían el acceso a internet.<br>
6. Que se ha informado de los mecanismos que tengo a mi disposición para solicitar o reportar fallos en los servicios de acceso, así como de los tiempos máximos en los cuales se tramitarán las peticiones.<br>
7. Que conozco y acepto los términos de protección de la Información y Tratamiento de Uso de Datos que se encuentran descritos en el documento de Términos y Condiciones de Protección de Datos, disponible en el portal Web del Proyecto.<br>
8. Que conozco, comprendo y acepto el Régimen de Derechos y Obligaciones plasmado en los anexos del presente contrato.
                            </td>
                        </tr>
                        <tr>
                            <td text-align=center;'><b>ENTREGA DEL EQUIPO DE CÓMPUTO</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
    De acuerdo con los lineamientos del Anexo Técnico del Contrato de Aporte N° 681 de 2015, junto con el servicio POLITÉCNICA entregará al USUARIO un equipo de cómputo a título de comodato (uso y goce). Finalizado el contrato, se transferirá el derecho de propiedad del equipo dado en comodato al USUARIO, siempre y cuando se haya cancelado el valor total del servicio y que dicho servicio se haya utilizado durante los 15 meses pactados en el presente contrato. No obstante lo anterior, si el USUARIO cancela, suspende o se retira del  contrato de manera anticipada, procederá a aplicarse lo contemplado en la cláusula VIGÉSIMO TERCERA. CAUSALES DE TERMINACIÓN DEL CONTRATO, del Anexo <b>CONDICIONES GENERALES DE SERVICIO DE COMUNICACIONES CONEXIONES DIGITALES II</b>, en lo atinente al procedimiento de restitución del equipo por parte del USUARIO al patrimonio autónomo P.A. Politécnica 8965 propietario del equipo.
                       </td>
                        </tr>
                        <tr>
                            <td text-align=center;'><b>DISPONIBILIDAD DE LA INFORMACIÓN E INCORPORACIÓN DE ANEXOS</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
                   De conformidad con lo establecido en el numeral 11.2 de la Resolución 3066 de 2011, con la firma del presente documento, el USUARIO autoriza expresamente a POLITÉCNICA a publicar en el portal web del Proyecto (http://conexionesdigitales.politecnica.edu.co/), las condiciones de prestación del (los) servicio (s), sus modificaciones y Anexos, los cuales entiende, acepta y declara conocer a cabalidad.  Así mismo, el USUARIO autoriza expresamente a POLITÉCNICA a remitir los anexos de este contrato a través de correo electrónico enviando a al cuenta registrada durante el proceso de contratación.  El USUARIO expresa su voluntad de aprobar el uso del correo electrónico registrado como mecanismo válido de intercambio de información en el marco del presente contrato de conformidad con los Términos y Condiciones de intercambio de Información que se encuentran publicados en el portal del Proyecto.
                            </td>
                        </tr>
                        <tr>
                            <td text-align=center;'><b>RÉGIMEN DE DERECHOS DEL USUARIO EN RELACIÓN CON LOS SERVICIOS PRESTADOS</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
                       Son derechos de los usuarios (Art 10 Resolución CRC 3066/11): 1. Contar con la medición apropiada de sus consumos reales, mediante los instrumentos tecnológicos apropiados para efectuar dicha medición, dentro de los plazos fijados por la CRC.  2. Ejercer los derechos contenidos en el Régimen de Protección al USUARIO de los Servicios de Comunicaciones, establecido en la Resolución CRC, 3066 de 2011 y demás normas que lo modifiquen o deroguen; así como todos aquellos derechos que se deriven de las disposiciones contenidas en la regulación expedida por el MinTIC, la CRC, y la Superintendencia de Industria y Comercio.  3. Recibir los servicios contratados de manera continua e ininterrumpida en los términos del Anexo Técnico del Contrato de Aporte N° 681 de 2015 salvo por circunstancias de fuerza mayor, caso fortuito o hecho de un tercero que impidan la presentación del servicio en condiciones normales; en caso de que el (los) servicios prestados por parte de causas diferentes a fuerza mayor, caso fortuito o hecho de un tercero, POLITÉCNICA reparará el daño en la mayor brevedad posible, de tal forma que la prestación del servicio no se vea gravemente afectada.
                            </td>
                        </tr>
                    </table>
                    <nobreak>
                    <br>
                    <br>
                    <br>
                    <table>
                        <tr>
                            <td text-align=center;'><b>RÉGIMEN DE OBLIGACIONES DEL USUARIO EN RELACIÓN CON LOS SERVICIOS PRESTADOS</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
1. Las contenidas en la Resolución CRC 3066/11 y demás normas que lo modifiquen o deroguen; así como todas aquellas derivadas de las disposiciones contenidas en la regulación expedida por el MinTIC, la CRC y la Superintendencia de Industria y Comercio.<br>
2. La utilización de la clave de acceso al servicio cuando ello resulte aplicable, es personal e intransferible y está a cargo y bajo la plena responsabilidad del USUARIO.<br>
3. Facilitar el acceso al inmueble a personas debidamente autorizadas e identificadas por POLITÉCNICA para efectuar revisiones a las instalaciones internas.<br>
4. Responder por cualquier anomalía, fraude o adulteración que se encuentre en las instalaciones internas, así como por las variaciones o modificaciones que sin autorización de POLITÉCNICA se hagan en relación con las condiciones del servicio contratado. Una vez suscrita el Acta de entrega de equipos y servicio de banda ancha, el USUARIO será responsable de la instalación entregada.<br>
5. El USUARIO entiende y acepta que le está prohibida la comercialización a terceras personas del servicio y que en consecuencia los beneficios que obtenga en virtud del mismo no son objeto de venta o comercialización, y que de hacerlo, su conducta constituye causal de cancelación del servicio y terminación del contrato.<br>
6. Proporcionar a las instalaciones internas, y equipos en general, el mantenimiento y uso adecuado con el fin de prevenir daños que puedan ocasionar deficiencias o interrupciones en el suministro del servicio contratado.<br>
7. El USUARIO entiende y acepta que es responsable absoluto de la utilización del servicio de comunicaciones, por lo tanto, es el único responsable de las transacciones que realice por intermedio de éste, así como de la seguridad de su identificación de ingreso, su clave y cualquier código de seguridad de bloqueo que utilice para proteger el acceso a sus datos, su(s) nombre(s) de archivo y sus archivos, el acceso a la red, o cualquier otra información que el USUARIO difunda a través del uso del servicio de POLITÉCNICA.<br>
8. Informar de inmediato a POLITÉCNICA sobre cualquier irregularidad, anomalía o cambio que se presente en las instalaciones internas, o la variación del propietario, dirección u otra novedad que implique modificación a las condiciones y datos registrados en el contrato de servicios y/o en el sistema de información comercial.<br>
9.  El USUARIO no transferirá su cuenta o le permitirá a otra persona usar su cuenta, de ser así, este será el único responsable del uso que se le dé a la misma 10. Informar sobre cualquier irregularidad, omisión, inconsistencia o variación que se detecte en la factura de cobro.<br>
11. Al finalizar este contrato o en cualquier tiempo, permitir el retiro de los equipos y elementos que le hayan sido entregados a título de comodato, y devolver cualquier información técnica de soporte que tenga en su poder.<br>
12. Abstenerse de trasladar los equipos, realizar modificación a la red instalada, establecer derivaciones o utilizar cualquier otro mecanismo que permita extender el servicio de comunicaciones a otros computadores, puntos, lugares o establecimientos comerciales diferentes a los cobijados y autorizados por este contrato, sin previa autorización escrita de POLITÉCNICA.<br>
13. En caso de pérdida, hurto o deterioro imputable al USUARIO, responder y pagar en forma inmediata su valor correspondiente, conforme a los precios vigentes a la fecha en que se haga efectivo el pago.<br>
14. Cumplir con los requisitos mínimos de prestación del servicio, exigidos por POLITÉCNICA, de conformidad con la modalidad del servicio contratado.<br>
15. De conformidad con lo establecido en la Ley 679 /01 y el Decreto 1524/02, abstenerse de alojar en su propio sitio de Internet, imágenes, textos, documentos o archivos audiovisuales que impliquen directa o indirectamente actividades sexuales con menores de edad; material pornográfico, en especial en modo de imágenes o videos, cuando existan indicios de que las personas fotografiadas o filmadas son menores de edad; vínculos o “links” sobre sitios telemáticos que contengan o distribuyan material pornográfico relativo a menores de edad.<br>
16. De conformidad con la Ley 679/01, denunciar ante las autoridades competentes cualquier acto criminal contra menores de edad de que tengan conocimiento, incluso de la difusión de material pornográfico asociado a menores; combatir con todos los medios técnicos a su alcance la difusión de material pornográfico con menores de edad; abstenerse de usar las redes globales de información para divulgación de material ilegal con menores de edad; establecer mecanismos técnicos de bloqueo por medio de los cuales los usuarios se puedan proteger a sí mismos o a sus hijos de material ilegal, ofensivo o indeseable en relación con menores de edad.<br>
17. Cerciorarse que el uso que está dando a la red no viola ninguna norma municipal, departamental o nacional, además de no violar las leyes en materia de derechos de autor, difamación, invasión de privacidad, distribución de información confidencial, información protegida y propiedad intelectual.<br>
18. El USUARIO suministrará a POLITÉCNICA información precisa, veraz, completa y actualizada para mantener actualizado el servicio; así mismo, deberá notificar a POLITÉCNICA dentro de los diez (10) días hábiles, de cualquier cambio en sus datos o información. Si el USUARIO incumple esta obligación responderá por los daños y perjuicios que tal omisión le cause a POLITÉCNICA, y en todo caso, el USUARIO queda en la obligación de pagar oportunamente el servicio.<br>
19. Abstenerse de utilizar el servicio con fines ilegales o contra la moral pública, ni comercializarlo a terceros, ni para perturbar a terceros, ni de tal manera que llegare a interferir injustificadamente con el uso del servicio por parte de otros clientes o terceros.<br>
20. Contratar exclusivamente con firmas instaladoras calificadas, la ejecución de instalaciones internas, o la realización de labores relacionadas con modificaciones, ampliaciones y trabajos similares. Quedan bajo su exclusiva responsabilidad los riesgos que puedan presentarse por el incumplimiento de esta disposición.<br>
21. Adoptar las políticas y las recomendaciones de seguridad que garanticen el uso adecuado de las claves de acceso a internet, correo electrónico, y demás servicios conexos o derivados para el intercambio de información por internet.<br>
22. El USUARIO, en caso de ser padre de familia o tener a su cargo personas menores de edad, conoce y acepta que la información obtenible a través de cualquier servicio de acceso a internet puede incluir materiales no aptos para menores, como sexuales explícitos o de contenido adulto, por lo cual el USUARIO será el único responsable del acceso que menores de edad puedan tener a ese material a través de la utilización de su cuenta 23. Las demás obligaciones previstas en el presente contrato y en la normatividad vigente.
                            </td>
                        </tr>
                    </table>
                    <table style='width:100%;'>
                        <tr>
                            <td text-align=center;'><b>PETICIONES, QUEJAS Y RECURSOS</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
                           El USUARIO tiene derecho a presentar peticiones, quejas y recursos –PQR- ante POLITÉCNICA, en forma verbal o escrita, mediante los medios tecnológicos o electrónicos asociados a los mecanismos obligatorios de atención al USUARIO dispuestos en el presente contrato.  La presentación de peticiones, quejas y recursos –PQR- y el trámite de las mismas no requieren presentación personal ni de intervención de abogado, aunque el USUARIO autorice a otra persona para que presente la PQR.  Las peticiones, quejas y recursos serán tramitadas por POLITÉCNICA de conformidad con las normas vigentes sobre el derecho de petición y recursos previstos en el Código de Procedimiento Administrativo y de lo Contencioso Administrativo y en la regulación vigente. Cuando se presenten las PQR en forma verbal, bastará con informarle a POLITÉCNICA, el nombre completo del peticionario, el número de identificación y el motivo por el cual presenta la PQR. En dicho caso, POLITÉCNICA podrá responderle de la misma forma, dejando constancia de la presentación de la PQR. Las PQR presentadas de forma escrita, deberán contener lo siguiente: el nombre del proveedor al que se dirige (POLITÉCNICA), el nombre, identificación y dirección de notificación del USUARIO, y los hechos en que se fundamenta la solicitud. POLITÉCNICA le informará por cualquier medio físico o electrónico la constancia de presentación de la PQR y un código único numérico –CUN-, el cual deberá mantenerse durante todo el trámite.(Art 39 y ss. Res 3066/2011 CRC).
                            </td>
                        </tr>
                        <tr>
                            <td text-align=center;'><b>MECANISMOS DE ATENCIÓN</b></td>
                        </tr>
                        <tr>
                            <td text-align=justify;font-size:9.5px'>
                      De acuerdo con lo establecido en la Resolución 3066 de 2011, POLITÉCNICA tiene a disposición del USUARIO los siguientes mecanismos de atención: Oficina Principal: Carrera 20 # 27-87 Oficina 302, Edificio Cámara de Comercio Sincelejo - Sucre. Línea Gratuita Nacional: 018000961016. Correo Electrónico:  soportecd2@soygenial.co.
                            </td>
                        </tr>
                    </table>
                    ";

            $contenidoPagina .= "
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>


 <table style='width:100%;border:none'>
            <tr>
                <td style='width:100%;border:none'>
                     <table style='width:100%;border:none'>
                    <tr>
                    <td style='width:25%;text-align:left;border:none'>FIRMA :</td>
                    <td style='width:75%;text-align:left;border:none'>_________________________</td>
                    </tr>
                    <tr>
                    <td style='width:25%;text-align:left;border:none'>Nombre Usuario:</td>
                    <td style='width:75%;text-align:left;border:none'>" . $beneficiario['nombres'] . " " . $beneficiario['primer_apellido'] . " " . $beneficiario['segundo_apellido'] . "</td>
                    </tr>
                    <tr>
                    <td style='width:25%;text-align:left;border:none'>C.C :</td>
                    <td style='width:75%;text-align:left;border:none'>" . number_format($beneficiario['numero_identificacion'], 0, '', '.') . "</td>
                    </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br>
        <br>
        <br>
        <br>
        <br>
        </nobreak>";

            $contenidoPagina .= "</page>";

        }

        $this->contenidoPagina = $contenidoPagina;
        unset($contenidoPagina);
        $contenidoPagina = NULL;
        unset($beneficiario);
        $beneficiario = NULL;
    }
}
$miDocumento = new GenerarDocumento($this->miSql, $this->proceso, $this->rutaAbsoluta_archivos);

?>
