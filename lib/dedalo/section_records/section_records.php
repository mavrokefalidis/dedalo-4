<?php
	
	# CONTROLLER

	$tipo					= $this->get_tipo();
	$permissions			= common::get_permissions($tipo);
	$modo					= $this->options->modo;
	$file_name				= $modo;

	$cwd = basename(__DIR__);
	css::$ar_url[] = DEDALO_LIB_BASE_URL."/$cwd/css/$cwd.css";
	js::$ar_url[]  = DEDALO_LIB_BASE_URL."/$cwd/js/$cwd.js";

	
	switch($modo) {

		case 'edit':

				require_once(DEDALO_LIB_BASE_PATH . '/section_records/record/class.record.php');

				#
				# PAGINATOR HTML
					$rows_paginator_html= '';
					$context_name 		= isset($_GET['context_name']) ? $_GET['context_name'] : false;
					switch (true) {
						case (isset($this->options->save_handler) && $this->options->save_handler!='database'):
							# ignore paginator when save_handler is not 'database'
							break;
						case $context_name=='list_in_portal':
							# nothing to do (avoid show paginator when portal tool is opened)
							break;						
						default:
							$rows_paginator 		= new records_navigator($this->rows_obj, $modo);
							$rows_paginator_html	= $rows_paginator->get_html();
							break;
					}					
				
				#
				# ROW HTML
					$record_html 	= '';
					$record 		= new record($this, $modo);
					$record_html	= $record->get_html();

				break;

		case 'list_tm':

				$file_name='list';

		case 'list':


				require_once(DEDALO_LIB_BASE_PATH . '/section_records/rows_header/class.rows_header.php');
				require_once(DEDALO_LIB_BASE_PATH . '/section_records/rows/class.rows.php');

				/*
				$tool_update_cache = new tool_update_cache($tipo);
				$tool_update_cache->update_cache();
				if(SHOW_DEBUG) {
					global $log_messages;
					#dump(tool_update_cache::$debug_response,'$tool_update_cache->debug_response');
					$log_messages .= tool_update_cache::$debug_response;
				}
				*/	

				
				# BUTTONS
				# Calcula los bonones de esta sección y los deja disponibles como : $this->section_obj->ar_buttons
				#$this->section_obj->set_ar_buttons();
				#$section = section::get_instance(null, $this->options->section_tipo);
				#$ar_delete_button = $section->get_ar_children_objects_by_modelo_name_in_section("button_delete");

				if (isset($this->options->section_real_tipo)) {				
					$section_real_tipo = $this->options->section_real_tipo;
					$ar_children_tipo_by_modelo_name_in_section = section::get_ar_children_tipo_by_modelo_name_in_section($section_real_tipo, 'button_delete', true);
						#dump($ar_children_tipo_by_modelo_name_in_section,"section_real_tipo:$section_real_tipo");

					if (!empty($ar_children_tipo_by_modelo_name_in_section[0])) {
						$security = new security();
						$this->button_delete_permissions = $security->get_security_permissions($ar_children_tipo_by_modelo_name_in_section[0]);
					}
				}
				#dump($this);
				#dump($button_delete_permissions," button_delete_permissions");
				if(SHOW_DEBUG) {
					#dump($this->rows_obj->result,"this");
				}
				
					/*	if (empty($this->rows_obj->result)) {
							echo "<div class=\"no_results_msg\">No results found</div>";
							if(SHOW_DEBUG) {
								#dump($this->rows_obj->strQuery,"DEBUG: No results found whit this query");
								#echo "DEBUG: No results found whit this query: ";
								echo "<blockquote><pre>".$this->rows_obj->strQuery."</pre></blockquote>";
							}					
							return;
						}
					*/	
				# BUILD ALL HTML ROWS (PAGINATOR, TH, TD)				
				#$rows_search_html		= '';
					
				#
				# PAGINATOR HTML
					$records_data 			= $this->rows_obj;
					$rows_paginator 		= new records_navigator($records_data, $modo);
					$rows_paginator_html	= $rows_paginator->get_html();
				
				#
				# ROWS TABLE HTML
					
					# HEADER HTML (TH)
					$rows_header 			= new rows_header($this, $modo);
					$rows_header_html		= $rows_header->get_html();

					# ROWS HTML (TD)
					$rows 					= new rows($this, $modo);
					$rows_html				= $rows->get_html();
			
				
				if (isset($_REQUEST['m']) && $_REQUEST['m']!='list') {
					# Nothing to load
				}else{
					#
					# CSS
							css::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/nv.d3.min.css';
							css::$ar_url[] = DEDALO_LIB_BASE_URL.'/diffusion/diffusion_section_stats/css/diffusion_section_stats.css';

					#
					# JS includes
							js::$ar_url[] = D3_URL_JS;
							js::$ar_url[] = NVD3_URL_JS;
							/*										
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/axis.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/pieChart.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/discreteBarChart.js';
							#js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/multiBarHorizontal.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/multiBarHorizontalChart.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/utils.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/tooltip.js';
							js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/nvd3/src/models/legend.js';
							*/
							js::$ar_url[] = DEDALO_LIB_BASE_URL.'/diffusion/diffusion_section_stats/js/diffusion_section_stats.js';

							#js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/jquery/jquery.resizableColumns.min.js';
							#js::$ar_url[] = DEDALO_ROOT_WEB.'/lib/store.js-master/store.min.js';
				}		
				break;

		/*
		case 'portal_list' :
		
				# Mostrando portal dentro de un listado	(ej. listado imágenes identificativas en el listado de Historia Oral)	
				$rows_paginator 		= new rows_paginator($this, $modo);
				$rows_paginator_html	= $rows_paginator->get_html();
					#dump($rows_paginator, 'rows_paginator_html', array());

				$rows_header 			= new rows_header($this, $modo);
				$rows_header_html		= $rows_header->get_html();
					#dump($rows_header,"rows_header");

				$rows 		= new rows($this, $modo);
				$rows_html	= $rows->get_html();
					#dump($rows_html,"rows html for $tipo");

				break;
		
		case 'portal_list_in_list' :
				$rows 		= new rows($this, $modo);
				$rows_html	= $rows->get_html();
					#dump($rows_html,"rows html for $tipo");						
				break;
		*/
	}

	


	# LOAD PAGE	
	$page_html	= 'html/' . get_class($this) . '_' . $file_name . '.phtml';		#dump($page_html);
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $modo</div>";
	}
	
?>