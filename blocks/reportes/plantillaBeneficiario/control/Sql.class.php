<?php

namespace reportes\plantillaBeneficiario;

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
			
			/**
			 * Clausulas específicas
			 */
			
			case 'consultarExitenciaBeneficiario' :
				$cadenaSql = " SELECT id_beneficiario";
				$cadenaSql .= " FROM interoperacion.beneficiario_potencial";
				$cadenaSql .= " WHERE estado_registro='TRUE'";
				$cadenaSql .= " AND identificacion='" . $variable . "';";
				break;
			
			case 'consultarInformacionBeneficiario' :
				$cadenaSql = " SELECT bp.* ,mn.municipio as nombre_municipio,dp.departamento as nombre_departamento";
				$cadenaSql .= " FROM interoperacion.beneficiario_potencial bp";
				$cadenaSql .= " JOIN parametros.municipio mn ON mn.codigo_mun=bp.municipio";
				$cadenaSql .= " JOIN parametros.departamento dp ON dp.codigo_dep=bp.departamento";
				$cadenaSql .= " WHERE bp.estado_registro='TRUE' ";
				$cadenaSql .= " AND bp.identificacion='" . $variable . "';";
				
				break;
			
			// Registro Procesos
			case 'registrarProceso' :
				$cadenaSql = " INSERT INTO parametros.procesos_masivos(";
				$cadenaSql .= " descripcion,";
				$cadenaSql .= " estado,";
				$cadenaSql .= " nombre_archivo, ruta_archivo,";
				$cadenaSql .= " parametro_inicio,";
				$cadenaSql .= " parametro_fin)";
				$cadenaSql .= " VALUES (";
				$cadenaSql .= " 'Actualizar Servicio - Pruebas Comision',";
				$cadenaSql .= " 'Actualizar',";
				$cadenaSql .= " '" . $variable ['nombre_archivo'] . "',";
				$cadenaSql .= " '" . $variable ['nombre_archivo'] . "',";
				$cadenaSql .= " 0,";
				$cadenaSql .= " 0";
				$cadenaSql .= " )RETURNING id_proceso;";
				break;
			
			case 'consultarProceso' :
				$cadenaSql = " SELECT * ";
				$cadenaSql .= " FROM parametros.procesos_masivos";
				$cadenaSql .= " WHERE descripcion='Contratos'";
				$cadenaSql .= " ORDER BY id_proceso DESC;";
				break;
			
			case 'consultarProcesoParticular' :
				$cadenaSql = " SELECT *";
				$cadenaSql .= " FROM parametros.procesos_masivos";
				$cadenaSql .= " WHERE id_proceso=(";
				$cadenaSql .= " SELECT MIN(id_proceso) ";
				$cadenaSql .= " FROM parametros.procesos_masivos";
				$cadenaSql .= " WHERE estado_registro='TRUE' ";
				$cadenaSql .= " AND estado='No Iniciado'";
				$cadenaSql .= " );";
				break;
			
			case 'actualizarProceso' :
				$cadenaSql = " UPDATE parametros.procesos_masivos";
				$cadenaSql .= " SET estado='En Proceso'";
				$cadenaSql .= " WHERE id_proceso='" . $variable . "';";
				break;
			
			case 'finalizarProceso' :
				$cadenaSql = " UPDATE parametros.procesos_masivos";
				$cadenaSql .= " SET estado='Finalizado',";
				$cadenaSql .= " ruta_archivo='" . $variable ['ruta_archivo'] . "',";
				$cadenaSql .= " nombre_ruta_archivo='" . $variable ['nombre_archivo'] . "'";
				$cadenaSql .= " WHERE id_proceso='" . $variable ['id_proceso'] . "';";
				break;
			// Crear Documenntos Contrato
			case 'ConsultaBeneficiarios' :
				$cadenaSql = " SELECT *";
				$cadenaSql .= " FROM interoperacion.contrato";
				$cadenaSql .= " WHERE numero_contrato >=" . $variable ['Inicio'] . " ";
				$cadenaSql .= " AND numero_contrato<=" . $variable ['Fin'] . " ";
				$cadenaSql .= " ORDER BY numero_contrato ;";
				break;
			
			case 'consultarTipoDocumento' :
				$cadenaSql = " SELECT pr.id_parametro,pr.codigo, pr.descripcion ";
				$cadenaSql .= " FROM parametros.parametros pr";
				$cadenaSql .= " JOIN parametros.relacion_parametro rl ON rl.id_rel_parametro=pr.rel_parametro";
				$cadenaSql .= " WHERE ";
				$cadenaSql .= " pr.estado_registro=TRUE ";
				$cadenaSql .= " AND rl.descripcion='Tipo de Documento'";
				$cadenaSql .= " AND pr.descripcion='" . $variable . "' ";
				$cadenaSql .= " AND rl.estado_registro=TRUE ";
				break;
			
			case 'consultarParametroParticular' :
				$cadenaSql = " SELECT descripcion ";
				$cadenaSql .= " FROM parametros.parametros";
				$cadenaSql .= " WHERE estado_registro='TRUE'";
				$cadenaSql .= " AND id_parametro='" . $variable . "';";
				break;
			
			case "codificacion" :
				$cadenaSql = "SELECT mc.abreviatura AS abr_mun, ur.abreviatura AS  abr_urb, ur.abreviatura_benef AS abr_benf ";
				$cadenaSql .= "FROM parametros.urbanizacion ur join parametros.municipio mc ON  ur.codigo_mun=mc.codigo_mun ";
				$cadenaSql .= "WHERE id_urbanizacion='" . $variable . "'";
				break;
			
			case "registrarBeneficiarioPotencial" :
				
				$cadenaSql = "INSERT INTO interoperacion.beneficiario_potencial (";
				$cadenaSql .= "id_beneficiario,";
				$cadenaSql .= "tipo_beneficiario,";
				$cadenaSql .= "tipo_documento,";
				$cadenaSql .= "identificacion,";
				$cadenaSql .= "nombre,";
				$cadenaSql .= "primer_apellido,";
				$cadenaSql .= "segundo_apellido,";
				$cadenaSql .= "genero,";
				$cadenaSql .= "edad,";
				$cadenaSql .= "nivel_estudio,";
				$cadenaSql .= "correo,";
				$cadenaSql .= "direccion,";
				$cadenaSql .= "manzana,";
				$cadenaSql .= "interior,";
				$cadenaSql .= "bloque,";
				$cadenaSql .= "torre,";
				$cadenaSql .= "apartamento,";
				$cadenaSql .= "lote,";
				$cadenaSql .= "telefono,";
				$cadenaSql .= "departamento,";
				$cadenaSql .= "municipio,";
				$cadenaSql .= "proyecto,";
				$cadenaSql .= "id_proyecto,";
				$cadenaSql .= "estrato,";
				$cadenaSql .= "nomenclatura,";
				$cadenaSql .= "minvi,";
				$cadenaSql .= "barrio,";
				$cadenaSql .= "piso";
				$cadenaSql .= ") VALUES ";
				$cadenaSql .= "(";
				$cadenaSql .= "'" . $variable ['id_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['tipo_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['tipo_documento'] . "',";
				$cadenaSql .= "'" . $variable ['identificacion_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['nombre_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['primer_apellido'] . "',";
				$cadenaSql .= "'" . $variable ['segundo_apellido'] . "',";
				$cadenaSql .= "'" . $variable ['genero_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['edad_beneficiario'] . "',";
				$cadenaSql .= "'" . $variable ['nivel_estudio'] . "',";
				$cadenaSql .= "'" . $variable ['correo'] . "',";
				$cadenaSql .= "'" . $variable ['direccion'] . "',";
				$cadenaSql .= "'" . $variable ['manzana'] . "',";
				$cadenaSql .= "'" . $variable ['interior'] . "',";
				$cadenaSql .= "'" . $variable ['bloque'] . "',";
				$cadenaSql .= "'" . $variable ['torre'] . "',";
				$cadenaSql .= "'" . $variable ['apartamento'] . "',";
				$cadenaSql .= "'" . $variable ['lote'] . "',";
				$cadenaSql .= "'" . $variable ['telefono'] . "',";
				$cadenaSql .= "'" . $variable ['departamento'] . "',";
				$cadenaSql .= "'" . $variable ['municipio'] . "',";
				$cadenaSql .= "'" . $variable ['proyecto'] . "',";
				$cadenaSql .= "'" . $variable ['id_proyecto'] . "',";
				$cadenaSql .= "'" . $variable ['estrato'] . "',";
				$cadenaSql .= "'" . $variable ['nomenclatura'] . "',";
				$cadenaSql .= "'" . $variable ['minvi'] . "',";
				$cadenaSql .= "'" . $variable ['barrio'] . "',";
				$cadenaSql .= "'" . $variable ['piso'] . "'";
				$cadenaSql .= ");";
				break;
			
			case 'actualizarBeneficiario' :
				$cadenaSql = "UPDATE interoperacion.beneficiario_potencial SET ";
				// $cadenaSql .= "tipo_beneficiario=" . "'" . $variable ['tipo_beneficiario'] . "',";
				// $cadenaSql .= "tipo_documento=" . "'" . $variable ['tipo_documento'] . "',";
				// $cadenaSql .= "identificacion=" . "'" . $variable ['identificacion_beneficiario'] . "',";
				if (! is_null ( $variable ['nombre_beneficiario'] )) {
					$cadenaSql .= "nombre=" . "'" . $variable ['nombre_beneficiario'] . "',";
				}
				if (! is_null ( $variable ['primer_apellido'] )) {
					$cadenaSql .= "primer_apellido=" . "'" . $variable ['primer_apellido'] . "',";
				}
				if (! is_null ( $variable ['segundo_apellido'] )) {
					$cadenaSql .= "segundo_apellido=" . "'" . $variable ['segundo_apellido'] . "',";
				}
				if ($variable ['genero_beneficiario'] != 0) {
					$cadenaSql .= "genero=" . "'" . $variable ['genero_beneficiario'] . "',";
				}
				if ($variable ['edad_beneficiario'] != 0) {
					$cadenaSql .= "edad=" . "'" . $variable ['edad_beneficiario'] . "',";
				}
				if ($variable ['nivel_estudio'] != 0) {
					$cadenaSql .= "nivel_estudio=" . "'" . $variable ['nivel_estudio'] . "',";
				}
				if ($variable ['telefono'] != 0) {
					$cadenaSql .= "telefono=" . "'" . $variable ['telefono'] . "',";
				}
				if (! is_null ( $variable ['correo'] )) {
					$cadenaSql .= "correo=" . "'" . $variable ['correo'] . "',";
				}
				if (! is_null ( $variable ['direccion'] )) {
					$cadenaSql .= "direccion=" . "'" . $variable ['direccion'] . "',";
				}
				if (! is_null ( $variable ['manzana'] )) {
					$cadenaSql .= "manzana=" . "'" . $variable ['manzana'] . "',";
				}
				if (! is_null ( $variable ['interior'] )) {
					$cadenaSql .= "interior=" . "'" . $variable ['interior'] . "',";
				}
				if (! is_null ( $variable ['bloque'] )) {
					$cadenaSql .= "bloque=" . "'" . $variable ['bloque'] . "',";
				}
				if (! is_null ( $variable ['torre'] )) {
					$cadenaSql .= "torre=" . "'" . $variable ['torre'] . "',";
				}
				if (! is_null ( $variable ['apartamento'] )) {
					$cadenaSql .= "apartamento=" . "'" . $variable ['apartamento'] . "',";
				}
				if (! is_null ( $variable ['lote'] )) {
					$cadenaSql .= "lote=" . "'" . $variable ['lote'] . "',";
				}
				if (! is_null ( $variable ['barrio'] )) {
					$cadenaSql .= "barrio=" . "'" . $variable ['barrio'] . "',";
				}
				if (! is_null ( $variable ['interior'] )) {
					$cadenaSql .= "telefono=" . "'" . $variable ['telefono'] . "' ,";
				}
				if ($variable ['estrato'] != 0) {
					$cadenaSql .= "estrato=" . "'" . $variable ['estrato'] . "'";
				}
				
				$cadenaSql .= " WHERE identificacion='" . $variable ['identificacion_beneficiario'] . "' ";
				break;
			
			case 'consultarConsecutivo' :
				$cadenaSql = " select id_beneficiario ";
				$cadenaSql .= "FROM interoperacion.beneficiario_potencial ";
				$cadenaSql .= "WHERE id_beneficiario ";
				$cadenaSql .= "ILIKE '" . $variable ['string'] . "%' ";
				$cadenaSql .= "AND substr(id_beneficiario, length(id_beneficiario)-" . $variable ['longitud'];
				$cadenaSql .= ", 1) ~ '^[0-9]'";
				$cadenaSql .= "ORDER BY id_beneficiario DESC LIMIT 1";
				break;
		}
		
		return $cadenaSql;
	}
}
?>

