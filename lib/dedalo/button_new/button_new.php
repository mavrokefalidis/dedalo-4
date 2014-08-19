<?php
	
	# CONTROLLER
	
	$tipo 					= $this->get_tipo();			
	$target_tipo			= $this->get_target();
	$id						= NULL;
	$modo					= $this->get_modo();	
	$label 					= $this->get_label();

	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($tipo); 	
	$html_title				= "Info about $tipo";
	$ar_css					= $this->get_ar_css();	
	$file_name 				= $modo;


	switch($modo) {
		
		case 'edit'	:						
						break;

		case 'tool_portal':			
		case 'relation':
						$file_name	= 'edit';
						break;

		case 'tool_time_machine':		
						$file_name	= 'edit';
						break;				

		case 'selected_fragment':
						$file_name  = 'edit';
						break;
						
		case 'list'	:	#$label = strip_tags($label);
						break;
						
		case 'list_of_values'	:	
						
						break;	
						
						
	}
		
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if (!file_exists($page_html)) {
		throw new Exception("Error Processing Request. Mode <b>$file_name</b> is not valid! (2) ", 1);		
	}
	include($page_html);
?>