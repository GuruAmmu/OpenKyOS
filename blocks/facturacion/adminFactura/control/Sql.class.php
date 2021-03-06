<?php

namespace facturacion\adminFactura;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "../index.php";
	exit ();
}

include_once "core/manager/Configurador.class.php";
include_once "core/connection/Sql.class.php";

// Para evitar redefiniciones de clases el nombre de la clase del archivo sqle debe corresponder al nombre del bloque
// en camel case precedida por la palabra sql
class Sql extends \Sql {
	public $miConfigurador;
	public function getCadenaSql($tipo, $variable = '') {
		
		/**
		 * 1.
		 * Revisar las variables para evitar SQL Injection
		 */
		$prefijo = $this->miConfigurador->getVariableConfiguracion ( "prefijo" );
		$idSesion = $this->miConfigurador->getVariableConfiguracion ( "id_sesion" );
		
		switch ($tipo) {
			
			case 'consultarFactura' :
				$cadenaSql = " SELECT id_factura, total_factura, estado_factura, id_ciclo, fac.id_beneficiario, identificacion||' - '|| nombre ||' '|| primer_apellido ||' '||segundo_apellido as nombres ";
				$cadenaSql .= " FROM facturacion.factura fac";
				$cadenaSql .= " JOIN interoperacion.beneficiario_potencial bp on bp.id_beneficiario=fac.id_beneficiario";
				$cadenaSql .= " WHERE 1=1";
				$cadenaSql .= " AND fac.estado_registro=TRUE";
				$cadenaSql .= " AND bp.estado_registro=TRUE ORDER BY id_factura ASC ";
				break;
			
			case 'consultarFactura_especifico' :
				$cadenaSql = " SELECT fac.id_factura, total_factura, estado_factura, fac.id_ciclo, fac.id_beneficiario, identificacion||' - '|| nombre ||' '|| primer_apellido ||' '||segundo_apellido as nombres ,  ";
				$cadenaSql .= " regla.descripcion, conceptos.valor_calculado ";
				$cadenaSql .= " FROM facturacion.factura fac ";
				$cadenaSql .= " JOIN interoperacion.beneficiario_potencial bp on bp.id_beneficiario=fac.id_beneficiario ";
				$cadenaSql .= " JOIN facturacion.conceptos on conceptos.id_factura=fac.id_factura AND conceptos.estado_registro=TRUE ";
				$cadenaSql .= " JOIN facturacion.regla on regla.id_regla=conceptos.id_regla AND regla.estado_registro=TRUE ";
				$cadenaSql .= " WHERE 1=1 ";
				$cadenaSql .= " AND fac.estado_registro=TRUE ";
				$cadenaSql .= " AND bp.estado_registro=TRUE ";
				$cadenaSql .= " AND fac.id_factura='" . $variable . "' ";
				break;
			
			case 'consultarConceptos_especifico' :
				$cadenaSql = " SELECT fac.id_factura,fac.id_beneficiario,regla.descripcion regla, conceptos.valor_calculado, rol.descripcion, conceptos.id_usuario_rol_periodo, inicio_periodo, fin_periodo ";
				$cadenaSql .= " FROM facturacion.factura fac ";
				$cadenaSql .= " JOIN interoperacion.beneficiario_potencial bp on bp.id_beneficiario=fac.id_beneficiario ";
				$cadenaSql .= " JOIN facturacion.conceptos on conceptos.id_factura=fac.id_factura AND conceptos.estado_registro=TRUE ";
				$cadenaSql .= " JOIN facturacion.usuario_rol_periodo urp on conceptos.id_usuario_rol_periodo=urp.id_usuario_rol_periodo ";
				$cadenaSql .= " JOIN facturacion.regla on regla.id_regla=conceptos.id_regla AND regla.estado_registro=TRUE ";
				$cadenaSql .= " JOIN facturacion.usuario_rol on usuario_rol.id_beneficiario=fac.id_beneficiario AND usuario_rol.estado_registro=TRUE AND usuario_rol.id_usuario_rol=urp.id_usuario_rol ";
				$cadenaSql .= " JOIN facturacion.rol on rol.id_rol=usuario_rol.id_rol and rol.estado_registro=TRUE ";
				$cadenaSql .= " WHERE 1=1 ";
				$cadenaSql .= " AND fac.estado_registro=TRUE ";
				$cadenaSql .= " AND bp.estado_registro=TRUE ";
				$cadenaSql .= " AND fac.id_factura='".$variable."' ORDER BY rol.descripcion ASC, regla.descripcion ASC ";
				break;
			
			case 'inhabilitarFactura' :
				$cadenaSql = " UPDATE facturacion.factura SET  ";
				$cadenaSql .= " estado_factura='Cancelada' ";
				$cadenaSql .= " WHERE id_factura='" . $variable . "' AND estado_factura='Borrador'";
				break;
		}
		
		return $cadenaSql;
	}
}
?>

