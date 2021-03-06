<?php
	
	# CONTROLLER
	
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$section_tipo			= $this->get_section_tipo();
	$modo					= $this->get_modo();		
	$dato 					= $this->get_dato();
	$dato_reference_lang 	= null;
	$traducible 			= $this->get_traducible();
	$label 					= $this->get_label();	
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($section_tipo,$tipo);	
	$html_title				= "Info about $tipo";
	$lang					= $this->get_lang();
	$lang_name				= $this->get_lang_name();
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);
	$ejemplo 				= $this->get_ejemplo();
	$propiedades 			= $this->get_propiedades();
		

	$file_name 				= $modo;
	$from_modo				= $modo;

	if($permissions===0) return null;
	
	switch($modo) {
		
		case 'edit' :
				#$valor = $this->get_valor();
				#dump($valor, ' valor'.to_string());
				# Verify component content record is inside section record filter
				if ($this->get_filter_authorized_record()===false) return NULL;

				$id_wrapper 	= 'wrapper_'.$identificador_unico;
				$dato_json 		= json_encode($this->dato);
				$component_info = $this->get_component_info('json');

				if (isset($_REQUEST['from_modo'])) {
					$from_modo = $_REQUEST['from_modo'];
				}

				$date_mode = $this->get_date_mode();
				switch ($date_mode) {
					case 'range':
						$uid_start		  	= 'start_'.$identificador_unico;
						$uid_end 		  	= 'end_'.$identificador_unico;
						$input_name_start	= 'start_'."{$tipo}_{$parent}";
						$input_name_end		= 'end_'."{$tipo}_{$parent}";

						# Start
						$valor_start = '';
						if(isset($dato->start)) {
							$dd_date	= new dd_date($dato->start);
							$valor_start= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
											

						# End
						$valor_end = '';
						if(isset($dato->end)) {
							$dd_date	= new dd_date($dato->end);
							$valor_end 	= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
						break;
					case 'period':						
						$placeholder_year 	= 'Y';//labeL::get_label('anyos');
						$placeholder_month 	= 'M';//labeL::get_label('meses');
						$placeholder_day 	= 'D';//labeL::get_label('dias');

						$input_name_year	= "year_{$tipo}_{$parent}";
						$input_name_month	= "month_{$tipo}_{$parent}";
						$input_name_day		= "day_{$tipo}_{$parent}";

						$valor_year	= $valor_month = $valor_day = '';
						if(!empty($dato->period)) {
							$dd_date = new dd_date($dato->period);
							# Year							
							$valor_year		= isset($dd_date->year) ? $dd_date->year : '';							
							# Month							
							$valor_month	= isset($dd_date->month) ? $dd_date->month : '';							
							# Day							
							$valor_day		= isset($dd_date->day) ? $dd_date->day : '';							
						}						
						break;
					case 'time':
						$input_name	= "{$tipo}_{$parent}";

						$valor	= '';
						if(!empty($dato)) {
							$dd_date 	= new dd_date($dato);

							$hour  	 = isset($dd_date->hour)	? $dd_date->hour : '00';
							$minute  = isset($dd_date->minute)	? $dd_date->minute : '00';
							$second  = isset($dd_date->second)	? $dd_date->second : '00';
							$separator_time = ':';
							$valor 	 = $hour . $separator_time . $minute . $separator_time . $second;
						}
						break;
					case 'date':
					default:
						$input_name	= "{$tipo}_{$parent}";

						$valor	= '';
						if(!empty($dato)) {
							$dd_date 	= new dd_date($dato);

							if (isset($propiedades->method->get_valor_local)) {
								$valor	= component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) );
							}else{
								$valor	= component_date::get_valor_local( $dd_date, false );
							}
						}
						break;
				}
				#dump($date_mode, ' date_mode ++ '.to_string());
				$mandatory 		= (isset($propiedades->mandatory) && $propiedades->mandatory===true) ? true : false;
				$mandatory_json = json_encode($mandatory);	
				break;
		
		case 'portal_list'	:
				$file_name = 'list';

		case 'list_tm' :
				$file_name = 'list';

		case 'list'	:

				$id_wrapper 	= 'wrapper_'.$identificador_unico;
				$dato_json 		= json_encode($this->dato);
				$component_info = $this->get_component_info('json');


				$date_mode = $this->get_date_mode();
				switch ($date_mode) {
					case 'range':
						
						# Start
						$valor_start = '';
						if(isset($dato->start)) {
							$dd_date	= new dd_date($dato->start);
							$valor_start= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
											

						# End
						$valor_end = '';
						if(isset($dato->end)) {
							$dd_date	= new dd_date($dato->end);
							$valor_end 	= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
						break;
					case 'period':
						$valor_year	= $valor_month = $valor_day = '';
						if(!empty($dato->period)) {
							$dd_date = new dd_date($dato->period);
							# Year
							$valor_year	= isset($dd_date->year) ? $dd_date->year : '';
							# Month
							$valor_month= isset($dd_date->month) ? $dd_date->month : '';
							# Day
							$valor_day	= isset($dd_date->day) ? $dd_date->day : '';
						}
						break;
					case 'date':
					default:

						$valor	= '';
						if(!empty($dato)) {
							$dd_date 	= new dd_date($dato);

							if (isset($propiedades->method->get_valor_local)) {
								$valor	= component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) );
							}else{
								$valor	= component_date::get_valor_local( $dd_date, false );
							}
						}
						break;
				}
				break;

		case 'tool_time_machine' :
				$id_wrapper = 'wrapper_'.$identificador_unico.'_tm';
				$input_name = "{$tipo}_{$parent}_tm";	
				# Force file_name
				$file_name  = 'edit';
				break;
				
		case 'print':
				$date_mode = $this->get_date_mode();
				switch ($date_mode) {
					case 'range':
						
						# Start
						$valor_start = '';
						if(isset($dato->start)) {
							$dd_date	= new dd_date($dato->start);
							$valor_start= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
											

						# End
						$valor_end = '';
						if(isset($dato->end)) {
							$dd_date	= new dd_date($dato->end);
							$valor_end 	= isset($propiedades->method->get_valor_local) 
										? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
										: component_date::get_valor_local( $dd_date, false );
						}
						break;
					case 'period':
						$valor_year	= $valor_month = $valor_day = '';
						if(!empty($dato->period)) {
							$dd_date = new dd_date($dato->period);
							# Year
							$valor_year	= isset($dd_date->year) ? $dd_date->year : '';
							# Month
							$valor_month= isset($dd_date->month) ? $dd_date->month : '';
							# Day
							$valor_day	= isset($dd_date->day) ? $dd_date->day : '';
						}
						break;
					case 'date':
					default:

						$valor	= '';
						if(!empty($dato)) {
							$dd_date 	= new dd_date($dato);

							if (isset($propiedades->method->get_valor_local)) {
								$valor	= component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) );
							}else{
								$valor	= component_date::get_valor_local( $dd_date, false );
							}
						}
						break;
				}
				break;

		case 'search':
				# Showed only when permissions are >1
				if ($permissions<1) return null;
				
				$valor = '';
				$ar_comparison_operators 	= $this->build_search_comparison_operators();
				$ar_logical_operators 		= $this->build_search_logical_operators();

				# Search input name (var search_input_name is injected in search -> records_search_list.phtml)
				# and recovered in component_common->get_search_input_name()
				# Normally is section_tipo + component_tipo, but when in portal can be portal_tipo + section_tipo + component_tipo
				$search_input_name = $this->get_search_input_name();
				break;					
	}
	
	
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->modo</div>";
	}
?>