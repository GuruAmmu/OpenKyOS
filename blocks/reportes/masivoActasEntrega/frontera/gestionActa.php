<?php

namespace reportes\masivoActas\frontera;

use reportes\masivoActas\entidad\GenerarDocumento;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "../index.php";
	exit ();
}
class GestionarContrato {
	public $miConfigurador;
	public $lenguaje;
	public $miFormulario;
	public $miSql;
	public $ruta;
	public $rutaURL;
	public function __construct($lenguaje, $formulario, $sql) {
		
		ini_set('memory_limit', '650M');
		ini_set('max_execution_time', 100000);
		
		$this->miConfigurador = \Configurador::singleton ();
		
		$this->miConfigurador->fabricaConexiones->setRecursoDB ( 'principal' );
		
		$this->lenguaje = $lenguaje;
		
		$this->miFormulario = $formulario;
		
		$this->miSql = $sql;
		
		$esteBloque = $this->miConfigurador->configuracion ['esteBloque'];
		
		$this->ruta = $this->miConfigurador->getVariableConfiguracion ( "raizDocumento" );
		$this->rutaURL = $this->miConfigurador->getVariableConfiguracion ( "host" ) . $this->miConfigurador->getVariableConfiguracion ( "site" );
		
		if (! isset ( $esteBloque ["grupo"] ) || $esteBloque ["grupo"] == "") {
			$ruta .= "/blocks/" . $esteBloque ["nombre"] . "/";
			$this->rutaURL .= "/blocks/" . $esteBloque ["nombre"] . "/";
		} else {
			$this->ruta .= "/blocks/" . $esteBloque ["grupo"] . "/" . $esteBloque ["nombre"] . "/";
			$this->rutaURL .= "/blocks/" . $esteBloque ["grupo"] . "/" . $esteBloque ["nombre"] . "/";
		}
	}
	public function formulario() {
		include_once $this->ruta . "entidad/guardarDocumentoCertificacion.php";
		
		$beneficiarios = explode ( ", ", $_REQUEST ['beneficiario'] );
		
		$esteBloque = $this->miConfigurador->getVariableConfiguracion ( "esteBloque" );
		$miPaginaActual = $this->miConfigurador->getVariableConfiguracion ( "pagina" );
		
		$conexion = "produccion";
		$esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
		
		$_REQUEST['beneficiario'] ='5CG6122Z7J, 5CG6122Z8Y, 5CG6122Z28, 5CG6122ZBR, 5CG6122ZC8, 5CG6122ZF8, 5CG6122ZNP, 5CG6122ZPT, 5CG6122ZWL, 5CG6122ZX7, 5CG6122ZY0, 5CG6122ZZR, 5CG6123B2G, 5CG6123BJP, 5CG6123BKL, 5CG6123BMV, 5CG6123BQT, 5CG6123BW8, 5CG6123BWX, 5CG6123BX5, 5CG6130BYM, 5CG6130C8S, 5CG6130CK2, 5CG6130CLL, 5CG6130CQ6, 5CG6130CRD, 5CG6130D0S, 5CG6130D5V, 5CG6130DB3, 5CG6130DRF, 5CG6130FDB, 5CG6130FP3, 5CG6130FZ1, 5CG6130GB0, 5CG6130GBV, 5CG6130GD2, 5CG6130GDG, 5CG6130GJL, 5CG6130GJY, 5CG6130GLK, 5CG6130GMY, 5CG6130GNJ, 5CG6130GQN, 5CG6130GZP, 5CG6130H1H, 5CG6130HBC, 5CG6130HDR, 5CG6130HPV, 5CG6130HQN, 5CG6130HQY, 5CG6130HXW, 5CG6130J13, 5CG6130JBQ, 5CG6130JF2, 5CG6130JHG, 5CG6130JJK, 5CG6130JQH, 5CG6130JT5, 5CG6130JVK, 5CG6130JWQ, 5CG6130JY3, 5CG6130K2K, 5CG6130K25, 5CG6130K44, 5CG6130KRB, 5CG6130KRY, 5CG6130KVF, 5CG6130KW6, 5CG6130KX3, 5CG6130KXD, 5CG6130KXV, 5CG6130KY7, 5CG6130L3N, 5CG6130LBC, 5CG6130LF0, 5CG6130LHN, 5CG6130LX1, 5CG6130LXC, 5CG6130LZ4, 5CG6130M0S, ';
		
		$contratos = explode(", ",$_REQUEST['beneficiario']);

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
		
		foreach ( $contratos as $generarActa ) {
			
			$cadenaSql = $this->miSql->getCadenaSql ( 'consultarInformacionActa', $generarActa);
			$infoCertificado = $esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" )[0];

			$_REQUEST = $infoCertificado;
			
			$_REQUEST['fecha_instalacion'] = date("d") . "-" . date("m") . "-" . date("Y");
			$miDocumento = new GenerarDocumento ();
			$miDocumento->crearActa ( $this->miSql, $this->rutaURL, $generarActa, $this->lenguaje);
			
			unset ( $miDocumento );
			$miDocumento = NULL;
			
			unset ( $_REQUEST );
			$_REQUEST = NULL;
			
			$cadenaSql = NULL;
			
			unset($infoCertificado);
			$infoCertificado = NULL;
			
			unset($beneficiarios);
			$beneficiarios = NULL;
			
			echo $generarActa . "<br>";
		}
		
		// $esteBloque = $this->miConfigurador->getVariableConfiguracion("esteBloque");
		// $miPaginaActual = $this->miConfigurador->getVariableConfiguracion("pagina");
		
		// $conexion = "interoperacion";
		// $esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
		
		// $_REQUEST ['id_beneficiario'] = $_REQUEST ['id'];
		// $_REQUEST['mensaje'] = "insertoInformacionCertificado";
		
		// $cadenaSql = $this->miSql->getCadenaSql ( 'consultaInformacionCertificado' );
		// $infoCertificado = $esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0];
		
		// if($infoCertificado){
		
		// $cadenaSql = $this->miSql->getCadenaSql('consultaInformacionBeneficiario');
		// $infoBeneficiario = $esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];
		
		// $_REQUEST = array_merge($_REQUEST, $infoBeneficiario);
		
		// $_REQUEST = array_merge($_REQUEST, $infoCertificado);
		
		// $_REQUEST['nombres'] = $_REQUEST['nombre'];
		// $_REQUEST['numero_identificacion'] = $_REQUEST['identificacion'];
		
		// $_REQUEST['mensaje'] = "insertoInformacionCertificado";
		
		// if($infoCertificado['firmabeneficiario'] != "" && $infoCertificado['ruta_documento_ps'] == ""){
		// $_REQUEST['firmabeneficiario'] = $infoCertificado['firmabeneficiario'];
		// include_once $this->ruta . "entidad/guardarDocumentoCertificacion.php";
		// }else if($infoCertificado['firmabeneficiario_aes'] != "" && $infoCertificado['ruta_documento_ps'] == ""){
		// $_REQUEST['firmabeneficiario'] = $infoCertificado['firmabeneficiario_aes'];
		// include_once $this->ruta . "entidad/guardarDocumentoCertificacion.php";
		// }
		
		// $cadenaSql = $this->miSql->getCadenaSql ( 'consultaInformacionCertificado' );
		// $infoCertificado = $esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0];
		
		// $_REQUEST = array_merge($_REQUEST, $infoCertificado);
		
		// {
		
		// $anexo_dir = '';
		
		// if ($infoBeneficiario['manzana_contrato'] != 0) {
		// $anexo_dir .= " Manzana #" . $infoBeneficiario['manzana_contrato'] . " - ";
		// }
		
		// if ($infoBeneficiario['bloque_contrato'] != 0) {
		// $anexo_dir .= " Bloque #" . $infoBeneficiario['bloque_contrato'] . " - ";
		// }
		
		// if ($infoBeneficiario['torre_contrato'] != 0) {
		// $anexo_dir .= " Torre #" . $infoBeneficiario['torre_contrato'] . " - ";
		// }
		
		// if ($infoBeneficiario['casa_apto_contrato'] != 0) {
		// $anexo_dir .= " Casa/Apartamento #" . $infoBeneficiario['casa_apto_contrato'];
		// }
		
		// if ($infoBeneficiario['interior_contrato'] != 0) {
		// $anexo_dir .= " Interior #" . $infoBeneficiario['interior_contrato'];
		// }
		
		// if ($infoBeneficiario['lote_contrato'] != 0) {
		// $anexo_dir .= " Lote #" . $infoBeneficiario['lote_contrato'];
		// }
		
		// }
		// }else{
		// $_REQUEST['mensaje'] = "certificadoNoDisponible";
		// }
		
		// // Rescatar los datos de este bloque
		
		// // ---------------- SECCION: Parámetros Globales del Formulario ----------------------------------
		
		// {
		// $atributosGlobales['campoSeguro'] = 'true';
		// }
		
		// $_REQUEST['tiempo'] = time();
		// // -------------------------------------------------------------------------------------------------
		
		// // ---------------- SECCION: Parámetros Generales del Formulario ----------------------------------
		// $esteCampo = $esteBloque['nombre'];
		// $atributos['id'] = $esteCampo;
		// $atributos['nombre'] = $esteCampo;
		// // Si no se coloca, entonces toma el valor predeterminado 'application/x-www-form-urlencoded'
		// $atributos['tipoFormulario'] = 'multipart/form-data';
		// // Si no se coloca, entonces toma el valor predeterminado 'POST'
		// $atributos['metodo'] = 'POST';
		// // Si no se coloca, entonces toma el valor predeterminado 'index.php' (Recomendado)
		// $atributos['action'] = 'index.php';
		// $atributos['titulo'] = $this->lenguaje->getCadena($esteCampo);
		// // Si no se coloca, entonces toma el valor predeterminado.
		// $atributos['estilo'] = '';
		// $atributos['marco'] = true;
		// $tab = 1;
		// // ---------------- FIN SECCION: de Parámetros Generales del Formulario ----------------------------
		
		// // ----------------INICIAR EL FORMULARIO ------------------------------------------------------------
		// $atributos['tipoEtiqueta'] = 'inicio';
		// echo $this->miFormulario->formulario($atributos);
		
		// {
		// {
		// $esteCampo = 'Agrupacion';
		// $atributos['id'] = $esteCampo;
		// $atributos['leyenda'] = "ACTA DE ENTREGA DE PORTATIL Y SERVICIOS";
		// echo $this->miFormulario->agrupacion('inicio', $atributos);
		// unset($atributos);
		
		// {
		
		// $this->mensaje();
		
		// if($infoCertificado){
		// // ------------------Division para los botones-------------------------
		// $atributos["id"] = "botones";
		// $atributos["estilo"] = "marcoBotones";
		// $atributos["estiloEnLinea"] = "display:block;";
		// echo $this->miFormulario->division("inicio", $atributos);
		// unset($atributos);
		
		// // Acordar Roles
		
		// {
		
		// $url = $this->miConfigurador->getVariableConfiguracion("host");
		// $url .= $this->miConfigurador->getVariableConfiguracion("site");
		// $url .= "/index.php?";
		
		// // ------------------Division para los botones-------------------------
		// $atributos["id"] = "botones_sin";
		// $atributos["estilo"] = "marcoBotones";
		// $atributos["estiloEnLinea"] = "display:block;";
		// echo $this->miFormulario->division("inicio", $atributos);
		// unset($atributos);
		
		// {
		
		// $valorCodificado = "action=" . $esteBloque["nombre"];
		// $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
		// $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
		// $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
		// $valorCodificado .= "&id_beneficiario=" . $_REQUEST['id_beneficiario'];
		// $valorCodificado .= "&opcion=generarCertificacion";
		// $valorCodificado .= "&tipo_beneficiario=" . $infoBeneficiario['tipo_beneficiario'];
		// $valorCodificado .= "&numero_contrato=" . $infoBeneficiario['numero_contrato'];
		// $valorCodificado .= "&estrato_socioeconomico=" . $infoBeneficiario['estrato_socioeconomico'];
		
		// $enlace = $this->miConfigurador->getVariableConfiguracion("enlace");
		// $cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url($valorCodificado, $enlace);
		
		// $urlpdfNoFirmas = $url . $cadena;
		
		// echo "<b><a id='link_b' href='" . $urlpdfNoFirmas . "'>Acta Entrega de Servicios Instalados <br> Sin Firma</a></b>";
		
		// }
		
		// // ------------------Fin Division para los botones-------------------------
		// echo $this->miFormulario->division("fin");
		// unset($atributos);
		
		// // ------------------Division para los botones-------------------------
		// $atributos["id"] = "botones_pdf";
		// $atributos["estilo"] = "marcoBotones";
		// $atributos["estiloEnLinea"] = "display:block;";
		// echo $this->miFormulario->division("inicio", $atributos);
		// unset($atributos);
		
		// {
		// echo "<b><a id='link_a' target='_blank' href='" . $infoCertificado['ruta_documento_ps'] . "'>Acta Entrega de Servicios Instalados <br> Con Firma</a></b>";
		// }
		
		// // ------------------Fin Division para los botones-------------------------
		// echo $this->miFormulario->division("fin");
		// unset($atributos);
		
		// }
		
		// // ------------------Fin Division para los botones-------------------------
		// echo $this->miFormulario->division("fin");
		// unset($atributos);
		// }
		
		// }
		// echo $this->miFormulario->agrupacion('fin');
		// unset($atributos);
		// }
		
		// }
		
		// ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
		// Se debe declarar el mismo atributo de marco con que se inició el formulario.
		$atributos ['marco'] = true;
		$atributos ['tipoEtiqueta'] = 'fin';
		echo $this->miFormulario->formulario ( $atributos );
	}
	public function mensaje() {
		switch ($_REQUEST ['mensaje']) {
			
			case 'insertoInformacionCertificado' :
				$estilo_mensaje = 'success'; // information,warning,error,validation
				$atributos ["mensaje"] = '<b>Acta de Entrega Disponible</b>';
				break;
			
			case 'certificadoNoDisponible' :
				$estilo_mensaje = 'error'; // information,warning,error,validation
				$atributos ["mensaje"] = 'Al parecer no se ha generado el Acta de Entrega de Portatil o el Acta de Entrega de Servicios<b>';
				break;
		}
		// ------------------Division para los botones-------------------------
		$atributos ['id'] = 'divMensaje';
		$atributos ['estilo'] = 'marcoBotones';
		echo $this->miFormulario->division ( "inicio", $atributos );
		
		// -------------Control texto-----------------------
		$esteCampo = 'mostrarMensaje';
		$atributos ["tamanno"] = '';
		$atributos ["etiqueta"] = '';
		$atributos ["estilo"] = $estilo_mensaje;
		$atributos ["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
		echo $this->miFormulario->campoMensaje ( $atributos );
		unset ( $atributos );
		
		// ------------------Fin Division para los botones-------------------------
		echo $this->miFormulario->division ( "fin" );
		unset ( $atributos );
	}
	public function mensajeModal() {
		switch ($_REQUEST ['mensaje']) {
			
			case 'insertoInformacionContrato' :
				$mensaje = "Exito en el registro información del Acta de Entrega";
				$atributos ['estiloLinea'] = 'success'; // success,error,information,warning
				break;
			case 'errorGenerarArchivo' :
				$mensaje = "Error en el registro de información del Acta de Entrega";
				$atributos ['estiloLinea'] = 'error'; // success,error,information,warning
				
				break;
		}
		
		// ----------------INICIO CONTROL: Ventana Modal Beneficiario Eliminado---------------------------------
		
		$atributos ['tipoEtiqueta'] = 'inicio';
		$atributos ['titulo'] = 'Mensaje';
		$atributos ['id'] = 'mensaje';
		echo $this->miFormulario->modal ( $atributos );
		unset ( $atributos );
		
		// ----------------INICIO CONTROL: Mapa--------------------------------------------------------
		echo '<div style="text-align:center;">';
		
		echo '<p><h5>' . $mensaje . '</h5></p>';
		
		echo '</div>';
		
		// ----------------FIN CONTROL: Mapa--------------------------------------------------------
		
		echo '<div style="text-align:center;">';
		
		echo '</div>';
		
		$atributos ['tipoEtiqueta'] = 'fin';
		echo $this->miFormulario->modal ( $atributos );
		unset ( $atributos );
	}
}

$miSeleccionador = new GestionarContrato ( $this->lenguaje, $this->miFormulario, $this->sql );

$miSeleccionador->formulario ();

?>
