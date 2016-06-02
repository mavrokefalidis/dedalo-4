<?php
	
	# CONTROLLER

	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$section_tipo			= $this->get_section_tipo();
	$modo					= $this->get_modo();
	$lang					= DEDALO_APPLICATION_LANG;
	$label 					= $this->get_label();	
	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($section_tipo,$tipo);
	$html_title				= "Info about $tipo";
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);
	$file_name 				= $modo;
		
	
	switch($modo) {
		
		case 'edit'	:
				$dato 			  		= $this->get_dato();				
				$id_wrapper 			= 'wrapper_'.$identificador_unico;
				$input_name 			= "{$tipo}_{$parent}";	
				$component_info 		= $this->get_component_info('json');				
				$profile_section_tipo 	= DEDALO_SECTION_PROFILES_TIPO;
					#dump($dato, ' dato ++ '.to_string($tipo));

				$ar_select_values 		= (array)$this->get_ar_select_values();
				$valor 					= $this->get_valor();

				# IS_GLOBAL_ADMIN 
				$logged_user_id 		= navigator::get_user_id();
				$is_global_admin 		= component_security_administrator::is_global_admin($logged_user_id);
					#dump($is_global_admin, ' is_global_admin ++ '.to_string($logged_user_id));
				#if($is_global_admin!==true) $permissions = 1;
				break;

		case 'valor_list':
				$file_name	= 'list';
		case 'list':
				$valor 		= $this->get_valor();
				break;		
	}
		
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->modo</div>";
	}
?>