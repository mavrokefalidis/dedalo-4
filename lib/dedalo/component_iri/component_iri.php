<?php
	
	# CONTROLLER

	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$modo					= $this->get_modo();
	$lang					= $this->get_lang();
	$section_tipo 			= $this->get_section_tipo();
	$propiedades			= $this->get_propiedades();	
	$dato 					= $this->get_dato();
	$valor					= $this->get_valor();
	$dato_reference_lang 	= NULL;
	$traducible 			= $this->get_traducible();
	$label 					= $this->get_label();
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();
	if($modo != 'simple')
	$permissions			= common::get_permissions($section_tipo,$tipo);
	$ejemplo				= $this->get_ejemplo();
	$html_title				= "Info about $tipo";
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);
	$visible				= $this->get_visible();
	$file_name				= $modo;
	
	if($permissions===0) return null;
	
	# Verify component content record is inside section record filter
	if ($this->get_filter_authorized_record()===false) return null ;
	
	
	switch($modo) {		
		
		case 'edit_in_list':
				// Fix always edit as modo / filename
				$modo 			= 'edit';
				$file_name		= 'edit';

				$wrap_style 	= '';	// 'width:100%'; // Overwrite possible custon component structure css
				// Dont break here. Continue as modo edit
		
		case 'tool_lang':
				$file_name 		= 'edit';
		case 'edit'	:

				$id_wrapper 	= 'wrapper_'.$identificador_unico;
				$dato_json 		= json_encode($this->dato);
				$component_info = $this->get_component_info('json');


				$input_name 	= "{$tipo}_{$parent}";
				#$dato_json 		= json_handler::encode($dato);
				#$dato 			= htmlentities($dato);
				
				# DATO_REFERENCE_LANG
				$default_component = null;
													
				if (empty($dato) && $traducible=='si') {					
					$default_component = $this->get_default_component();		
				}

				$valor	= '';
				if(isset($dato->iri)) {
					$dd_iri	= new dd_iri($dato->iri);
					$valor	= component_iri::get_valor( $dd_iri );
				}

				$mandatory 		= (isset($propiedades->mandatory) && $propiedades->mandatory===true) ? true : false;
				$mandatory_json = json_encode($mandatory);									
				break;

		case 'print' :
				#$dato = htmlentities($dato);
				break;

		case 'tool_time_machine'	:	
				$id_wrapper = 'wrapper_'.$identificador_unico.'_tm';
				$input_name = "{$tipo}_{$parent}_tm";	
				# Force file_name
				$file_name  = 'edit';
				break;
				
		case 'portal_list':
				if(empty($valor)) return null;

		case 'list_tm' :
				$file_name = 'list';
				# use list file to render value
										
		case 'list'	:
				if(empty($valor)) return null;	
				break;
						
		case 'list_of_values':
				break;

		case 'relation':
				$file_name  = 'list';
				break;
						
		case 'lang'	:									
				break;
		
		case 'search':
				# Showed only when permissions are >1
				if ($permissions<1) return null;

				$ar_comparison_operators = $this->build_search_comparison_operators();
				$ar_logical_operators 	 = $this->build_search_logical_operators();

				#if(isset($_REQUEST[$tipo])) $dato = $_REQUEST[$tipo];
				#$dato 			= json_encode($dato);

				# Search input name
				$search_input_name = $section_tipo.'_'.$tipo;
				break;
						
		case 'list_thesaurus':
				$render_vars = $this->get_render_vars();
					#dump($render_vars, ' render_vars ++ '.to_string());
				$icon_label = isset($render_vars->icon) ? $render_vars->icon : '';
				break;						
	}
	

	$page_html	= dirname(__FILE__) . '/html/' . $component_name . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->modo</div>";
	}
?>