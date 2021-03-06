<?php

namespace facturacion\calculoFactura\entidad;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "../index.php";
	exit ();
}

include_once 'Redireccionador.php';
include_once 'RestClient.class.php';
include_once 'sincronizarErp.php';
class FormProcessor {
	public $miConfigurador;
	public $lenguaje;
	public $miFormulario;
	public $miSql;
	public $conexion;
	public $esteRecursoDB;
	public $sincronizar;
	public function __construct($lenguaje, $sql) {
		date_default_timezone_set ( 'America/Bogota' );
		
		$this->miConfigurador = \Configurador::singleton ();
		$this->miConfigurador->fabricaConexiones->setRecursoDB ( 'principal' );
		$this->lenguaje = $lenguaje;
		$this->miSql = $sql;
		
		$this->rutaURL = $this->miConfigurador->getVariableConfiguracion ( "host" ) . $this->miConfigurador->getVariableConfiguracion ( "site" );
		
		$this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion ( "raizDocumento" );
		
		$this->sincronizar = new sincronizarErp ( $lenguaje, $sql );
		
		if (! isset ( $_REQUEST ["bloqueGrupo"] ) || $_REQUEST ["bloqueGrupo"] == "") {
			$this->rutaURL .= "/blocks/" . $_REQUEST ["bloque"] . "/";
			$this->rutaAbsoluta .= "/blocks/" . $_REQUEST ["bloque"] . "/";
		} else {
			$this->rutaURL .= "/blocks/" . $_REQUEST ["bloqueGrupo"] . "/" . $_REQUEST ["bloque"] . "/";
			$this->rutaAbsoluta .= "/blocks/" . $_REQUEST ["bloqueGrupo"] . "/" . $_REQUEST ["bloque"] . "/";
		}
		// Conexion a Base de Datos
		$conexion = "interoperacion";
		$this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
		
		$_REQUEST ['tiempo'] = time ();
		
		/**
		 * 1.
		 * Organizar Información por Roles
		 */
		
		$this->ordenarInfoRoles ();
		
		/**
		 * 2.
		 * Recuperar Reglas por Rol
		 */
		
		$this->reglasRol ();
		$this->consultarUsuarioRol ();
		
		$resultado = $this->verificarFactura ();
		
		if ($resultado > 0) {
			// Mensaje de factura para el ciclo generada
			Redireccionador::redireccionar ( "ErrorFactura", '' );
		} else {
			$this->datosContrato ();
			$this->estadoServicio ();
			
			/**
			 * 4.
			 * Calcular Valores
			 */
			$this->reducirFormula ();
			
			$this->calculoPeriodo ();
			$this->registrarPeriodo ();
			$this->revisarMora ();
			$this->calculoMora ();
			$this->calculoFactura ();
			
			/**
			 * 5.
			 * Guardar Conceptos de Facturación
			 */
			
			$this->guardarFactura ();
			
			$this->guardarConceptos ();
			
			/**
			 * Crear Cliente
			 */
			$this->consultarCliente ();
			
			if ($this->registroConceptos ['resultado'] == 0 && $this->clienteEstado == 'f') {
				// // Crear el cliente
				$clienteURL = $this->sincronizar->crearUrlCliente ( $_REQUEST ['id_beneficiario'] );
				$clienteCrear = $this->sincronizar->crearCliente ( $clienteURL );
				if ($clienteCrear ['estado'] == 1) {
					$this->registroConceptos ['cliente'] [0] = 'Cliente no creado correctamente. Crearlo en ERPNext';
				} elseif ($clienteCrear ['estado'] == 0) {
					
					$cadenaSql = $this->miSql->getCadenaSql ( 'updateestadoCliente', $_REQUEST ['id_beneficiario'] );
					$updatecliente = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
					
					$this->registroConceptos ['cliente'] [0] = 'Cliente creado correctamente.';
					
					$facturaCrearURL = $this->sincronizar->crearUrlFactura ( $this->informacion_factura );
					$facturaCrear = $this->sincronizar->crearFactura ( $facturaCrearURL );
				}
			} else {
				$facturaCrearURL = $this->sincronizar->crearUrlFactura ( $this->informacion_factura );
				$facturaCrear = $this->sincronizar->crearFactura ( $facturaCrearURL );
			}
			
			if ($facturaCrear ['estado'] == 0) {
				$invoice = array (
						'invoice' => $facturaCrear ['recibo'],
						'id_factura' => $this->informacion_factura ['id_factura'] 
				);
				
				$this->registroConceptos ['cliente'] [1] = 'Factura creada en ERPNext.';
				$this->sincronizar->actualizarFactura ( $invoice );
			} else {
				$this->registroConceptos ['cliente'] [1] = '<b>Error en la creación de Factura en ERPNext.</b>';
			}
			
			/**
			 * 6.
			 * Revisar Resultado Proceso
			 */
			if ($this->registroConceptos ['resultado'] == 0) {
				Redireccionador::redireccionar ( "ExitoInformacion", $this->registroConceptos ['cliente'] );
			} else {
				Redireccionador::redireccionar ( "ErrorInformacion", $this->registroConceptos ['resultado'] );
			}
		}
	}
	public function ordenarInfoRoles() {
		$roles = json_decode ( base64_decode ( $_REQUEST ['roles'] ), true );
		
		foreach ( $roles as $data => $valor ) {
			foreach ( $_REQUEST as $key => $values ) {
				if (strncmp ( $key, "data", 4 ) === 0) {
					if (strtok ( substr ( $key, 4 ), "_" ) === $roles [$data] ['id_rol']) {
						if (strpos ( $key, 'periodo' ) !== false) {
							$p = $values;
						}
						if (strpos ( $key, 'cantidad' ) !== false) {
							$c = $values;
						}
						if (strpos ( $key, 'fecha' ) !== false) {
							$f = $values;
						}
					}
				}
			}
			
			$rolPeriodo [$roles [$data] ['id_rol']] = array (
					'periodo' => $p,
					'cantidad' => $c,
					'fecha' => date ( "Y/m/d H:i:s", strtotime ( $f ) ),
					'reglas' => array () 
			);
		}
		
		$this->rolesPeriodo = $rolPeriodo;
	}
	public function reglasRol() {
		foreach ( $this->rolesPeriodo as $key => $vales ) {
			
			$cadenaSql = $this->miSql->getCadenaSql ( 'consultarReglas', $key );
			$reglas = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
			foreach ( $reglas as $a => $b ) {
				$this->rolesPeriodo [$key] ['reglas'] [$reglas [$a] ['identificador']] = $reglas [$a] ['formula'];
				$this->rolesPeriodo [$key] ['mora'] = 0;
				$this->rolesPeriodo [$key] ['totalMora'] = 0;
			}
		}
	}
	public function consultarUsuarioRol() {
		$cadenaSql = $this->miSql->getCadenaSql ( 'consultarUsuarioRol', $_REQUEST ['id_beneficiario'] );
		$this->idUsuarioRol = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
		
		foreach ( $this->idUsuarioRol as $key => $values ) {
			foreach ( $this->rolesPeriodo as $llave => $valores ) {
				if ($this->idUsuarioRol [$key] ['id_rol'] == $llave) {
					$this->rolesPeriodo [$llave] ['id_usuario_rol'] = $this->idUsuarioRol [$key] ['id_usuario_rol'];
				}
			}
		}
	}
	public function verificarFactura() {
		$res = 0;
		foreach ( $this->rolesPeriodo as $key => $vales ) {
			foreach ( $this->rolesPeriodo as $llave => $valores ) {
				
				$ciclo = date ( "Y", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) ) . '-' . date ( "m", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) );
				$datos = array (
						'id_usuario_rol' => $this->rolesPeriodo [$llave] ['id_usuario_rol'],
						'id_ciclo' => $ciclo,
						'id_beneficiario' => $_REQUEST ['id_beneficiario'] 
				);
				
				$cadenaSql = $this->miSql->getCadenaSql ( 'consultarFactura', $datos );
				$resultado = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
				
				if ($resultado != FALSE) {
					$this->registroConceptos ['observaciones'] = 'Ya existe una factura para el ciclo ' . $ciclo;
					$res ++;
				} else {
					$res = 0;
				}
			}
		}
		
		return $res;
	}
	public function datosContrato() {
		$cadenaSql = $this->miSql->getCadenaSql ( 'consultarContrato', $_REQUEST ['id_beneficiario'] );
		$this->datosContrato = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0];
	}
	public function reducirFormula() {
		$contar = 0;
		$formula_base = 0;
		
		do {
			
			foreach ( $this->rolesPeriodo as $key => $values ) {
				foreach ( $values ['reglas'] as $variable => $c ) {
					foreach ( $values ['reglas'] as $incognita => $d ) {
						$incognita = preg_replace ( "/\b" . $incognita . "\b/", $d, $c, - 1, $contar );
						if ($contar == 1) {
							$this->rolesPeriodo [$key] ['reglas'] [$variable] = $incognita;
							$termina = false;
						}
					}
					$formula_base = $formula_base . "+" . $this->rolesPeriodo [$key] ['reglas'] [$variable];
				}
				$formulaRol [$key] = $formula_base;
				$formula_base = 0;
			}
			
			$termina = true;
		} while ( $termina == false );
		
		$this->formularRolGlobal = $formulaRol;
	}
	public function calculoPeriodo() {
		foreach ( $this->rolesPeriodo as $key => $values ) {
			
			$cadenaSql = $this->miSql->getCadenaSql ( 'consultarPeriodo', $values ['periodo'] );
			$periodoUnidad = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] ['valor'];
			
			$this->rolesPeriodo [$key] ['periodoValor'] = ( double ) ($periodoUnidad);
		}
	}
	public function calculoMora() {
		$cadenaSql = $this->miSql->getCadenaSql ( 'consultarMoras', $_REQUEST ['id_beneficiario'] );
		$facturasVencidas = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
		
		$dm = 0;
		
		if ($facturasVencidas != FALSE) {
			
			foreach ( $facturasVencidas as $llave => $valor ) {
				foreach ( $this->rolesPeriodo as $key => $values ) {
					
					if ($values ['id_usuario_rol'] == $facturasVencidas [$llave] ['id_usuario_rol']) {
						$fin = new \DateTime ( $facturasVencidas [$llave] ['fin_periodo'] );
						$inicio = new \DateTime ( $facturasVencidas [$llave] ['inicio_periodo'] );
						$dm_calculo = $fin->diff ( $inicio );
						$dias = $dm_calculo->d;
						
						$this->rolesPeriodo [$key] ['mora'] = $this->rolesPeriodo [$key] ['mora'] + $dias;
						$this->rolesPeriodo [$key] ['facturasMora'] [$facturasVencidas [$llave] ['id_factura'] . "(" . $facturasVencidas [$llave] ['id_ciclo'] . ")"] = $facturasVencidas [$llave] ['total_factura'];
						$this->rolesPeriodo [$key] ['totalMora'] = $this->rolesPeriodo [$key] ['totalMora'] + $facturasVencidas [$llave] ['total_factura'];
					}
				}
			}
		} else {
			foreach ( $this->rolesPeriodo as $key => $values ) {
				$this->rolesPeriodo [$key] ['mora'] = 0;
			}
		}
	}
	
	// Registrar el ciclo de facturación de acuerdo al periodo seleccionado
	public function registrarPeriodo() {
		foreach ( $this->rolesPeriodo as $key => $values ) {
			
			// Acá se debe controlar el ciclo de facturación
			$dia = date ( 'd', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+ 1 day' ) );
			
			$fecha_fin_mes = date ( "Y-m-t", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) );
			
			if ($dia != 1) {
				if ($this->rolesPeriodo [$key] ['periodoValor'] == 1) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $fecha_fin_mes ) );
				} elseif ($this->rolesPeriodo [$key] ['periodoValor'] == 720) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+' . $values ['cantidad'] . ' hours' ) );
				} elseif ($this->rolesPeriodo [$key] ['periodoValor'] == 30) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+' . $values ['cantidad'] . ' days' ) );
				} else {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+ 1 month' ) );
				}
			} else {
				// Aquí se aumentan los periodos de facturacion
				
				if ($this->rolesPeriodo [$key] ['periodoValor'] == 1) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+ 1 month' ) );
				} elseif ($this->rolesPeriodo [$key] ['periodoValor'] == 720) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+' . $values ['cantidad'] . ' hours' ) );
				} elseif ($this->rolesPeriodo [$key] ['periodoValor'] == 30) {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+' . $values ['cantidad'] . ' days' ) );
				} else {
					$fin = date ( 'Y/m/d H:i:s', strtotime ( $this->rolesPeriodo [$key] ['fecha'] . '+ 1 month' ) );
				}
			}
			
			// En un mundo ideal un float alcanzaría para dates basados en meses ((1 / $this->rolesPeriodo [$key] ['periodoValor']) * $values ['cantidad']);
			
			$usuariorolperiodo = array (
					'id_usuario_rol' => $this->rolesPeriodo [$key] ['id_usuario_rol'],
					'id_periodo' => $this->rolesPeriodo [$key] ['periodo'],
					'inicio_periodo' => $this->rolesPeriodo [$key] ['fecha'],
					'fin_periodo' => $fin,
					'id_ciclo' => date ( "Y", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) ) . '-' . date ( "m", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) ) 
			);
			
			$cadenaSql = $this->miSql->getCadenaSql ( 'registrarPeriodoRolUsuario', $usuariorolperiodo );
			$periodoRolUsuario = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] ['id_usuario_rol_periodo'];
			$this->rolesPeriodo [$key] ['id_usuario_rol_periodo'] = $periodoRolUsuario;
			$this->rolesPeriodo [$key] ['finPeriodo'] = $fin;
		}
	}
	public function calculoFactura() {
		$total = 0;
		$vm = $this->datosContrato ['vm'];
		$factura = 0;
		
		if ($this->estadoServicio != 'ET0') {
			$vm = 0;
		}
		
		foreach ( $this->rolesPeriodo as $key => $values ) {
			$total = 0;
			foreach ( $values ['reglas'] as $variable => $c ) {
				$a = preg_replace ( "/\bvm\b/", ($vm / $values ['periodoValor']) * $values ['cantidad'], $c, - 1, $contar );
				$b = preg_replace ( "/\bdm\b/", $values ['mora'], $a, - 1, $contar );
				$valor = eval ( 'return (' . $b . ');' );
				$this->rolesPeriodo [$key] ['valor'] [$variable] = $valor;
				$total = $total + $this->rolesPeriodo [$key] ['valor'] [$variable];
			}
			
			$factura = $factura + $total;
			$this->rolesPeriodo [$key] ['valor'] ['vm'] = $vm ;
			$this->rolesPeriodo [$key] ['valor'] ['total'] = $total;
		}
	}
	public function guardarFactura() {
		$total = 0;
		
		foreach ( $this->rolesPeriodo as $key => $values ) {
			$mora = $this->rolesPeriodo [$key] ['totalMora'];
			break;
		}
		
		foreach ( $this->rolesPeriodo as $key => $values ) {
			
			$total = $this->rolesPeriodo [$key] ['valor'] ['total'] + $total + $mora;
			$ciclo = date ( "Y", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) ) . '-' . date ( "m", strtotime ( $this->rolesPeriodo [$key] ['fecha'] ) );
		}
		
		$this->informacion_factura = array (
				'total_factura' => $total,
				'id_beneficiario' => $_REQUEST ['id_beneficiario'],
				'id_ciclo' => $ciclo,
				'fecha' => $this->rolesPeriodo [$key] ['finPeriodo'] 
		);
		
		$cadenaSql = $this->miSql->getCadenaSql ( 'registrarFactura', $this->informacion_factura );
		$this->registroFactura ['factura'] = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] ['id_factura'];
		
		$this->informacion_factura ['id_factura'] = $this->registroFactura ['factura'];
	}
	public function guardarConceptos() {
		$this->registroConceptos ['observaciones'] = '';
		$a = 0;
		
		foreach ( $this->rolesPeriodo as $key => $values ) {
			foreach ( $values ['reglas'] as $llave => $valores ) {
				$cadenaSql = $this->miSql->getCadenaSql ( 'consultarReglaID', $llave );
				$reglaid = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] ['id_regla'];
				
				$registroConceptos = array (
						'id_factura' => $this->registroFactura ['factura'],
						'id_regla' => $reglaid,
						'valor_calculado' => $values ['valor'] [$llave],
						'id_usuario_rol_periodo' => $this->rolesPeriodo [$key] ['id_usuario_rol_periodo'],
						'observacion' => '' 
				);
				
				$cadenaSql = $this->miSql->getCadenaSql ( 'registrarConceptos', $registroConceptos );
				$this->registroConceptos [$key] = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "registro" );
				
				if ($this->registroConceptos [$key] == false) {
					$a ++;
				}
			}
		}
		
		if (isset ( $values ['facturasMora'] )) {
			foreach ( $this->rolesPeriodo as $key => $values ) {
				foreach ( $values ['facturasMora'] as $llave => $valores ) {
					$cadenaSql = $this->miSql->getCadenaSql ( 'consultarReglaID', 'facturasMora' );
					$reglaid = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] ['id_regla'];
					
					$registroConceptos = array (
							'id_factura' => $this->registroFactura ['factura'],
							'id_regla' => $reglaid,
							'valor_calculado' => $valores,
							'id_usuario_rol_periodo' => $this->rolesPeriodo [$key] ['id_usuario_rol_periodo'],
							'observacion' => $llave 
					);
					
					$cadenaSql = $this->miSql->getCadenaSql ( 'registrarConceptos', $registroConceptos );
					$this->registroConceptos [$key] = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "registro" );
					
					if ($this->registroConceptos [$key] == false) {
						$a ++;
					}
				}
				break;
			}
		}
		
		$this->registroConceptos ['resultado'] = $a;
		
		if ($a == 0) {
			$this->registroConceptos ['observaciones'] = 'Factura Generada Exitosamente';
		} else {
			$this->registroConceptos ['observaciones'] = 'Error en la generación de la factura';
		}
	}
	public function consultarCliente() {
		$this->registroConceptos ['cliente'] [0] = '';
		
		$cadenaSql = $this->miSql->getCadenaSql ( 'estadoCliente', $_REQUEST ['id_beneficiario'] );
		$this->clienteEstado = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] [0];
	}
	public function revisarMora() {
		$cadenaSql = $this->miSql->getCadenaSql ( 'revisarMora', $_REQUEST ['id_beneficiario'] );
		$moras = $this->esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
	}
	public function estadoServicio() {
		$conexion2 = "otun";
		$this->esteRecursoDBOtun = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion2 );
		
		$cadenaSql = $this->miSql->getCadenaSql ( 'estadoServicio', $_REQUEST ['id_beneficiario'] );
		$this->estadoServicio = $this->esteRecursoDBOtun->ejecutarAcceso ( $cadenaSql, "busqueda" ) [0] [0];
	}
}

$miProcesador = new FormProcessor ( $this->lenguaje, $this->sql );
?>

