<?php
require_once( dirname(dirname(__FILE__)).'/lib/dedalo/config/config4.php');

/**
* LOGIN
*/
$is_logged	= login::is_logged();
	
if($is_logged!==true) {
	$url =  DEDALO_ROOT_WEB ."/main/";
	header("Location: $url");
	exit();
}
$permissions = (int)security::get_security_permissions(DEDALO_TESAURO_TIPO,DEDALO_TESAURO_TIPO);
if ($permissions<1) {
	$url =  DEDALO_ROOT_WEB ."/main/";
	header("Location: $url");
	exit();
}

require_once(DEDALO_LIB_BASE_PATH . '/common/class.navigator.php');


require_once(DEDALO_ROOT . '/Connections/config.php');
require_once(DEDALO_ROOT . '/inc/funciones.php');
require_once(DEDALO_ROOT . '/jer/class.Jerarquia.php');

$localizacion 	= $tesaurus_title ;
$localizacion2 	= $jerarquias_title ;
$area			= 'tesauro'; verify_access_area($area);

# fix area
$_SESSION['area_ts'] = 'jer';


$jer = new Jerarquia();

/*
* Búsqueda
*/
$maxRows_Recordset1 = 15 ;
$incPag=1;require(dirname(dirname(__FILE__)).'/inc/incPag.php');# paginacion
 

$idGET			= false;
$nombreGET		= false;
$alpha2GET 		= false;
$activaGET		= false;
$tipoGET		= false;

$filtro_sql 	= " WHERE jer.id IS NOT NULL " ;

if ((isset($_REQUEST['filtro'])) && ($_REQUEST['filtro'] == "y")) {  
	 
	$idGET 			= $_REQUEST['id'];			if($idGET!='') 			$filtro_sql .= " AND jer.id = $idGET " ;
	$nombreGET 		= $_REQUEST['nombre'];		if($nombreGET!='') 		$filtro_sql .= " AND jer.nombre LIKE '%$nombreGET%' " ;
	$alpha2GET 		= $_REQUEST['alpha2'];		if($alpha2GET!='') 		$filtro_sql .= " AND jer.alpha2 LIKE '%$alpha2GET%' " ;
	$activaGET 		= $_REQUEST['activa'];		if($activaGET!='') 		$filtro_sql .= " AND jer.activa LIKE '%$activaGET%' " ;
	$tipoGET 		= $_REQUEST['tipo'];		if($tipoGET!='') 		$filtro_sql .= " AND jer.tipo LIKE '%$tipoGET%' " ;
}

# Tipo 5 is not showed
if(SHOW_DEBUG==FALSE) {
	# Remove tipo 5 from array
	$filtro_sql .= " AND (jer.tipo != 5) ";
}

$orden 	= 'jer.activa, jer.tipo, jer.nombre';
$ot 	= 'ASC';
if (isset($_GET['orden']) && $_GET['orden']!=' ' ) 	$orden 	= $_GET['orden'] ;
if (isset($_GET['ot']) && $_GET['ot']!=' ' ) 		$ot 	= $_GET['ot'];
$ordenacion = " ORDER BY  $orden  $ot  "; 
 
/*
$query_Recordset1 		= " SELECT SQL_CACHE jer.id, jer.alpha2, jer.nombre, jer.activa, jer.tipo, jer.mainLang, jert.nombre as tipoText FROM 
							jerarquia AS jer
							LEFT JOIN jerarquia_tipos AS jert ON jer.tipo = jert.id					
							$filtro_sql
							$ordenacion 
							";
$query_limit_Recordset1 = sprintf("%s LIMIT %d, %d", $query_Recordset1, $startRow_Recordset1, $maxRows_Recordset1);
$Recordset1 			= mysql_query($query_limit_Recordset1, DB::_getConnection()) or die(mysql_error());
$row_Recordset1 		= mysql_fetch_assoc($Recordset1);
*/

### MYSQL ####
/*
$sql 		= "SELECT SQL_CACHE jer.id, jer.alpha2, jer.nombre, jer.activa, jer.tipo, jer.mainLang, jert.nombre as tipoText 
				FROM jerarquia AS jer
				LEFT JOIN jerarquia_tipos AS jert ON jer.tipo = jert.id
				$filtro_sql
				$ordenacion 
			  ";
$sql_limited= sprintf("%s LIMIT %d, %d", $sql, $startRow_Recordset1, $maxRows_Recordset1);
	#dump($sql,"sql");
$result 	= DBi::_getConnection()->query($sql_limited);
	#dump($result,'$result');

$row_Recordset1 = $result->fetch_array(MYSQLI_ASSOC);
*/
### /MYSQL ####

### POSTGRESQL ####
$sql = '
SELECT jer."id", jer."alpha2", jer."nombre", jer."activa", jer."tipo", jer."mainLang" AS main_lang, jert."nombre" as tipo_text 
FROM "jerarquia" AS jer
LEFT JOIN "jerarquia_tipos" AS jert ON jer.tipo = jert.id
WHERE jer.id IS NOT NULL 
ORDER BY  jer.activa, jer.tipo, jer.nombre ASC
';
$sql_limited	= $sql . 'LIMIT '.$maxRows_Recordset1.' OFFSET '.intval($startRow_Recordset1);
#$result 		= pg_query(DBi::_getConnection(), $sql_limited);
$result			= JSON_RecordDataBoundObject::search_free($sql_limited);
#$row_Recordset1 = pg_fetch_assoc($result);
	#dump($row_Recordset1,'$row_Recordset1');die();
### /POSTGRESQL ####


$incPag=2;require(dirname(dirname(__FILE__)).'/inc/incPag.php');# paginacion


$modo = 'jer_list';

# MENU
$menu_html = NULL;
if(empty($caller_id)) {
	$menu 		= new menu($modo);
	$menu_html 	= $menu->get_html();
}
$file		 	= DEDALO_LIB_BASE_PATH . '/html_page/html/html_page_header.phtml';
ob_start();
include ( $file );
$html_header =  ob_get_contents();
ob_get_clean();


$page_html = dirname(__FILE__).'/html/jer_list.phtml';

# LOAD VISTA TEMPLATE CODE
require_once($page_html);
exit();
?>
