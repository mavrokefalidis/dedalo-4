<?php
/*
* CLASS COMPONENT TEXT AREA
*
*
*/
class component_text_area extends component_common {

	public $arguments;


	function __construct($tipo=null, $parent=null, $modo='edit', $lang=DEDALO_DATA_LANG, $section_tipo=null) {
		
		// Overwrite lang when component_select_lang is present
		if ( ($modo==='edit') && (!empty($parent) && !empty($section_tipo)) ) {
			# dump($_REQUEST, ' _REQUEST ++ '.to_string());
			if ( (isset($_GET['m']) && $_GET['m']==='edit') || (isset($_REQUEST['mode']) && $_REQUEST['mode']==='load_rows') ) {
				# Only when component is loaded on page edit mode (avoid tool_lang changes of lang)
				$lang = self::force_change_lang($tipo, $parent, $modo, $lang, $section_tipo);
			}					
		}

		# Creamos el componente normalmente
		parent::__construct($tipo, $parent, $modo, $lang, $section_tipo);
	}//end __construct



	/**
	* FORCE_CHANGE_LANG
	* If defined component_select_lang as related term of current component, the lang of the component
	* gets from component_select_lang value. Else, received lag is used normally
	* @return string $lang
	*/
	public static function force_change_lang($tipo, $parent, $modo, $lang, $section_tipo) {
		
		if(SHOW_DEBUG===true) {
			$start_time=microtime(1);;
		}

		$changed_lang = false;
		$first_lang   = $lang;
		$ar_related_by_model = common::get_ar_related_by_model('component_select_lang',$tipo);
		if (!empty($ar_related_by_model)) {
			switch (true) {
				case count($ar_related_by_model)===1 :
					$related_component_select_lang = reset($ar_related_by_model);
					break;
				case count($ar_related_by_model)>1 :
					debug_log(__METHOD__." More than one ar_related_by_model are found. Please fix this ASAP ".to_string(), logger::ERROR);
					break;
				default:
					debug_log(__METHOD__." Var ar_related_by_model value is invalid. Please fix this ASAP ".to_string($ar_related_by_model), logger::ERROR);
					break;
			}
			if (isset($related_component_select_lang)) {				
				$component_select_lang = component_common::get_instance('component_select_lang',
																	    $related_component_select_lang,
																	    $parent,
																	    $modo,
																	    DEDALO_DATA_NOLAN,
																	    $section_tipo);
				$component_select_lang_dato = (array)$component_select_lang->get_dato();

				#
				# LANG
				# dato of component_select_lang is an array of locators.
				# For this we select only first locator and get his lang data 
				if (!empty($component_select_lang_dato[0])) {			
					$lang_locator = reset( $component_select_lang_dato );
					$target_lang  = lang::get_code_from_locator($lang_locator, $add_prefix=true);
					if (!empty($target_lang) && strpos($target_lang, 'lg-')!==false && $target_lang!==$lang) {
						#debug_log(__METHOD__." Changed lang: $lang to $target_lang ", logger::DEBUG);
						$lang = $target_lang;
						$changed_lang = true;
					}
				}
			}
			#dump($lang, ' lang ++ '.to_string());			
		}

		if(SHOW_DEBUG===true) {
			if (isset($target_lang)) {
				$total=round(microtime(1)-$start_time,3);
				if ($changed_lang === true) {
					$msg = "Changed lang: $first_lang to $target_lang ($first_lang-$lang)";
					debug_log(__METHOD__." $msg ".exec_time_unit($start_time,'ms').' ms' , logger::DEBUG);
				}else{
					$msg = "No change lang is necessary ($first_lang-$lang)";
				}				
			}
		}	
				

		return $lang;
	}//end force_change_lang
	


	/**
	* GET DATO : Format "lg-spa"
	*/
	public function get_dato() {
		$dato = parent::get_dato();

		# Compatibility old dedalo3 instalations		
		if ( strpos($dato, '[index_')!==false || strpos($dato, '[out_index_')!==false ) {
			$this->dato = $this->convert_tr_v3_v4( $dato );	// Update index tags format
			$this->Save();
			$dato = parent::get_dato();
		}

		return (string)$dato;
	}//end get_dato



	/**
	* SET_DATO
	*/
	public function set_dato($dato) {
		if($dato==='""') $dato = ''; // empty dato json encoded
		parent::set_dato( (string)$dato );
	}//end set_dato



	/**
	* CONVERT_TR_V3_V4
	* @return 
	*/
	public function convert_tr_v3_v4( $dato ) {
		
		$dato_source = $dato;
		
		#
		# INDEX IN		
		$dato_final = preg_replace("/\[index_[0]*([0-9]+)_in\]/", "[index-n-$1]", $dato_source, -1 , $count_index_in);

		#
		# INDEX OUT		
		$dato_final = preg_replace("/\[out_index_[0]*([0-9]+)\]/", "[/index-n-$1]", $dato_final, -1 , $count_index_out);

		debug_log(__METHOD__." Replaced index_in:$count_index_in and index_out:$count_index_out matches in dato".to_string(), logger::DEBUG);

		return (string)$dato_final;
	}//end convert_tr_v3_v4



	/**
	* SAVE OVERRIDE
	* Overwrite component_common method to set always lang to config:DEDALO_DATA_NOLAN before save
	*/
	public function Save( $update_all_langs_tags_state=false, $cleant_text=true ) {
		
		# revisamos las etiquetas para actualizar el estado de las mismas en los demás idiomas
		# para evitar un bucle infinito, en la orden 'Save' de las actualizaciones, pasaremos '$update_all_langs_tags_state=false'
		if ($update_all_langs_tags_state===true) {
			$this->update_all_langs_tags_state();
		}

		# Dato current assigned
		$dato_current 	= $this->dato;
			
		# Clean dato 
		if ($cleant_text) {
			$dato_current = TR::limpiezaPOSTtr($dato_current);
		}
		#$dato_clean 	= mb_convert_encoding($dato_clean, "UTF-8", "auto");

		# Set dato again (cleaned)
		$this->dato 	= $dato_current;
		if(SHOW_DEBUG) {
			#dump($this->dato,"salvando desde el componente text area");
		}

		# A partir de aquí, salvamos de forma estándar
		return parent::Save();
	}//end Save



	/**
	* GET_AR_TOOLS_OBJ
	* Override component_common method
	*/
	public function get_ar_tools_obj() {
		# Add tool_indexation
		$this->ar_tools_name[] = 'tool_indexation';
		$this->ar_tools_name[] = 'tool_tc';
		$this->ar_tools_name[] = 'tool_tr_print';
		
		return parent::get_ar_tools_obj();
	}//end get_ar_tools_obj
	

	
	/**
	* GET_HTML
	*//*
	public function get_html(){

		switch ($this->modo) {
			case 'list':

				break;
			
			default:
				return parent::get_html();
				break;
		}
	}//end get_html
	*/



	/**
	* GET DATO DEFAULT 
	* Overwrite common_function
	*/
	public function get_dato_default_lang() {

		$dato = parent::get_dato_default_lang();
		$dato = TR::addTagImgOnTheFly($dato);
		#$dato = self::decode_dato_html($dato);

		return $dato;
	}//end get_dato_default_lang



	/**
	* GET VALOR
	* Overwrite common_function
	*/
	public function get_valor() {
		
		switch ($this->modo) {
			case 'dummy':
			case 'diffusion':
				#$dato = $this->get_dato();
				$dato = parent::get_dato();		
				break;
			
			default:

				$dato = parent::get_dato();	
				#dump($dato,'dato');

				$dato = TR::deleteMarks($dato);
				$dato = self::decode_dato_html($dato);
				#$dato = addslashes($dato);					

				# Desactivo porque elimina el '<mar>'
				$dato = filter_var($dato, FILTER_UNSAFE_RAW);	# FILTER_SANITIZE_STRING
				break;
		}		

		return $dato;
	}//end get_valor



	/**
	* GET_VALOR_EXPORT
	* Return component value sended to export data
	* @return string $valor
	*/
	public function get_valor_export( $valor=null, $lang=DEDALO_DATA_LANG, $quotes, $add_id ) {
		
		if (is_null($valor)) {
			$dato = $this->get_dato();				// Get dato from DB
		}else{
			$this->set_dato( $valor );	// Use parsed json string as dato
		}

		$valor_export = $this->get_valor($lang);
		#$valor_export = br2nl($valor_export);

		return $valor_export;
	}//end get_valor_export
	


	/*
	* DECODE DATO HTML
	*/
	protected static function decode_dato_html($dato) {
		return htmlspecialchars_decode($dato);
	}//end decode_dato_html



	/**
	* UPDATE ALL LANGS TAGS STATE 
	* Actualiza el estado de las etiquetas:
	* Revisa el texto completo, comparando fragmento a fragmento, y si detecta que algún fragmento ha cambiado
	* cambia sus etiquetas a estado 'r'
	* @return $ar_changed_tags (key by lang)
	*/
	protected function update_all_langs_tags_state() {
		die(__METHOD__." EN PROCESO");
		/*
		$ar_changed_tags 	= array();
		$ar_changed_records = array();

		if (!$this->id) return $ar_changed_tags;

		# Previous dato
		# re-creamos este objeto para obtener el dato previo a las modificaciones		
		$previous_obj 		= new component_text_area($this->id, $this->tipo);
		$previous_raw_text	= $previous_obj->get_dato();
		
		# Current dato
		$current_text 		= $this->dato;
		# Clean current dato 
		$current_raw_text 	= TR::limpiezaPOSTtr($current_text);

		# Search tags
		$matches 		= $this->get_ar_relation_tags();
		$key 	 		= 0;		
			#dump(max($matches[$key]),'max($matches[$key])');		
		if (empty($matches[$key])) {
			return $ar_changed_tags ;
		}

		# Eliminamos duplicados (las etiquetas in/out se devuelven igual, como [index-n-1],[index-n-1])
		$ar_tags = array_unique($matches[$key]); 
			#dump($ar_tags,'ar_tags');

		# iterate all tags comparing fragments
		if(is_array($ar_tags)) foreach ($ar_tags as $tag) {			
			
			# Source fragment
			$source_fragment_text = component_text_area::get_fragment_text_from_tag( $tag_id, $tag_type, $previous_raw_text )[0];
			# Target fragment
			$target_fragment_text = component_text_area::get_fragment_text_from_tag( $tag_id, $tag_type, $current_raw_text )[0];

			if ($source_fragment_text != $target_fragment_text) {
				$ar_changed_tags[] = $tag;
			}
					
			#dump($source_fragment_text,'$source_fragment_text');
			#dump($target_fragment_text,'$target_fragment_text');
		}
		#dump($ar_changed_tags,'$ar_changed_tags');
		$ar_final['changed_tags']	= $ar_changed_tags;

		# Ya tenemos calculadas las etiquetas de los fragmentos que han cambiado		
		if (count($ar_changed_tags)===0) {
			# no hay etiquetas a cambiar
			$ar_final['changed_records'] = NULL;
		}else{
			# Recorremos los registros del resto de idiomas actualizando el estado de las etiquetas coincidentes a 'r' (para revisar)
			$arguments=array();
			$arguments['parent']	= $this->get_parent();
			$arguments['tipo']		= $this->get_tipo();			
			$matrix_table 			= common::get_matrix_table_from_tipo($this->get_section_tipo());		
			$RecordObj_matrix		= new RecordObj_matrix($matrix_table,NULL);
			$ar_result				= $RecordObj_matrix->search($arguments);

			foreach ($ar_result as $id_matrix) {
				
				$component_text_area= new component_text_area($id_matrix, $this->get_tipo() );
				$current_lang 		= $component_text_area->get_lang();
				if ($current_lang != $this->lang) {
					
					$text_raw 			= $component_text_area->get_dato();
					$text_raw_updated 	= self::change_tag_state( $ar_changed_tags, $state='r', $text_raw );
					$component_text_area->set_dato($text_raw_updated);	
						#dump($text_raw_updated,'$text_raw_updated',"for id: $id_matrix");
					$component_text_area->Save(false);	# Important: arg 'false' is mandatory for avoid infinite loop
					$ar_changed_records[] = $id_matrix;
				}
			}
			$ar_final['changed_records']= $ar_changed_records;			
		}		
		#dump($ar_final,'ar_final');

		return $ar_final;
		*/
	}//end update_all_langs_tags_state



	/**
	* CHANGE TAG STATE
	* Cambia el estado de la etiqueta dada dentro del texto.
	* Ejemplo: [index-n-1] -> [index-r-1]
	* @param $ar_tag (formated as tag in, like [index-n-1]. Can be string or array)
	* @param $state (default 'r')
	* @param $text_raw
	* @return $text_raw_updated
	*/
	public static function change_tag_state( $ar_tag, $state='r', $text_raw ) {

		# Force array
		if (is_string($ar_tag)) $ar_tag = array($ar_tag);

		# Default no change text
		$text_raw_updated = $text_raw;		

		if(is_array($ar_tag)) foreach ($ar_tag as $tag) {
			
			$id 				= TR::tag2value($tag);			#dump($id, ' id ++ '.to_string($ar_tag));

			# patrón válido tanto para 'in' como para 'out' tags
			#$pattern 			= "/(\[\/{0,1}index-)([a-z])(-$id\])/";  /\[\/{0,1}index-[a-z]-1(-.{0,8}-data:.*?:data)?\]/
			$pattern 			= TR::get_mark_pattern($mark='index', $standalone=true, false, $data=false);
				#dump($pattern, ' $pattern ++ '.to_string());

			preg_match_all($pattern, $text_raw, $matches);
				#dump($matches, ' matches ++ '.to_string($text_raw)); 

			foreach ((array)$matches[3] as $key => $value) {
				if ($value==$id) {
					if (strpos($tag, '[/index')!==false) {
						$type = 'indexOut';
					}else if (strpos($tag, '[index')!==false) {
						$type = 'indexIn';
					}else{
						$type = $matches[1][0];
					}
					$label = $matches[5][0];
					$data  = $matches[6][0];
					$new_tag = TR::build_tag($type, $state, $id, $label, $data);
						#dump($new_tag, ' new_tag ++ '.to_string());

					# reemplazamos sólo la letra correspondiente al estado de la etiqueta
					$text_raw_updated = str_replace($tag, $new_tag, $text_raw);
					break;
				}
			}

			# reemplazamos sólo la letra correspondiente al estado de la etiqueta
			#$replacement		= "$1".$state."$2";
			#$text_raw_updated 	= preg_replace($pattern, $replacement, $text_raw);
			#	dump($text_raw_updated, ' text_raw_updated - state : '.$state.' ++ '.to_string($text_raw));
		}		

		return $text_raw_updated ;
	}//end change_tag_state



	/**
	* GET AR REALATION TAGS
	* Buscamos apariciones del patron de etiqueta indexIn (definido en class TR)
	* @return $matches Array()
	* Devuelve un array con todas las apariciones, del tipo: 'indexIn' formateadas
	* Like:
	*/
	/*
		pattern: /([index-([a-z])-([0-9]{1,6})])/ 
		result :
		[0] => Array
	        (
	            [0] => [index-n-5]
	            [1] => [index-n-4]
	            [2] => [index-n-3]
	        )
	    [1] => Array
	        (
	            [0] => [index-n-5]
	            [1] => [index-n-4]
	            [2] => [index-n-3]
	        )
	    [2] => Array
	        (
	            [0] => n
	            [1] => n
	            [2] => n
	        )
	    [3] => Array
	        (
	            [0] => 5
	            [1] => 4
	            [2] => 3
	        )
		*/
	public function get_ar_relation_tags() {

		# Cogemos los datos raw de la base de datos
		$dato = $this->get_dato();

		if (empty($dato))
			return NULL;

		$matches = NULL;				

		# Buscamos apariciones del patrón de etiqueta indexIn (definido en class TR)
		$pattern = TR::get_mark_pattern($mark='indexIn',$standalone=true);

		# Search math patern tags
		preg_match_all($pattern,  $dato,  $matches, PREG_PATTERN_ORDER);
			#dump($matches,'$matches',"matches to patern: $pattern");

		return $matches;
	}//end get_ar_relation_tags



	/**
	* GET LAST TAG REL ID
	* @return Int max tag id value
	*//*
	public function get_last_tag_index_id() {

		$matches = $this->get_ar_relation_tags();
		$key 	 = 3;
		
		if (empty($matches[$key])) {
			$last_tag_index_id = 0;
		}else{
			$last_tag_index_id = intval(max($matches[$key]));	
		}

		return (int)$last_tag_index_id;
	}//end get_last_tag_index_id
	*/



	/**
	* GET FRAGMENT TEXT FROM TAG
	* @param $tag (String like '[index-n-5]' or '[/index-n-5]' or [index-r-5]...)
	* @return $fragment_text (String like 'texto englobado por las etiquetas xxx a /xxx')
	*/
	public static function get_fragment_text_from_tag( $tag_id, $tag_type, $raw_text ) {

		# Test if la etiqueta no está bien formada
		if(empty($tag_id) || empty($tag_type)) {
			$msg = "Warning: tag '$tag_id' is not valid! (get_fragment_text_from_tag tag_id:$tag_id - tag_type:$tag_type - raw_text:$raw_text)";
			trigger_error($msg);
			if(SHOW_DEBUG) {
				error_log( 'get_fragment_text_from_tag : '.print_r(debug_backtrace(),true) );
			}
			return NULL;
		}
	
		switch ($tag_type) {
			case 'index':
				$tag_in  = TR::get_mark_pattern('indexIn',  $standalone=false, $tag_id, $data=false);
				$tag_out = TR::get_mark_pattern('indexOut', $standalone=false, $tag_id, $data=false);
				break;
			case 'struct':
				$tag_in  = TR::get_mark_pattern('structIn',  $standalone=false, $tag_id, $data=false);
				$tag_out = TR::get_mark_pattern('structOut', $standalone=false, $tag_id, $data=false);
				break;
			default:
				throw new Exception("Error Processing Request. Invalid tag type: $tag_type", 1);				
				break;
		}
		
		# Build in/out regex pattern to search
		$regexp = $tag_in ."(.*)". $tag_out;
			#dump($regexp,'regexp',"regexp for $tag_id : $regexp - ".to_string($tag_type)); die();		

		# Search fragment_text
			# Dato raw from matrix db				
			$dato = $raw_text ;	#parent::get_dato();
				#dump($dato, ' dato ++ '.to_string($regexp));
			#if( preg_match_all("/$regexp/", $dato, $matches, PREG_SET_ORDER) ) {
			if( preg_match_all("/$regexp/", $dato, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE) ) {
				#dump($matches,'$matches');
				$key_fragment = 3;
			    foreach($matches as $match) {
			        #dump($match,'$match',"regexp: $regexp for id:$id");
			        if (isset($match[$key_fragment][0])) {

			        	$fragment_text = $match[$key_fragment][0];	#dump($fragment_text,'$fragment_text',"regexp: $regexp for id:$id");#

			        	# Clean fragment_text
			        	$fragment_text = TR::deleteMarks($fragment_text);
			        	$fragment_text = self::decode_dato_html($fragment_text);


			        	# tag in position
			        	$tag_in_pos = $match[0][1];

			        	# tag out position
			        	$tag_out_pos = $tag_in_pos + strlen($match[0][0]);

			        	return array( $fragment_text, $tag_in_pos, $tag_out_pos );
			        }
			    }
			}

		return NULL;
	}//end get_fragment_text_from_tag
	


	/**
	* GET FRAGMENT TEXT FROM REL LOCATOR
	* Despeja el tag a partir de rel_locator y llama a component_text_area::get_fragment_text_from_tag($tag, $raw_text)
	* para devolver el fragmento buscado
	* Ojo : Se puede llamar a un fragmento, tanto desde un locator de relación cómo desde uno de indexación.
	* @param $rel_locator (Object like '{rel_locator:{section_id : "55"}, {section_id : "oh1"},{component_tipo:"oh25"} }')
	* @return $fragment
	* @see static component_text_area::get_fragment_text_from_tag($tag_id, $tag_type, $raw_text)
	* Usado por section_records/rows/rows.php para mostrar el fragmento en los listados
	*/
	public static function get_fragment_text_from_rel_locator( $rel_locator ) {

		#throw new Exception("SORRY. DEACTIVATED FUNCTION: get_fragment_text_from_rel_locator", 1); // 6-4-2015		
				
		/*
		# INDEXATION TAG
		if ( preg_match("/dd.{1,32}\.[0-9]{1,32}\.[0-9]{1,32}\.dd.{1,32}\.[0-9]{1,32}/", $rel_locator) ) {
			$tag_obj = component_common::get_locator_as_obj($rel_locator);		
		}
		# RELATION TAG
		else if ( preg_match("/[0-9]{1,32}\.dd.{1,32}\.[0-9]{1,32}/", $rel_locator) ) {
			$tag_obj = component_common::get_locator_relation_as_obj($rel_locator);
		}	
		# INVALID LOCATOR
		else{
		*/
		if (empty($rel_locator)) {
			if(SHOW_DEBUG) {
				dump($rel_locator,'$rel_locator');
			}			
			$msg = "rel_locator $rel_locator is not valid!";
			trigger_error($msg);
			#throw new Exception("Error Processing Request : $msg", 1);			
			return NULL;
		}
				
		$section_tipo 			= $rel_locator->section_tipo;
		$section_id 			= $rel_locator->section_id;
		$component_tipo			= $rel_locator->component_tipo;
		$tag_id					= $rel_locator->tag_id;

		switch ($rel_locator->type) {
			case DEDALO_RELATION_TYPE_STRUCT_TIPO:
				$tag_type = 'index';
				break;
			case DEDALO_RELATION_TYPE_INDEX_TIPO:
				$tag_type = 'struct';
				break;
			default:
				debug_log(__METHOD__." Making fallback to index because rel_locator->type is NOT DEFINED in locator ".to_string($rel_locator), logger::ERROR);
				$tag_type = 'index';
				break;
		}
	

		$component_text_area = component_common::get_instance('component_text_area',
															  $component_tipo,
															  $section_id,
															  $modo='edit',
															  DEDALO_DATA_LANG,
															  $section_tipo);
		$raw_text = $component_text_area->get_dato();

		return component_text_area::get_fragment_text_from_tag($tag_id, $tag_type, $raw_text);
	}//end get_fragment_text_from_rel_locator



	/**
	* DELETE_TAG_FROM_ALL_LANGS
	* Search all component data langs and delete tag an update (save) dato on every lang 
	* @param string $tag like '[index-n-2]'
	* @return array $ar_langs_changed (langs afected)
	* @see trigger.tool_indexation mode 'delete_tag'
	*/
	public function delete_tag_from_all_langs($tag_id, $tag_type) {

		$component_ar_langs = (array)$this->get_component_ar_langs();
			#dump($component_ar_langs, ' component_ar_langs');		

		$ar_langs_changed=array();
		foreach ($component_ar_langs as $current_lang) {
			$component_text_area 	= component_common::get_instance('component_text_area', $this->tipo, $this->parent, $this->modo, $current_lang, $this->section_tipo);
			$text_raw 				= $component_text_area->get_dato();
			$text_raw_updated 		= self::delete_tag_from_text($tag_id, $tag_type, $text_raw);
			$component_text_area->set_dato($text_raw_updated);			
			if (!$component_text_area->Save()) {
			 	throw new Exception("Error Processing Request. Error saving component_text_area lang ($current_lang)", 1);			 	
			}
			$ar_langs_changed[] = $current_lang;
		}
		
		return (array)$ar_langs_changed;
	}//end delete_tag_from_all_langs



	/**
	* DELETE TAG FROM TEXT
	* !!!!
	* @param array $ar_tag (formated as tag in, like [index-n-1]. Can be string (will be converted to array))	
	* @param string $text_raw
	* @return string $text_raw_updated
	*/
	public static function delete_tag_from_text($tag_id, $tag_type, $text_raw) {
		
		# patrón válido tanto para 'in' como para 'out' tags
		$pattern 			= TR::get_mark_pattern($tag_type, $standalone=true, $tag_id, $data=false);
		#$pattern 			= "/(\[\/{0,1}index-[a-z]-$id\])/";
		# reemplazamos la etiqueta por un string vacío
		$replacement		= "";
		$text_raw_updated 	= preg_replace($pattern, $replacement, $text_raw);	

		return $text_raw_updated;
	}//end delete_tag_from_text



	/**
	* FIX_BROKEN_INDEX_TAGS
	* @return 
	*/
	public function fix_broken_index_tags( $save=false ) {

		$start_time = start_time();

		$response = new stdClass();
			$response->result = false;
			$response->msg 	  = null;

		$changed_tags = 0;

		$raw_text = $this->get_dato();

		# INDEX IN
		$pattern = TR::get_mark_pattern($mark='indexIn',$standalone=false);
		preg_match_all($pattern,  $raw_text,  $matches_indexIn, PREG_PATTERN_ORDER);
			#dump($matches_indexIn,"matches_indexIn ".to_string($pattern));		

		# INDEX OUT
		$pattern = TR::get_mark_pattern($mark='indexOut',$standalone=false);
		preg_match_all($pattern,  $raw_text,  $matches_indexOut, PREG_PATTERN_ORDER);
			#dump($matches_indexOut,"matches_indexOut ".to_string($pattern));

		$index_tag_id = 3;
		
		# INDEX IN MISSING
		$ar_missing_indexIn=array();
		foreach ($matches_indexOut[$index_tag_id] as $key => $value) {
			if (!in_array($value, $matches_indexIn[$index_tag_id])) {
				$tag_out = $matches_indexOut[0][$key];		
				$tag_in  = str_replace('[/', '[', $tag_out);
				$ar_missing_indexIn[] = $tag_in;										

				# Add deleted tag
				$tag_in   = self::change_tag_state( $tag_in, $state='d', $tag_in );	// Change state to 'd'
				$pair 	  = $tag_in.''.$tag_out;	// concatenate in-out
				$raw_text = str_replace($tag_out, $pair, $raw_text);
					#dump($raw_text, ' raw_text ++** '.$pair .to_string($tag_in));	
				$changed_tags++;				
			}
		}
		#dump($ar_missing_indexIn, ' ar_missing_indexIn ++ '.to_string());

		# INDEX MISSING OUT
		$ar_missing_indexOut=array();
		foreach ($matches_indexIn[$index_tag_id] as $key => $value) {
			if (!in_array($value, $matches_indexOut[$index_tag_id])) {
				$tag_in  = $matches_indexIn[0][$key];	// As we only have the in tag, we create out tag
				$tag_out = str_replace('[', '[/', $tag_in);
				$ar_missing_indexOut[] = $tag_out;

				# Add deleted tag
				$tag_out   = self::change_tag_state( $tag_out, $state='d', $tag_out );	// Change state to 'd'
					#dump($tag_out, ' tag_out ++ '.to_string());
				$pair 	  = $tag_in.''.$tag_out;	// concatenate in-out
				$raw_text = str_replace($tag_in, $pair, $raw_text);
					#dump($raw_text, ' raw_text ++** '.$pair .to_string($tag_in));
				$changed_tags++;
			}
		}
		#dump($ar_missing_indexOut, ' ar_missing_indexOut ++ '.to_string()); 
		

		# TESAURUS INDEXATIONS INTEGRITY VERIFY
		$ar_indexations = $this->get_component_indexations(DEDALO_RELATION_TYPE_INDEX_TIPO); // DEDALO_RELATION_TYPE_STRUCT_TIPO - DEDALO_RELATION_TYPE_INDEX_TIPO
			#dump($ar_indexations, ' ar_indexations ++ '.to_string());
		$ar_indexations_tag_id = array();
		foreach ($ar_indexations as $ar_locator) {
			#dump($current_index, ' $current_index ++ '.to_string());
			
			foreach ($ar_locator as $locator) {
				#dump($locator, ' locator ++ '.to_string());
				if(!property_exists($locator,'tag_id')) continue;
				
				$l_section_tipo 	= $locator->section_tipo;
				$l_section_id 		= $locator->section_id;
				$l_component_tipo 	= $locator->component_tipo;
				if ($l_section_tipo  === $this->section_tipo && 
					$l_section_id    == $this->parent &&
					$l_component_tipo=== $this->tipo
					) {
					$tag_id = $locator->tag_id;
						#dump($tag_id, ' $tag_id ++ '.to_string());
					$ar_indexations_tag_id[] = $tag_id;
				}
			}//end foreach ($ar_locator as $locator) {				
		}
		$ar_indexations_tag_id = array_unique($ar_indexations_tag_id);
		#dump($ar_indexations_tag_id, ' $ar_indexations_tag_id ++ '.to_string());


		# PORTALS POINTERS
		$ar_portal_pointers = component_portal::get_component_pointers($this->tipo, $this->section_tipo, $this->parent, $tag_id=null);
			#dump($ar_portal_pointers, ' ar_portal_pointers ++ '.to_string());
		$ar_portal_tag_id = array();
		foreach ($ar_portal_pointers as $key => $portal_locator) {
			if (isset($portal_locator->tag_id)) {
				$ar_portal_tag_id[] = $portal_locator->tag_id;
			}
		}
		#dump($ar_indexations_tag_id, ' ar_indexations_tag_id ++ '.to_string());
		# Add portal tags to index tags array
		$ar_indexations_tag_id = array_merge($ar_indexations_tag_id, $ar_portal_tag_id );
		$ar_indexations_tag_id = array_unique($ar_indexations_tag_id);
		#dump($ar_indexations_tag_id, ' $ar_indexations_tag_id ++ '.to_string($ar_portal_tag_id));


		$added_tags = 0;
		if (!empty($ar_indexations_tag_id)) {

			$all_text_tags = array_unique(array_merge($matches_indexIn[$index_tag_id], $matches_indexOut[$index_tag_id]));
				#dump($all_text_tags, ' $all_text_tags ++ '.to_string());

			foreach ($ar_indexations_tag_id as $current_tag_id) {
				if (!in_array($current_tag_id, $all_text_tags)) {
					#dump($current_tag_id, ' current_tag_id +++++++++ '.to_string());
					#$new_pair = "[index-d-{$current_tag_id}][/index-d-{$current_tag_id}] ";

					$tag_in   = TR::build_tag('indexIn',  'd', $current_tag_id, '', '');
					$tag_out  = TR::build_tag('indexOut', 'd', $current_tag_id, '', '');
					$new_pair = $tag_in . $tag_out;

					$raw_text = $new_pair . $raw_text;
					$added_tags++;
				}
			}
		}//end if (!empty($ar_indexations_tag_id)) {


		if ($added_tags>0 || $changed_tags>0) {

			$response->result = true;
			$response->msg 	  = strtoupper(label::get_label('atencion')).": ";	// WARNING
			
			if($added_tags>0)
			$response->msg .= sprintf(" %s ".label::get_label('etiquetas_index_borradas'),$added_tags);	// deleted index tags was created at beginning of text.

			if($changed_tags>0)
			$response->msg .= sprintf(" %s ".label::get_label('etiquetas_index_fijadas'),$changed_tags); // broken index tags was fixed.

			$response->msg .= " ".label::get_label('etiquetas_revisar');	// Please review position of blue tags
			
			# UPDATE MAIN DATO
			$this->set_dato($raw_text);

			# SAVE
			if($save===true) {
				$this->Save();
				#$response->msg .= ". Text repaired, has been saved.";
			}else{
				$response->msg .= " ".label::get_label('etiqueta_salvar_texto'); // and saved text
			}

			$response->total = round(microtime(1)-$start_time,4)*1000 ." ms";
		}	
			
		return $response;		
	}//end fix_broken_index_tags



	/**
	* FIX_BROKEN_STRUCT_TAGS
	* @return 
	*/
	public function fix_broken_struct_tags( $save=false ) {

		$start_time = start_time();

		$response = new stdClass();
			$response->result = false;
			$response->msg 	  = null;

		$changed_tags = 0;

		$raw_text = $this->get_dato();

		# INDEX IN
		$pattern = TR::get_mark_pattern($mark='structIn',$standalone=false);
		preg_match_all($pattern,  $raw_text,  $matches_indexIn, PREG_PATTERN_ORDER);
			#dump($matches_indexIn,"matches_indexIn ".to_string($pattern));		

		# INDEX OUT
		$pattern = TR::get_mark_pattern($mark='structOut',$standalone=false);
		preg_match_all($pattern,  $raw_text,  $matches_indexOut, PREG_PATTERN_ORDER);
			#dump($matches_indexOut,"matches_indexOut ".to_string($pattern));

		$index_tag_id = 3;
		
		# INDEX IN MISSING
		$ar_missing_indexIn=array();
		foreach ($matches_indexOut[$index_tag_id] as $key => $value) {
			if (!in_array($value, $matches_indexIn[$index_tag_id])) {
				$tag_out = $matches_indexOut[0][$key];		
				$tag_in  = str_replace('[/', '[', $tag_out);
				$ar_missing_indexIn[] = $tag_in;										

				# Add deleted tag
				$tag_in   = self::change_tag_state( $tag_in, $state='d', $tag_in );	// Change state to 'd'
				$pair 	  = $tag_in.''.$tag_out;	// concatenate in-out
				$raw_text = str_replace($tag_out, $pair, $raw_text);
					#dump($raw_text, ' raw_text ++** '.$pair .to_string($tag_in));	
				$changed_tags++;				
			}
		}
		#dump($ar_missing_indexIn, ' ar_missing_indexIn ++ '.to_string());

		# INDEX MISSING OUT
		$ar_missing_indexOut=array();
		foreach ($matches_indexIn[$index_tag_id] as $key => $value) {
			if (!in_array($value, $matches_indexOut[$index_tag_id])) {
				$tag_in  = $matches_indexIn[0][$key];	// As we only have the in tag, we create out tag
				$tag_out = str_replace('[', '[/', $tag_in);
				$ar_missing_indexOut[] = $tag_out;

				# Add deleted tag
				$tag_out   = self::change_tag_state( $tag_out, $state='d', $tag_out );	// Change state to 'd'
					#dump($tag_out, ' tag_out ++ '.to_string());
				$pair 	  = $tag_in.''.$tag_out;	// concatenate in-out
				$raw_text = str_replace($tag_in, $pair, $raw_text);
					#dump($raw_text, ' raw_text ++** '.$pair .to_string($tag_in));
				$changed_tags++;
			}
		}
		#dump($ar_missing_indexOut, ' ar_missing_indexOut ++ '.to_string()); 
		

		# TESAURUS INDEXATIONS INTEGRITY VERIFY
		$ar_indexations = $this->get_component_indexations(DEDALO_RELATION_TYPE_STRUCT_TIPO); // DEDALO_RELATION_TYPE_STRUCT_TIPO - DEDALO_RELATION_TYPE_INDEX_TIPO
			#dump($ar_indexations, ' ar_indexations ++ '.to_string());
		$ar_indexations_tag_id = array();
		foreach ($ar_indexations as $ar_locator) {
			#dump($current_index, ' $current_index ++ '.to_string());
			
			foreach ($ar_locator as $locator) {
				#dump($locator, ' locator ++ '.to_string());
				if(!property_exists($locator,'tag_id')) continue;
				
				$l_section_tipo 	= $locator->section_tipo;
				$l_section_id 		= $locator->section_id;
				$l_component_tipo 	= $locator->component_tipo;
				if ($l_section_tipo  === $this->section_tipo && 
					$l_section_id    == $this->parent &&
					$l_component_tipo=== $this->tipo
					) {
					$tag_id = $locator->tag_id;
						#dump($tag_id, ' $tag_id ++ '.to_string());
					$ar_indexations_tag_id[] = $tag_id;
				}
			}//end foreach ($ar_locator as $locator) {				
		}
		$ar_indexations_tag_id = array_unique($ar_indexations_tag_id);
		#dump($ar_indexations_tag_id, ' $ar_indexations_tag_id ++ '.to_string());

		/*
		# PORTALS POINTERS
		$ar_portal_pointers = component_portal::get_component_pointers($this->tipo, $this->section_tipo, $this->parent, $tag_id=null);
			#dump($ar_portal_pointers, ' ar_portal_pointers ++ '.to_string());
		$ar_portal_tag_id = array();
		foreach ($ar_portal_pointers as $key => $portal_locator) {
			if (isset($portal_locator->tag_id)) {
				$ar_portal_tag_id[] = $portal_locator->tag_id;
			}
		}
		#dump($ar_indexations_tag_id, ' ar_indexations_tag_id ++ '.to_string());
		# Add portal tags to index tags array
		$ar_indexations_tag_id = array_merge($ar_indexations_tag_id, $ar_portal_tag_id );
		$ar_indexations_tag_id = array_unique($ar_indexations_tag_id);
		#dump($ar_indexations_tag_id, ' $ar_indexations_tag_id ++ '.to_string($ar_portal_tag_id));
		*/

		$added_tags = 0;
		if (!empty($ar_indexations_tag_id)) {

			$all_text_tags = array_unique(array_merge($matches_indexIn[$index_tag_id], $matches_indexOut[$index_tag_id]));
				#dump($all_text_tags, ' $all_text_tags ++ '.to_string());

			foreach ($ar_indexations_tag_id as $current_tag_id) {
				if (!in_array($current_tag_id, $all_text_tags)) {
					#dump($current_tag_id, ' current_tag_id +++++++++ '.to_string());
					#$new_pair = "[index-d-{$current_tag_id}][/index-d-{$current_tag_id}] ";

					$state = 'd';
					$label = $current_tag_id;

					$tag_in   = TR::build_tag('structIn',  $state, $current_tag_id, $label, '');
					$tag_out  = TR::build_tag('structOut', $state, $current_tag_id, $label, '');
					$new_pair = $tag_in . $tag_out;

					$raw_text = $new_pair . $raw_text;
					$added_tags++;
				}
			}
		}//end if (!empty($ar_indexations_tag_id)) {


		if ($added_tags>0 || $changed_tags>0) {

			$response->result = true;
			$response->msg 	  = strtoupper(label::get_label('atencion')).": ";	// WARNING
			
			if($added_tags>0)
			$response->msg .= sprintf(" %s ".label::get_label('etiquetas_index_borradas'),$added_tags);	// deleted index tags was created at beginning of text.

			if($changed_tags>0)
			$response->msg .= sprintf(" %s ".label::get_label('etiquetas_index_fijadas'),$changed_tags); // broken index tags was fixed.

			$response->msg .= " ".label::get_label('etiquetas_revisar');	// Please review position of blue tags
			
			# UPDATE MAIN DATO
			$this->set_dato($raw_text);

			# SAVE
			if($save===true) {
				$this->Save();
				#$response->msg .= ". Text repaired, has been saved.";
			}else{
				$response->msg .= " ".label::get_label('etiqueta_salvar_texto'); // and saved text
			}

			$response->total = round(microtime(1)-$start_time,4)*1000 ." ms";
		}	
			
		return $response;		
	}//end fix_broken_struct_tags



	/**
	* GET_RELATED_COMPONENT_AV_TIPO
	* @return 
	*/
	public function get_related_component_av_tipo() {
		$current_elated_component_av = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($this->tipo, $modelo_name='component_av', $relation_type='termino_relacionado');
		if (isset($current_elated_component_av[0])) {
			return $current_elated_component_av[0];
		}else{
			return null;
		}
	}//end get_related_component_av_tipo
	


	/**
	* GET_COMPONENT_INDEXATIONS
	* @return array $ar_indexations
	*/
	protected function get_component_indexations($type) {
			
		# Search relation index in hierarchy tables		
		$options = new stdClass();
			$options->fields = new stdClass();
			$options->fields->section_tipo 	= $this->section_tipo;
			$options->fields->section_id 	= $this->parent;
			$options->fields->component_tipo= $this->tipo;	
			$options->fields->type 			= $type;;	
			$options->ar_tables 			= array('matrix_hierarchy');

		$result = component_relation_index::get_indexations_search( $options );			

		$ar_indexations = array();
		while ($rows = pg_fetch_assoc($result)) {

			$current_section_id   	= $rows['section_id'];
			$current_section_tipo 	= $rows['section_tipo'];
			$relations 				= json_decode($rows['relations']);
				#dump($relation, ' relation ++ '.to_string());

			$ar_indexations[] = $relations;						
		}//end while
		// dump($ar_indexations, ' $ar_indexations ++ '.to_string());

		return (array)$ar_indexations;
	}//end get_component_indexations


	
	/**
	* GET_DIFFUSION_OBJ
	*/
	public function get_diffusion_obj( $propiedades ) {
		#dump($propiedades,'$propiedades');
		$diffusion_obj = parent::get_diffusion_obj( $propiedades );
		/*
		$diffusion_obj->component_name		= get_class($this);
		$diffusion_obj->parent 				= $this->get_parent();
		$diffusion_obj->tipo 				= $this->get_tipo();
		$diffusion_obj->lang 				= $this->get_lang();
		$diffusion_obj->label 				= $this->get_label();		
		$diffusion_obj->columns['valor']	= $this->get_valor();
		*/

		
		$section_tipo = $this->section_tipo; 

		if(isset($propiedades['rel_locator'])) {

			#dump($propiedades['rel_locator'],"propiedades rel_locator");
			$rel_locator_obj= $propiedades['rel_locator'];
			#$rel_locator 	= component_common::build_locator_from_obj( $rel_locator_obj );
			$fragment_info	= component_text_area::get_fragment_text_from_rel_locator( $rel_locator_obj );
			$texto 			= $this->get_dato();

			# FRAGMENT
			$diffusion_obj->columns['fragment']	= $fragment_info[0];

			# RELATED
			$current_related_tipo 	= RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($rel_locator_obj->component_tipo, $modelo_name='component_', $relation_type='termino_relacionado');

			# No related term is present
			if(empty($current_related_tipo[0])) return $diffusion_obj;

			$current_related_tipo = $current_related_tipo[0];
			$related_modelo_name  = RecordObj_dd::get_modelo_name_by_tipo($current_related_tipo,true);

			switch ($related_modelo_name) {

				case 'component_av':

					# TC
					$tag_in_pos  	= $fragment_info[1];
					$tag_out_pos 	= $fragment_info[2];
					$tc_in 		 	= OptimizeTC::optimize_tcIN($texto, false, $tag_in_pos, $in_margin=0);
					$tc_out 	 	= OptimizeTC::optimize_tcOUT($texto, false, $tag_out_pos, $in_margin=100);
						#dump($tc_in ,"tc_in - tc_out:$tc_out, tag_in_pos:$tag_in_pos - tag_out_pos:$tag_out_pos ");

					$tcin_secs		= OptimizeTC::TC2seg($tc_in);
			        $tcout_secs		= OptimizeTC::TC2seg($tc_out);
			        $duracion_secs	= $tcout_secs - $tcin_secs;
			        $duracion_tc	= OptimizeTC::seg2tc($duracion_secs);

			        $diffusion_obj->columns['related_tipo']	= $current_related_tipo;
			        $diffusion_obj->columns['related']		= $related_modelo_name;
			        $diffusion_obj->columns['tc_in']		= $tc_in;
			        $diffusion_obj->columns['tc_out']		= $tc_out;
			        $diffusion_obj->columns['duracion_tc']	= $duracion_tc;
			        $diffusion_obj->columns['tcin_secs']	= $tcin_secs;
			        $diffusion_obj->columns['tcout_secs']	= $tcout_secs;		        	
					
					#$component_av   = new component_av($current_related_tipo, $this->get_parent(), 'edit');
					$component_av   = component_common::get_instance($related_modelo_name,
																	 $current_related_tipo,
																	 $this->get_parent(),
																	 'list',
																	 DEDALO_DATA_LANG,
																	 $section_tipo);
					$video_id 		= $component_av->get_video_id();

					$diffusion_obj->columns['video_id']	= $video_id;
			        #$diffusion_obj->columns['video_url']	= $duracion_tc;

			        #dump($diffusion_obj, ' diffusion_obj ++ '.to_string());

					break;

				case 'component_image':

					break;

				case 'component_geolocation':
					
					break;

				default:
					throw new Exception("Error Processing Request. Current related $related_modelo_name is not valid. Please configure textarea for this media ", 1);					
			}
			#dump($diffusion_obj,'$diffusion_obj');
		}		
		
		return $diffusion_obj;
	}//end get_diffusion_obj 



	/**
	* RENDER_LIST_VALUE
	* Overwrite for non default behaviour
	* Receive value from section list and return proper value to show in list
	* Sometimes is the same value (eg. component_input_text), sometimes is calculated (e.g component_portal)
	* @param string $value
	* @param string $tipo
	* @param int $parent
	* @param string $modo
	* @param string $lang
	* @param string $section_tipo
	* @param int $section_id
	*
	* @return string $list_value
	*/ // ($value, $tipo, $parent, $modo, $lang, $section_tipo, $section_id, $caller_component_tipo=null) ? locator ?? 
	public static function render_list_value($value, $tipo, $parent, $modo, $lang, $section_tipo, $section_id, $locator=null, $caller_component_tipo=null) {
		#dump($value, ' value ++ '.$tipo.' - '.to_string($locator)); //get_fragment_text_from_tag( $tag_id, $tag_type, $raw_text ) {
		
		# Ignore DB data
		$value = null;


		$lang_received = $lang;

		# Always use original lang (defined by optional component_select_lang asociated)
		$original_lang 	= self::force_change_lang($tipo, $parent, $modo, $lang, $section_tipo);		
		$component 		= component_common::get_instance(__CLASS__,
														 $tipo,
													 	 $parent,
													 	 $modo,
														 $original_lang,
													 	 $section_tipo); 
		$value 			= $component->get_valor_list_html_to_save();
			#dump($value, ' value ++ '.to_string());
		#$value = $component->get_valor($original_lang);		

		#
		# FALLBACK
		#$calculated_value=false;
		#if (empty($value)) {
			/*
			# Fallback 1 Fallback to main_lang
			$main_lang = common::get_main_lang( $section_tipo );	#dump($main_lang, ' main_lang ++ '.to_string(DEDALO_DATA_LANG_DEFAULT));
			if ($main_lang!=$lang) {

				$component 	= component_common::get_instance(__CLASS__,
															 $tipo,
														 	 $parent,
														 	 $modo,
															 $main_lang,
														 	 $section_tipo); 
				
				$value = $component->get_valor($main_lang);
				#$value = component_common::decore_untranslated( $value );
					#dump($value, ' value ++ '.to_string($main_lang));
				$calculated_value=true;
			}
			*/			
		#}//end calculated_value
		

		#$obj_value = json_decode($value); # Evitamos los errores del handler accediendo directamente al json_decode de php
		$obj_value = $value;

		# value from database is always an array of strings. default we select first element (complete text)
		# other array index are fragments of complete text
		$current_tag = 0;

		#
		# Portal tables can reference fragments of text inside components (tags). In this cases
		# we verify current required text is from correct component and tag		
		if ( isset($locator->component_tipo) && isset($locator->tag_id) ) {
			$locator_component_tipo = $locator->component_tipo;
			$locator_tag_id 		= $locator->tag_id;
			if ($locator_component_tipo===$tipo) {
				$current_tag = (int)$locator_tag_id;
			}
		}
		
		if (is_object($obj_value) && isset($obj_value->$current_tag)) {
			$list_value = $obj_value->$current_tag;
		}else{
			$list_value = $value;
		}

		if (!is_string($list_value)) {
			dump( debug_backtrace() );
			dump($list_value, ' list_value ++ '.to_string()); die();
		}		

		# TRUNCATE ALL FRAGMENTS		
		TR::limpiezaFragmentoEnListados($list_value,160);

		#if($calculated_value===true) $list_value = component_common::decore_untranslated( $list_value );
		if($lang_received!==$original_lang) $list_value = component_common::decore_untranslated( $list_value );


		return $list_value;		
	}//end render_list_value



	/**
	* GET_DIFFUSION_VALUE
	* Overwrite component common method
	* Calculate current component diffusion value for target field (usually a mysql field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( $lang=null ) {
		
		$diffusion_value = $this->get_dato();  # Important: use raw text


		return (string)$diffusion_value;
	}//end get_diffusion_value



	/**
	* GET_VALOR_LIST_HTML_TO_SAVE
	* Usado por section:save_component_dato
	* Devuelve a section el html a usar para rellenar el 'campo' 'valor_list' al guardar
	* Por defecto será el html generado por el componente en modo 'list', pero en algunos casos
	* es necesario sobre-escribirlo, como en component_portal, que ha de resolverse obigatoriamente en cada row de listado
	*
	* NOTA : El valor a guardar del text area NO es un string sino un objeto que contine los fragmentos en que se divide
	* el texto (1 si no hay fragmentos definidos) limitados a un largo apropiado a los listados (ej. 25 chars)
	*
	* @see class.section.php
	* @return string $html
	*/
	public function get_valor_list_html_to_save() {
		# Store current modo
		$modo_previous = $this->get_modo();

		# Temporal modo
		$this->set_modo('list');

			#
			# OBJETO CON LOS FRAGMENTOS DE TEXTO
			$obj_fragmentos	= new stdClass();
			$max_char 		= 256;			
			$valor 			= (string)$this->get_valor(); // Full text source

			# 
			# FIRST fragment (key 0) always is a substring of whole text
			if (strlen($valor)>$max_char) {
				$fragmento_text = tools::truncate_text($valor,$max_char);
			}else{
				$fragmento_text = $valor;
			}
			# El fragmento 0 es el texto recortado. Los siguientes fragmentos van con los keys acorde a los tag_id correspondientes		
			$key=0;
			$obj_fragmentos->$key = $fragmento_text;
			
			#
			# NEXT fragments keys(1,2,..) (if tags exists)
			$tags_en_texto	= (array)$this->get_ar_relation_tags();		// No contempla las marcas 'struct' !!!
				#dump($tags_en_texto, ' $tags_en_texto ++ '.to_string());
			if (!empty($tags_en_texto[0]) && count($tags_en_texto[0])>0) {
				#dump($tags_en_texto[0], '$tags_en_texto[0] ++ '.to_string());
				$tag_id_key = 4;
				foreach ($tags_en_texto[0] as $key => $tag) {					
					#dump($tag, ' tag ++ '.to_string($key));
					// $tag here is like [index-n-8-label in 8-data::data]
					switch (true) {
						case (strpos($tag, 'index')!==false):
							$tag_type = 'index';
							$pattern  = TR::get_mark_pattern('indexIn');
							preg_match($pattern, $tag, $matches);
								#dump($matches, ' matches ++ '.to_string());
							$tag_id = $matches[$tag_id_key];
							break;
						/*
						case (strpos($tag, 'struct')!==false):
							$tag_type = 'struct';
							$pattern  = TR::get_mark_pattern('structIn');
							preg_match($pattern, $tag, $matches);
								#dump($matches, ' matches ++ '.to_string());
							$tag_id = $matches[$tag_id_key];
							break;*/
						default:
							debug_log(__METHOD__." Making fallback to index because tag is NOT WEEL DEFINED  ".to_string($tag), logger::ERROR);
							$tag_type = 'index';
							$pattern  = TR::get_mark_pattern('indexIn');
							preg_match($pattern, $tag, $matches);
								#dump($matches, ' matches ++ '.to_string());
							$tag_id = $matches[$tag_id_key];
							break;
					}
					#dump($tag_id, ' tag_id ++ '.to_string($key));
					#continue;

					$ar_fragmento = (array)component_text_area::get_fragment_text_from_tag($tag_id, $tag_type, $this->dato);					
					if(isset($ar_fragmento[0]) && strlen($ar_fragmento[0])>$max_char) {
						$fragmento_text = mb_substr($ar_fragmento[0],0,$max_char);
						if (strlen($ar_fragmento[0])>$max_char) {
							$fragmento_text .= '..';
						}
					}else{
						$fragmento_text = isset($ar_fragmento[0]) ? $ar_fragmento[0] : '';
					}
					
					$tag_id = $tags_en_texto[$tag_id_key][$key];			
					$obj_fragmentos->$tag_id = $fragmento_text;
				}
			}//end if (!empty($tags_en_texto[0]) && count($tags_en_texto[0])>0)
	
		
		# Return it to anterior mode after get the html
		$this->set_modo($modo_previous);	# Important!
		
		return (object)$obj_fragmentos;
	}//end get_valor_list_html_to_save



	/**
	* GET_RELATED_COMPONENT_select_lang
	* @return string $tipo | null
	*/
	public function get_related_component_select_lang() {

		$tipo = null;
		$related_terms = $this->get_ar_related_by_model('component_select_lang');
			#dump($related_terms, ' related_terms ++ '.to_string());

		switch (true) {
			case count($related_terms)===1 :
				$tipo = reset($related_terms);
				break;
			case count($related_terms)>1 :
				debug_log(__METHOD__." More than one related component_select_lang are found. Please fix this ASAP ".to_string(), logger::ERROR);
				break;
			default:
				break;
		}	

		return $tipo;		
	}//end get_related_component_select_lang



	/**
	* GET_DESCRIPTORS
	* Return all descriptors associated to index tag of current raw text or fragment
	* looking for index in tags inside
	* @return array $ar_descriptors
	*/
	public static function get_descriptors( $raw_text, $section_tipo, $section_id, $component_tipo ) {
		
		$ar_descriptors = array();

		# Search index in locators
		# INDEX IN
		#$pattern = TR::get_mark_pattern($mark='indexIn',$standalone=false);
		$pattern = TR::get_mark_pattern($mark='index',$standalone=true);
		preg_match_all($pattern,  $raw_text,  $matches_indexIn, PREG_PATTERN_ORDER);
			#dump($matches_indexIn,"matches_indexIn ".to_string($pattern));
		$total_indexIn = 0;
		if (isset($matches_indexIn[0])) {
			$total_indexIn = count($matches_indexIn[0]);
		}
		#dump($matches_indexIn[0], ' matches_indexIn[0] ++ '.to_string($raw_text)); die(); return array();

		if ($total_indexIn===0) {
			return $ar_descriptors;
		}

		foreach ($matches_indexIn[0] as $key => $tag) {
			
			$locator = new locator();
				$locator->set_section_tipo($section_tipo);
				$locator->set_section_id($section_id);
				$locator->set_component_tipo($component_tipo);

			$ar_index = RecordObj_descriptors::get_indexations_for_locator( $locator );

			$ar_descriptors[$tag] = $ar_index;
		}
		#dump($ar_descriptors, ' $ar_descriptors ++ '.to_string()); die();
		
		return (array)$ar_descriptors;
	}//end get_descriptors



	/**
	* GET_TAGS_PERSON
	* Get available tags for insert in text area. Intervieved, informants, etc..
	* @return array $ar_tags_inspector
	*/
	public function get_tags_person() {
		
		$tags_person = array();

		$section_id 		= $this->get_parent();
		$section_tipo		= $this->get_section_tipo();
		$section_top_tipo 	= TOP_TIPO;	

		$propiedades = $this->get_propiedades();
		if (!isset($propiedades->tags_person)) {
			debug_log(__METHOD__." Warning: misconfigured properties for tags_persons (section_top_tipo: $section_top_tipo) ".to_string($propiedades), logger::WARNING);
			return $tags_person;
		}
		elseif (!isset($propiedades->tags_person->$section_top_tipo)) {
			debug_log(__METHOD__." Warning: bad top_tipo for tags_persons (section_top_tipo: $section_top_tipo) ".to_string($propiedades), logger::WARNING);
			return $tags_person;
		}

		# Resolve obj value
		$ar_objects = array();
		foreach ((array)$propiedades->tags_person->$section_top_tipo as $key => $obj_value) {

			if ($obj_value->section_tipo===$this->section_tipo) {

				$obj_value->section_id = $section_id; // inject current record section id (parent)

				# Add direclty
				$ar_objects[] = $obj_value;
				
			}else{

				# Recalculate indirectly
				$ar_references = $this->get_ar_tag_references($obj_value->section_tipo, $obj_value->component_tipo);
				if (empty($ar_references)) {
					debug_log(__METHOD__." Error on calculate section_id from inverse locators $this->section_tipo - $this->parent ".to_string(), logger::ERROR);
					continue;
				}
				foreach ($ar_references as $reference_section_id) {
					
					$new_obj_value = clone $obj_value;
					$new_obj_value->section_id = $reference_section_id;
					$ar_objects[]  = $new_obj_value;
				}			
			}
		}
		#dump($ar_objects, ' ar_objects ++ '.to_string()); exit();
		$resolved=array();
		foreach ((array)$ar_objects as $key => $obj_value) {
			
			$current_section_tipo 	= $obj_value->section_tipo;
			$current_section_id 	= $obj_value->section_id;
			$current_component_tipo = $obj_value->component_tipo;
			$current_state 			= $obj_value->state;
			$current_tag_id 		= !empty($obj_value->tag_id) ? $obj_value->tag_id : "1";

			$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($current_component_tipo,true);
			$component 		= component_common::get_instance($modelo_name,
															 $current_component_tipo,
															 $current_section_id,
															 'list',
															 DEDALO_DATA_NOLAN,
															 $current_section_tipo);
			# TAG
			$dato = $component->get_dato();
			foreach ($dato as $key => $current_locator) {

				if (in_array($current_locator->section_id,$resolved)) {
					continue;
				}
			
				# Add current component tipo to locator stored in tag
				$current_locator->component_tipo = $current_component_tipo;

				# Label
				$label = (object)component_text_area::get_tag_person_label($current_locator);

				# Tag
				$tag_person = self::build_tag_person(array(
													'state'=>$current_state,
													'tag_id'=>$current_tag_id,
													'label'=>$label->initials,
													'data'=>$current_locator
												));
				$element = new stdClass();
					$element->tag 		= $tag_person;
					#$element->tag_image = TR::addTagImgOnTheFly($element->tag);
					$element->role 		= $label->role;  // RecordObj_dd::get_termino_by_tipo($current_component_tipo,DEDALO_APPLICATION_LANG,true);
					$element->full_name = $label->full_name;

					$element->state 	= $current_state;
					$element->tag_id 	= $current_tag_id;
					$element->label 	= $label->initials;
					$element->data 	= $current_locator;			

				$tags_person[] = $element;

				$resolved[] = $current_locator->section_id;
			}
		}
		#dump($tags_person, ' tags_person ++ '.to_string());
		
		return (array)$tags_person;
	}//end get_tags_person



	/**
	* GET_AR_TAG_REFERENCES
	* Resolves portal and other elements that are not in this section (inverse locator)
	* @return array $ar_tag_references
	*/
	public function get_ar_tag_references($section_tipo, $component_tipo) {
		
		$ar_tag_references = array();

		$section = section::get_instance($this->parent, $this->section_tipo);
		$my_inverse_locators = $section->get_inverse_locators();
			#dump($my_inverse_locators, ' $my_inverse_locators ++ '.to_string());		
			#$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);	

		// Calculate all references to this resource of section tipo $section_tipo (like 'oh1')
		$ar_caller_section_id = array();
		foreach ((array)$my_inverse_locators as $key => $current_locator) {

			if ( $current_locator->section_tipo===$section_tipo ) {
				$ar_caller_section_id[] = $current_locator->section_id;			
			}
		}

		return $ar_caller_section_id;
	}//end get_ar_tag_references



	/**
	* BUILD_PERSON
	* Format like [person-q-Pepe%20lópez%20de%20l'horta%20y%20Martínez-data:{locator}:data]
	* @return 
	*/
	public function build_tag_person($ar_data) {

		$type 		  = 'person';
		$tag_id 	  = $ar_data['tag_id'];
		$state 		  = $ar_data['state'];
		$label 	 	  = trim($ar_data['label']);
		$locator 	  = $ar_data['data'];
		$locator_json = json_encode($locator);	
		$data 		  = $locator_json;	

		$person_tag   = TR::build_tag($type, $state, $tag_id, $label, $data); 	// '[person-'.$state.'-'.$label.'-data:'.$locator_json.':data]';
		#$person_tag = '[person-data:'.$section_tipo.'_'.$section_id.':data]';
		
		return $person_tag;
	}//end build_tag_person



	/**
	* GET_TAG_PERSON_LABEL
	* Build tag label to show in transcriptions tag image of persons
	* @return object $label
	*/
	public static function get_tag_person_label($locator) {
		
		# Fixes tipos
		$ar_tipos = array(	'name'=>'rsc85',
							'surname'=>'rsc86'
						 );

		$label = new stdClass();
			$label->initials  = '';
			$label->full_name = '';
			$label->role 	  = '';

		if (isset($locator->component_tipo)) {
			$label->role 	  = RecordObj_dd::get_termino_by_tipo($locator->component_tipo,DEDALO_APPLICATION_LANG,true);
		}

		foreach ($ar_tipos as $key => $tipo) {

			$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$component 		= component_common::get_instance($modelo_name,
															 $tipo,
															 $locator->section_id,
															 'list',
															 DEDALO_DATA_NOLAN,
															 $locator->section_tipo);
			$dato = $component->get_valor(0);

			switch ($key) {
				case 'name':
					$label->initials .= mb_substr($dato,0,3);
					$label->full_name .= $dato;
					break;
				case 'surname':
					if (!empty($dato)) {
						$ar_parts = explode(' ', $dato);
						if (isset($ar_parts[0])) {
							$label->initials .= mb_substr($ar_parts[0],0,2);
						}
						if (isset($ar_parts[1])) {
							$label->initials .= mb_substr($ar_parts[1],0,2);
						}
						$label->full_name .= ' '.$dato;						
					}
					break;				
				default:
					# code...
					break;
			}
		}
		#$label = mb_strtolower($label);

		return (object)$label;
	}//end get_tag_person_label



	/**
	* PERSON_USED
	* @return array $ar_section_id
	*/
	public static function person_used( $locator ) {

		$ar_section_id = array();
		
		// Search in all transcriptions looking tags from this person
		$section_id   = $locator->section_id;
		$section_tipo = $locator->section_tipo;

		// Like '%''section_id'':''137'',''section_tipo'':''rsc194''%'::text		
		$search_string = "''section_id'':''$section_id'',''section_tipo'':''$section_tipo''";

		$matrix_table = common::get_matrix_table_from_tipo(DEDALO_SECTION_RESOURCES_AV_TIPO);

		$strQuery = "
		SELECT a.section_id, a.section_tipo
		FROM \"$matrix_table\" a
		WHERE
		 -- audiovisual resource section
		 a.section_tipo = 'rsc167'  
		 AND
		 -- search pseudo locator in all langs
		 a.datos#>>'{components, rsc36, dato}' ILIKE '%".$search_string."%'::text;
		";
		#dump($strQuery, ' $strQuery ++ '.to_string($strQuery ));

		$result = JSON_RecordObj_matrix::search_free($strQuery);
		$n_rows = pg_num_rows($result);		
		while ($rows = pg_fetch_assoc($result)) {
			$ar_section_id[] = $rows['section_id'];		
		}
		#dump($ar_section_id, ' ar_section_id ++ '.to_string());		

		return (array)$ar_section_id;
	}//end person_used



	/**
	* CREATE_NEW_NOTE
	* @return 
	*/
	public static function create_new_note() {
		
		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= 'Error. Request failed';

		#$user_id = navigator::get_user_id();
		$section_tipo = DEDALO_NOTES_SECTION_TIPO;

		$section 	= section::get_instance(null, $section_tipo);
		$section_id = $section->Save();

		$locator = new locator();
			$locator->set_section_tipo($section_tipo);
			$locator->set_section_id($section_id);
		
		$response->result = $locator;
		$response->msg 	  = 'Created notes record successfully with locator: '.json_encode($locator);

		return (object)$response;
	}//end create_new_note



	/**
	* CREATE_NEW_STRUCT
	* @return object $response
	*/
	public static function create_new_struct() {
		
		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= 'Error. Request failed';

		#$user_id = navigator::get_user_id();
		$section_tipo = DEDALO_STRUCTURATION_SECTION_TIPO;

		$section 	= section::get_instance(null, $section_tipo);
		$section_id = $section->Save();

		$locator = new locator();
			$locator->set_section_tipo($section_tipo);
			$locator->set_section_id($section_id);
		
		$response->result = $locator;
		$response->msg 	  = 'Created new_struct record successfully with locator: '.json_encode($locator);

		return (object)$response;
	}//end create_new_struct



	/**
	* REGENERATE_COMPONENT
	* Force the current component to re-save its data
	* Note that the first action is always load dato to avoid save empty content
	* @see class.tool_update_cache.php
	* @return bool
	*/
	public function regenerate_component() {

		# Force loads dato always !IMPORTANT
		$dato = $this->get_dato();

		# Converts old timecodes
		$old_pattern = '/(\[TC_([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})_TC\])/';
		$new_dato 	 = preg_replace($old_pattern, "[TC_$2.000_TC]", $dato);
		
		$this->set_dato($new_dato);

		# Save component data. Defaults arguments: $update_all_langs_tags_state=false, $cleant_text=true 
		$this->Save($update_all_langs_tags_state=false, $cleant_text=true);
			
		
		return true;
	}//end regenerate_component


	
	
}
?>