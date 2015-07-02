<?php
/*
	TRIGGER DB UTILS
*/
set_time_limit(300);
require_once(dirname(dirname(__FILE__)).'/config/config4.php');
require(DEDALO_LIB_BASE_PATH.'/backup/class.backup.php');


# set vars
	$vars = array('action');
		foreach($vars as $name) $$name = common::setVar($name);




# EXPORT DB (export_structure)
if($action=='export') {

	# LOGIN VERIFICATION
	if(login::is_logged()!==true) die("<span class='error'> Auth error: please login </span>");

	# Dump historic data first
	$db_name = 'dedalo4_development_str_'.date("Y-m-d_Hi").'.custom';
	$exp 	 = backup::export_structure($db_name, $exclude_tables=false);	// Full backup
	echo $exp;
	echo '<br>';

	# Dump official structure version 'dedalo4_development_str.custom' (partial backup)
	$res = backup::export_structure(null, $exclude_tables=true);	 // Partial backup
	echo $res;
	exit();
}


# IMPORT DB (import_structure)
if($action=='import') {

	# LOGIN VERIFICATION
	if(login::is_logged()!==true) die("<span class='error'> Auth error: please login </span>");

	# Before import, EXPORT ;-)
	$db_name = 'dedalo4_development_str_'.date("Y-m-d_Hi").'.custom';
	$exp 	 = backup::export_structure($db_name, $exclude_tables=false);	// Full backup
	echo $exp;
	echo '<br>';

	$res = backup::import_structure();	
	echo $res;
	exit();
	
}#if($action=='import') 



# BACKUP DB 
if($action=='backup') {

	$res = backup::init_backup_secuence($user_id_matrix='0', $username='system');
	echo $res;
	exit();
}



# LOAD_STR_DATA
if ($action=='load_str_data') {	

	$res = (array)backup::load_dedalo_str_tables_data();

	$html = implode('<hr>', $res);
	echo wrap_pre($html);
	exit();


}#nd load_str_data
?>   