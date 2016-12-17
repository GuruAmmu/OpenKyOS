<?php

namespace gestionComisionamiento\archivosAlfresco;

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
			
			// Sincronización Comisionamiento
			case "alfrescoUser" :
				$cadenaSql = " SELECT DISTINCT id_beneficiario, nombre_carpeta_dep as padre, nombre_carpeta_mun as hijo, site_alfresco as site ";
				$cadenaSql .= " FROM interoperacion.beneficiario_potencial ";
				$cadenaSql .= " INNER JOIN interoperacion.carpeta_alfresco on beneficiario_potencial.departamento=cast(carpeta_alfresco.cod_departamento as integer) ";
				$cadenaSql .= " WHERE cast(cod_municipio as integer)=municipio ";
				$cadenaSql .= " AND id_beneficiario='" . $variable . "' ";
				break;
			
			case "alfrescoCarpetas" :
				$cadenaSql = "SELECT parametros.codigo, parametros.descripcion ";
				$cadenaSql .= " FROM parametros.parametros ";
				$cadenaSql .= " JOIN parametros.relacion_parametro ON relacion_parametro.id_rel_parametro=parametros.rel_parametro ";
				$cadenaSql .= " WHERE parametros.estado_registro=TRUE AND relacion_parametro.descripcion='Alfresco Folders' ";
				break;
			
			case "alfrescoDirectorio" :
				$cadenaSql = "SELECT parametros.descripcion ";
				$cadenaSql .= " FROM parametros.parametros ";
				$cadenaSql .= " JOIN parametros.relacion_parametro ON relacion_parametro.id_rel_parametro=parametros.rel_parametro ";
				$cadenaSql .= " WHERE parametros.estado_registro=TRUE AND relacion_parametro.descripcion='Directorio Alfresco Site' ";
				break;
			
			case "alfrescoLog" :
				$cadenaSql = "SELECT host, usuario, password ";
				$cadenaSql .= " FROM parametros.api_data ";
				$cadenaSql .= " WHERE componente='alfresco' ";
				break;
			
            case 'consultarBeneficiariosPotenciales':
            	
                $cadenaSql = " SELECT DISTINCT identificacion ||' - ('||nombre||' '||primer_apellido||' '||segundo_apellido||')' AS  value, id_beneficiario  AS data  ";
                $cadenaSql .= " FROM  interoperacion.beneficiario_potencial ";
                $cadenaSql .= "WHERE estado_registro=TRUE ";
                $cadenaSql .= "AND  cast(identificacion  as text) ILIKE '%" . $_GET['query'] . "%' ";
                $cadenaSql .= "OR nombre ILIKE '%" . $_GET['query'] . "%' ";
                $cadenaSql .= "OR primer_apellido ILIKE '%" . $_GET['query'] . "%' ";
                $cadenaSql .= "OR segundo_apellido ILIKE '%" . $_GET['query'] . "%' ";
                $cadenaSql .= "LIMIT 10; ";
                break;
                
                case "consultarCarpetaSoportes" :
				$cadenaSql = " SELECT pr.id_parametro, pr.descripcion ";
				$cadenaSql .= " FROM parametros.parametros pr";
				$cadenaSql .= " JOIN parametros.relacion_parametro rl ON rl.id_rel_parametro=pr.rel_parametro";
				$cadenaSql .= " WHERE ";
				$cadenaSql .= " pr.estado_registro=TRUE ";
				$cadenaSql .= " AND rl.descripcion='Alfresco Folders'"; 
				$cadenaSql .= " AND pr.codigo='" . $variable . "' ";
				$cadenaSql .= " AND rl.estado_registro=TRUE ";
                	break;
                	
                case "masivosArchivos":
                $cadenaSql = "	SELECT id_beneficiario, split_part(nombre_documento,id_beneficiario||'_',2) as nombre_archivo, '/usr/share/nginx/html'||split_part(ruta_relativa,'http://conexionesdigitales.politecnica.edu.co',2) as rutaabsoluta";
                $cadenaSql.= " 	FROM interoperacion.documentos_contrato  ";
                $cadenaSql.= " 	WHERE estado_registro=TRUE ";
                $cadenaSql.= " 	ORDER BY id_beneficiario ASC  ";
                break;		
				
		}
		
		return $cadenaSql;
	}
}
?>