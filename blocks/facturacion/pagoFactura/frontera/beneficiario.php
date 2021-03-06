<?php

namespace facturacion\pagoFactura\frontera;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "../index.php";
	exit ();
}

/**
 * IMPORTANTE: Este formulario está utilizando jquery.
 * Por tanto en el archivo ready.php se declaran algunas funciones js
 * que lo complementan.
 */
class Consultar {
	public $miConfigurador;
	public $lenguaje;
	public $miFormulario;
	public function __construct($lenguaje, $formulario) {
		$this->miConfigurador = \Configurador::singleton ();
		
		$this->miConfigurador->fabricaConexiones->setRecursoDB ( 'principal' );
		
		$this->lenguaje = $lenguaje;
		
		$this->miFormulario = $formulario;
	}
	public function seleccionarForm() {
		$_REQUEST ['id_beneficiario'] = '';
		// Rescatar los datos de este bloque
		$esteBloque = $this->miConfigurador->getVariableConfiguracion ( "esteBloque" );
		
		// ---------------- SECCION: Parámetros Globales del Formulario ----------------------------------
		
		$atributosGlobales ['campoSeguro'] = 'true';
		
		$_REQUEST ['tiempo'] = time ();
		// -------------------------------------------------------------------------------------------------
		
		// ---------------- SECCION: Parámetros Generales del Formulario ----------------------------------
		$esteCampo = $esteBloque ['nombre'];
		$atributos ['id'] = $esteCampo;
		$atributos ['nombre'] = $esteCampo;
		// Si no se coloca, entonces toma el valor predeterminado 'application/x-www-form-urlencoded'
		$atributos ['tipoFormulario'] = '';
		// Si no se coloca, entonces toma el valor predeterminado 'POST'
		$atributos ['metodo'] = 'POST';
		// Si no se coloca, entonces toma el valor predeterminado 'index.php' (Recomendado)
		$atributos ['action'] = 'index.php';
		$atributos ['titulo'] = $this->lenguaje->getCadena ( $esteCampo );
		// Si no se coloca, entonces toma el valor predeterminado.
		$atributos ['estilo'] = '';
		$atributos ['marco'] = true;
		$tab = 1;
		// ---------------- FIN SECCION: de Parámetros Generales del Formulario ----------------------------
		
		// ----------------INICIAR EL FORMULARIO ------------------------------------------------------------
		$atributos ['tipoEtiqueta'] = 'inicio';
		echo $this->miFormulario->formulario ( $atributos );
		{
			
			{
				$esteCampo = 'Agrupacion';
				$atributos ['id'] = $esteCampo;
				$atributos ['leyenda'] = "Consultar Beneficiario Facturas Asociadas";
				echo $this->miFormulario->agrupacion ( 'inicio', $atributos );
				unset ( $atributos );
				
				{
					
					{
						// ------------------Division para los botones-------------------------
						$atributos ['id'] = 'divMensaje';
						$atributos ['estilo'] = 'textoIzquierda';
						echo $this->miFormulario->division ( "inicio", $atributos );
						unset ( $atributos );
						{
							
							// // -------------Control texto-----------------------
							// $esteCampo = 'mostrarMensaje';
							// $atributos ["tamanno"] = '';
							// $atributos ["etiqueta"] = '';
							// $mensaje = '<center>Recuerde: Antes de Generar una Factura debe haber asociado roles de facturación para el usuario.</center>';
							
							// $atributos ["mensaje"] = $mensaje;
							// $atributos ["estilo"] = 'information'; // information,warning,error,validation
							// $atributos ["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
							// echo $this->miFormulario->campoMensaje ( $atributos );
							// unset ( $atributos );
						}
						// ------------------Fin Division para los botones-------------------------
						echo $this->miFormulario->division ( "fin" );
						unset ( $atributos );
					}
					// ----------------INICIO CONTROL: Lista Proyectos---------------------------
					
					$esteCampo = 'beneficiario';
					$atributos ['nombre'] = $esteCampo;
					$atributos ['tipo'] = "text";
					$atributos ['id'] = $esteCampo;
					$atributos ['etiqueta'] = $this->lenguaje->getCadena ( $esteCampo );
					$atributos ["etiquetaObligatorio"] = true;
					$atributos ['tab'] = $tab ++;
					$atributos ['anchoEtiqueta'] = 2;
					$atributos ['estilo'] = "bootstrap";
					$atributos ['evento'] = '';
					$atributos ['deshabilitado'] = false;
					$atributos ['readonly'] = false;
					$atributos ['columnas'] = 1;
					$atributos ['tamanno'] = 1;
					$atributos ['placeholder'] = "Ingrese Mínimo 3 Caracteres de Busqueda";
					$atributos ['valor'] = "";
					$atributos ['ajax_function'] = "";
					$atributos ['ajax_control'] = $esteCampo;
					$atributos ['limitar'] = false;
					$atributos ['anchoCaja'] = 6;
					$atributos ['miEvento'] = '';
					$atributos ['validar'] = 'required';
					// Aplica atributos globales al control
					$atributos = array_merge ( $atributos, $atributosGlobales );
					echo $this->miFormulario->campoCuadroTextoBootstrap ( $atributos );
					unset ( $atributos );
					
					$esteCampo = 'id_beneficiario';
					$atributos ["id"] = $esteCampo; // No cambiar este nombre
					$atributos ["tipo"] = "hidden";
					$atributos ['estilo'] = '';
					$atributos ["obligatorio"] = false;
					$atributos ['marco'] = true;
					$atributos ["etiqueta"] = "";
					if (isset ( $_REQUEST [$esteCampo] )) {
						$atributos ['valor'] = $_REQUEST [$esteCampo];
					} else {
						$atributos ['valor'] = '';
					}
					$atributos = array_merge ( $atributos, $atributosGlobales );
					echo $this->miFormulario->campoCuadroTexto ( $atributos );
					unset ( $atributos );
					
					// ------------------Division para los botones-------------------------
					$atributos ["id"] = "botones";
					$atributos ["estilo"] = "marcoBotones";
					$atributos ["estiloEnLinea"] = "display:block;";
					echo $this->miFormulario->division ( "inicio", $atributos );
					unset ( $atributos );
					{
						// -----------------CONTROL: Botón ----------------------------------------------------------------
						$esteCampo = 'botonValidar';
						$atributos ["id"] = $esteCampo;
						$atributos ["tabIndex"] = $tab;
						$atributos ["tipo"] = 'boton';
						// submit: no se coloca si se desea un tipo button genérico
						$atributos ['submit'] = true;
						$atributos ["simple"] = true;
						$atributos ["estiloMarco"] = '';
						$atributos ["estiloBoton"] = 'default';
						$atributos ["block"] = false;
						// verificar: true para verificar el formulario antes de pasarlo al servidor.
						$atributos ["verificar"] = '';
						$atributos ["tipoSubmit"] = 'jquery'; // Dejar vacio para un submit normal, en este caso se ejecuta la función submit declarada en ready.js
						$atributos ["valor"] = $this->lenguaje->getCadena ( $esteCampo );
						$atributos ['nombreFormulario'] = $esteBloque ['nombre'];
						$tab ++;
						
						// Aplica atributos globales al control
						$atributos = array_merge ( $atributos, $atributosGlobales );
						echo $this->miFormulario->campoBotonBootstrapHtml ( $atributos );
						unset ( $atributos );
						// -----------------FIN CONTROL: Botón -----------------------------------------------------------
					}
					// ------------------Fin Division para los botones-------------------------
					echo $this->miFormulario->division ( "fin" );
					unset ( $atributos );
				}
				echo $this->miFormulario->agrupacion ( 'fin' );
				unset ( $atributos );
			}
			
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
				
				$valorCodificado = "actionBloque=" . $esteBloque ["nombre"];
				$valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion ( 'pagina' );
				$valorCodificado .= "&bloque=" . $esteBloque ['nombre'];
				$valorCodificado .= "&bloqueGrupo=" . $esteBloque ["grupo"];
				$valorCodificado .= "&opcion=verFacturas";
				
				/**
				 * SARA permite que los nombres de los campos sean dinámicos.
				 * Para ello utiliza la hora en que es creado el formulario para
				 * codificar el nombre de cada campo.
				 */
				$valorCodificado .= "&campoSeguro=" . $_REQUEST ['tiempo'];
				// Paso 2: codificar la cadena resultante
				$valorCodificado = $this->miConfigurador->fabricaConexiones->crypto->codificar ( $valorCodificado );
				
				$atributos ["id"] = "formSaraData"; // No cambiar este nombre
				$atributos ["tipo"] = "hidden";
				$atributos ['estilo'] = '';
				$atributos ["obligatorio"] = false;
				$atributos ['marco'] = true;
				$atributos ["etiqueta"] = "";
				$atributos ["valor"] = $valorCodificado;
				echo $this->miFormulario->campoCuadroTexto ( $atributos );
				unset ( $atributos );
			}
		}
		
		// ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
		// Se debe declarar el mismo atributo de marco con que se inició el formulario.
		$atributos ['marco'] = true;
		$atributos ['tipoEtiqueta'] = 'fin';
		echo $this->miFormulario->formulario ( $atributos );
		
		if (isset ( $_REQUEST ['mensajeModal'] )) {
			
			$this->mensajeModal ();
		}
	}
	public function mensajeModal() {
		switch ($_REQUEST ['mensajeModal']) {
			
			case 'exitoInformacion' :
				$mensaje = "Exito<br>Conceptos de Factura calculados y asociados.";
				$atributos ['estiloLinea'] = 'success'; // success,error,information,warning
				break;
			
			case 'errorCreacion' :
				$mensaje = "Error<br> No fue posible registrar el pago a la factura.";
				$atributos ['estiloLinea'] = 'error'; // success,error,information,warning
				break;
			
			case 'errorActualizacion' :
				$mensaje = "Error<br> Se realizó el registro del pago pero NO fue posible actualizar el estado de factura. Avíse al administrador del sistema.";
				$atributos ['estiloLinea'] = 'error'; // success,error,information,warning
				break;
			
			case 'errorValor' :
				$mensaje = "Error<br>El valor recibido no cubre el valor de la deuda para la Factura.";
				$atributos ['estiloLinea'] = 'error'; // success,error,information,warning
				break;
		}
		
		// ----------------INICIO CONTROL: Ventana Modal Beneficiario Eliminado---------------------------------
		
		$atributos ['tipoEtiqueta'] = 'inicio';
		$atributos ['titulo'] = 'Mensaje';
		$atributos ['id'] = 'mensajeModal';
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

$miSeleccionador = new Consultar ( $this->lenguaje, $this->miFormulario );

$miSeleccionador->seleccionarForm ();

?>
