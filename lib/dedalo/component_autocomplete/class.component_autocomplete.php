<?php
/*
* CLASS COMPONENT REF
 La idea es que sea un puntero hacia otros componentes. Que guarde el id_matrix y el tipo y se resuelva al mostrarse.
 Ejemplo: guardamos el id_matrix del usuario actual desde activity y al mostrar el componente en los listado de actividad, mostramos su resolución
 en lugar de su dato (Admin por )... por acabar..
*/


class component_autocomplete extends component_common {
	
	# Overwrite __construct var lang passed in this component
	protected $lang = DEDALO_DATA_NOLAN;

	public $ar_target_section_tipo ;		# Used to fix section tipo (calculado a partir del componente relacionado de tipo section) Puede ser virtual o real

	# Array of related terms in structure (one or more)
	protected $ar_terminos_relacionados;

	# referenced component tipo
	public $tipo_to_search;
	

	
	function __construct($tipo=null, $parent=null, $modo='edit', $lang=DEDALO_DATA_NOLAN, $section_tipo=null) {

		# Force always DEDALO_DATA_NOLAN
		$lang = $this->lang;

		# Creamos el componente normalmente
		parent::__construct($tipo, $parent, $modo, $lang, $section_tipo);

		if(SHOW_DEBUG) {
			$traducible = $this->RecordObj_dd->get_traducible();
			if ($traducible=='si') {
				throw new Exception("Error Processing Request. Wrong component lang definition. This component $tipo (".get_class().") is not 'traducible'. Please fix this ASAP", 1);
			}
		}
	}


	# GET DATO : 
	public function get_dato() {
		$dato = parent::get_dato();

		if (!empty($dato) && !is_array($dato)) {
			#dump($dato,"dato");
			trigger_error("Error: ".__CLASS__." dato type is wrong. Array expected and ".gettype($dato)." is received for tipo:$this->tipo, parent:$this->parent");
			$this->set_dato(array());
			$this->Save();
		}
		if ($dato==null) {
			$dato=array();
		}
		#$dato = json_handler::decode(json_encode($dato));	# Force array of objects instead default array of arrays
		#dump($dato," dato");
		return (array)$dato;
	}

	# SET_DATO
	public function set_dato($dato) {
		if (is_string($dato)) { # Tool Time machine case, dato is string
			$dato = json_handler::decode($dato);
		}
		if(SHOW_DEBUG) {
			#dump($dato," dato original");
		}
		if (is_object($dato)) {
			$dato = array($dato); // IMPORTANT 
		}

		# Remove possible duplicates
		$dato_unique=array();
		foreach ((array)$dato as $locator) {
			if (!in_array($locator, $dato_unique)) {
				$dato_unique[] = $locator;
			}		
		}
		$dato = $dato_unique;

		parent::set_dato( (array)$dato );		
	}
	


	/**
	* SAVE OVERRIDE
	* Overwrite component_common method
	*/
	public function Save() {		
		#dump($this->get_dato()," dato");
		# Salvamos de forma estándar
		return parent::Save();
	}



	

	

	/**
	* GET VALOR 
	* Get resolved string representation of current value (expected id_matrix of section or array)
	* @return array $this->valor
	*/
	public function get_valor( $lang=DEDALO_DATA_LANG, $format='string', $ar_related_terms=false ) {
		/*
		if (isset($this->valor)) {
			if(SHOW_DEBUG) {
				#error_log("Catched valor !!! from ".__METHOD__);
			}
			return $this->valor;
		}
		*/	

		
		$dato = $this->get_dato();
			#dump($dato,'dato '.gettype($dato) );

		if ( empty($dato) ) {
			if ($format=='array') {
				return array();
			}else{
				return '';
			}
		}

		# lang never must be DEDALO_DATA_NOLAN
		if ($lang==DEDALO_DATA_NOLAN) {
			$lang = DEDALO_DATA_LANG;
		}

		
		# Test dato format (b4 changed to object)
		foreach ($dato as $key => $value) {
			if (!is_object($value)) {
				if(SHOW_DEBUG) {
					dump($dato," dato ($value) is not object!! gettype:".gettype($value));
				}
				trigger_error(__METHOD__." Wrong dato format. OLD format dato in $this->label $this->tipo [section_id:$this->parent].Expected object locator, but received: ".gettype($value) .' : '. print_r($value,true) );
				return $this->valor = null;
			}
		}
		$ar_componets_related = array();

		# By default, ar_related_terms ius calculated. In some cases (diffusion for example) is needed overwrite ar_related_terms to obtain especific 'valor' form component
		if ($ar_related_terms===false) {
			$ar_related_terms = $this->RecordObj_dd->get_relaciones();
				
			foreach ((array)$ar_related_terms as $ar_value) foreach ($ar_value as $modelo => $component_tipo) {
				$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
				if ($modelo_name !='section'){
					$ar_componets_related[] = $component_tipo;
				}
			}
		}else{
			$ar_componets_related = $ar_related_terms;
		}
		#dump($ar_componets_related, ' ar_componets_related ++ '.to_string($this->tipo));



			
		$ar_values=array();
		foreach ($dato as $current_locator) {
			$value=array();

			foreach ($ar_componets_related as $component_tipo) {
				$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
				
				$current_component = component_common::get_instance($modelo_name,
																  $component_tipo,
																  $current_locator->section_id,
																  'edit',
																  $lang,
																  $current_locator->section_tipo);
				$value[] = $current_component->get_valor();
			}
			$current_locator_json = json_encode($current_locator);
			$ar_values[$current_locator_json] = implode(' ', $value);
		}


		if ($format=='array') {
			$valor = $ar_values;
		}else{
			$valor = implode("<br>", $ar_values);
		}
		
		return $valor;

	}//end get valor



	/**
	* GET_VALOR_EXPORT
	* Return component value sended to export data
	* @return string $valor
	*/
	public function get_valor_export( $valor=null, $lang=DEDALO_DATA_LANG ) {
		
		if (is_null($valor)) {
			$dato = $this->get_dato();				// Get dato from DB
		}else{
			$this->set_dato( json_decode($valor) );	// Use parsed json string as dato
		}

		#$valor = $this->get_valor($lang);
		$dato = $this->get_dato();
		if (empty($dato)) {
			if(SHOW_DEBUG) {
				#return "AUTOCOMPLETE: ";
			}
			return '';
		}

		#
		# TERMINOS_RELACIONADOS . Obtenemos los terminos relacionados del componente actual	
		$ar_terminos_relacionados = (array)$this->RecordObj_dd->get_relaciones();
			#dump($ar_terminos_relacionados, ' ar_terminos_relacionados');
		#
		# FIELDS
		$fields=array();
		$ar_skip = array(MODELO_SECTION, $exclude_elements='dd1129');
		foreach ($ar_terminos_relacionados as $key => $ar_value) {
			$modelo = key($ar_value);
			$tipo 	= $ar_value[$modelo];
			if (!in_array($modelo, $ar_skip)) {				
				$fields[] = $tipo;
			}
		}
		#dump($fields, ' fields ');

		$ar_resolved=array();
		foreach( (array)$dato as $key => $value) {
			#dump($value, ' value ++ '.to_string());
			$section_tipo 	= $value->section_tipo;
			$section_id 	= $value->section_id;

			foreach ($fields as $current_tipo) {				
			
				$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($current_tipo);
				$component 		= component_common::get_instance($modelo_name,
																 $current_tipo,
																 $section_id,
																 'list',
																 $lang,
																 $section_tipo);
				$ar_resolved[$section_id][] = $component->get_valor_export(null,$lang);
			}
		}
		#dump($ar_resolved, ' $ar_resolved ++ '.to_string());

		$valor_export='';
		foreach ($ar_resolved as $key => $ar_value) {
			$valor_export .= implode("\n", $ar_value) . "\n";
		}
		$valor_export = trim($valor_export);

		if(SHOW_DEBUG) {
			#return "AUTOCOMPLETE: ".$valor_export;
		}
		return $valor_export;

	}#end get_valor_export



	/**
	* GET_ar_target_section_tipo
	* Locate in structure TR the target section (remember, components are from real section, but you can target to virtual setion)
	* @return string $ar_target_section_tipo
	*//*
	public function get_ar_target_section_tipo($options=null) {
		#dump($this->RecordObj_dd->get_relaciones(), ' var');
		$ar_related_terms = (array)$this->RecordObj_dd->get_relaciones();		
		
		foreach ($ar_related_terms as $related_terms)
		foreach ($related_terms as $modelo => $current_tipo) {
			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
			if ($modelo_name=='section') {
				$ar_target_section_tipo = $current_tipo; break;
			}
		}
		if (!isset($ar_target_section_tipo)) {
			throw new Exception("Error Processing Request. Inconsistency detect. This component need related section always", 1);			
		}
		#dump($ar_target_section_tipo, ' ar_target_section_tipo');

		return $ar_target_section_tipo;

	}#end get_ar_target_section_tipo
	*/

	/**
	* GET_TIPO_TO_SEARCH
	* Locate in structure TR the component tipo to search
	* @return string $tipo_to_search
	*/
	public function get_tipo_to_search($options=null) {

		if(isset($this->tipo_to_search)) {
			return $this->tipo_to_search;
		}

		/* DESECHADO POR PROBLEMAS AL SELECCIONAR EL PRIMERO. EL ORDEN NO ES RESPETADO...
		$ar_related_terms = (array)$this->RecordObj_dd->get_relaciones();
			#dump($ar_related_terms, ' ar_related_terms');
		foreach ($ar_related_terms as $related_terms)		
		foreach ($related_terms as $modelo => $current_tipo) {
			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
			# Get first component only
			if (strpos($modelo_name, 'component_')!==false) {
				$tipo_to_search = $current_tipo; break;
			}
		}
		*/

		$ar_terminoID_by_modelo_name = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($this->tipo, 'component_', 'termino_relacionado'); 
				#dump($ar_terminoID_by_modelo_name, ' ar_terminoID_by_modelo_name '.$this->tipo.' ');
		$tipo_to_search = reset($ar_terminoID_by_modelo_name);

		if (!isset($tipo_to_search)) {
			throw new Exception("Error Processing Request. Inconsistency detect. This component need related component to search always", 1);			
		}
		#dump($tipo_to_search, ' tipo_to_search');

		# Fix value
		$this->tipo_to_search = $tipo_to_search;

		return $tipo_to_search;

	}#end get_tipo_to_search




	
	/**
	* AUTOCOMPLETE_SEARCH
	* Used by trigger on ajax call
	* This function search is almost identical to component_common->get_ar_list_of_values
	* @param string tipo
	* @param string string_to_search
	* @return array $output 
	*	Array format: id_matrix=>dato_string 
	*/
	public static function autocomplete_search($tipo, $ar_target_section_tipo, $string_to_search, $max_results=30, $id_path) {
			
		$ar_result=array();	
		
		foreach((array)$ar_target_section_tipo as $target_section_tipo) {	

			$component = component_common::get_instance(null, $tipo, null, 'edit', DEDALO_DATA_LANG, $target_section_tipo);
			$ar_list_of_values  = $component->get_ar_list_of_values(DEDALO_DATA_LANG, $id_path, $target_section_tipo);
				#dump($ar_list_of_values, ' ar_list_of_values ++ '.to_string($target_section_tipo));
			$result 			= search_string_in_array($ar_list_of_values->result,(string)$string_to_search);	#dump($ar_result," ar_result");

			$ar_result 			= array_merge($ar_result,$result);
				#dump($ar_list_of_values, ' ar_list_of_values ++ '.to_string($target_section_tipo));		
		}


		$output = array_slice($ar_result, 0, $max_results, true);
			#dump($output," ar_result");

		return $output;

	}#end autocomplete_search




	/**
	* GET_VALOR_LANG
	* Return the main component lang
	* If the component need change this langs (selects, radiobuttons...) overwritte this function
	* @return string $lang
	*/
	public function get_valor_lang() {

		$relacionados = (array)$this->RecordObj_dd->get_relaciones();
		
		#dump($relacionados,'$relacionados');
		if(empty($relacionados)){
			return $this->lang;
		}

		$termonioID_related = array_values($relacionados[0])[0];
		$RecordObjt_dd = new RecordObj_dd($termonioID_related);

		if($RecordObjt_dd->get_traducible() =='no'){
			$lang = DEDALO_DATA_NOLAN;
		}else{
			$lang = DEDALO_DATA_LANG;
		}
		return $lang;
	}




	/**
	* BUILD_SEARCH_COMPARISON_OPERATORS 
	* Note: Override in every specific component
	* @param array $comparison_operators . Like array('=','!=')
	* @return object stdClass $search_comparison_operators
	*/
	public function build_search_comparison_operators( $comparison_operators=array('=','!=') ) {
		return (object)parent::build_search_comparison_operators($comparison_operators);
	}#end build_search_comparison_operators



	/**
	* GET_SEARCH_QUERY (OVERWRITE COMPONENT COMMON)
	* Build search query for current component . Overwrite for different needs in other components 
	* (is static to enable direct call from section_records without construct component)
	* Params
	* @param string $json_field . JSON container column Like 'dato'
	* @param string $search_tipo . Component tipo Like 'dd421'
	* @param string $tipo_de_dato_search . Component dato container Like 'dato' or 'valor'
	* @param string $current_lang . Component dato lang container Like 'lg-spa' or 'lg-nolan'
	* @param string $search_value . Value received from search form request Like 'paco'
	* @param string $comparison_operator . SQL comparison operator Like 'ILIKE'
	*
	* @see class.section_records.php get_rows_data filter_by_search
	* @return string $search_query . POSTGRE SQL query (like 'datos#>'{components, oh21, dato, lg-nolan}' ILIKE '%paco%' )
	*/
	public static function get_search_query( $json_field, $search_tipo, $tipo_de_dato_search, $current_lang, $search_value, $comparison_operator='=') {
		$search_query='';
		if ( empty($search_value) ) {
			return $search_query;
		}
		switch (true) {
			case $comparison_operator=='=':
				$search_query = " $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}' @> '[$search_value]'::jsonb ";
				break;
			case $comparison_operator=='!=':
				$search_query = " ($json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}' @> '[$search_value]'::jsonb)=FALSE ";
				break;
		}
		
		if(SHOW_DEBUG) {
			$search_query = " -- filter_by_search $search_tipo ". get_called_class() ." \n".$search_query;
			#dump($search_query, " search_query for search_value: ".to_string($search_value)); #return '';
		}
		return $search_query;
	}//end get_search_query



	/**
	* CREATE_NEW_AUTOCOMPLETE_RECORD
	* Insert a new record on target section, set projects filter heritage, defdaults and text ar_data 
	* Return locator object of new created section
	* @param int $parent . section_id of current component_autocomplete
	* @param string $tipo . tipo of current component_autocomplete
	* @param string $target_section_tipo . tipo of section on create the record
	* @param string $section_tipo . section_tipo of current component_autocomplete
	* @param object $ar_data . Object with all component_tipo => value of component_autocomplete value elements
	* @return locator object. Locator of new created section to add in current component_autocomplete data
	*/
	public static function create_new_autocomplete_record($parent, $tipo, $target_section_tipo, $section_tipo, $ar_data ) {
		
		#
		# PROJECTS HERITAGE
		if ($section_tipo!=DEDALO_SECTION_PROJECTS_TIPO) {
			# All except main section Projects
			$source_ar_filter = section::get_ar_children_tipo_by_modelo_name_in_section($section_tipo, 'component_filter', true, true); //$section_tipo, $ar_modelo_name_required, $from_cache=true, $resolve_virtual=false
			if (!isset($source_ar_filter[0])) {
				if(SHOW_DEBUG) {
					throw new Exception("Error Processing Request. component_filter is not defined! ($section_tipo)", 1);
				}
				return "Error: component_filter is not defined!";
			}
			$source_component_filter = component_common::get_instance('component_filter',
																	  $source_ar_filter[0],
																	  $parent,
																	  'edit',
																	  DEDALO_DATA_NOLAN,
																	  $section_tipo);
			$source_component_filter_dato = $source_component_filter->get_dato();
				#dump($source_component_filter_dato, ' source_component_filter_dato'.to_string());die();
		}

		
		#
		# SECTION : Create a new section
		$section = section::get_instance(null,$target_section_tipo);

			# Inverse locator for store into the section
			$autocomplete_inverse_locator = new locator();
				$autocomplete_inverse_locator->set_section_id($parent);
				$autocomplete_inverse_locator->set_section_tipo($section_tipo);
				$autocomplete_inverse_locator->set_component_tipo($tipo);			

			$section->add_inverse_locator($autocomplete_inverse_locator);
			$section_id = $section->Save();

			
			
		
		#
		# FILTER : Set heritage of projects
		if ($section_tipo!=DEDALO_SECTION_PROJECTS_TIPO) {
			# All except main section Projects
			$target_ar_filter  = section::get_ar_children_tipo_by_modelo_name_in_section($target_section_tipo, 'component_filter', true, true);

			if (!isset($target_ar_filter[0])) {
				if(SHOW_DEBUG) {
					throw new Exception("Error Processing Request. target component_filter is not defined! ($target_section_tipo)", 1);
				}
				return "Error: target component_filter is not defined!";
			}
			$target_component_filter = component_common::get_instance('component_filter',
																	  $target_ar_filter[0],
																	  $section_id,'edit',
																	  DEDALO_DATA_NOLAN,
																	  $target_section_tipo);
			$target_component_filter->set_dato($source_component_filter_dato);
			$target_component_filter->Save();
		}
		

		#
		# COMPONENT_AUTOCOMPLETE
		$component_autocomplete 	= component_common::get_instance('component_autocomplete',
																	  $tipo,
																	  $section_id,
																	  'edit',
																	  DEDALO_DATA_NOLAN,
																	  $section_tipo);

		#
		# PROPIEDADES
		$propiedades = $component_autocomplete->get_propiedades();
		if (!empty($propiedades)) {
			
			if (isset($propiedades->filtered_by)) foreach($propiedades->filtered_by as $current_tipo => $current_value) {
				#dump($current_value, ' current_tipo - '.$current_tipo);
				$curren_modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
				$component 			= component_common::get_instance($curren_modelo_name,
																	$current_tipo,
																	$section_id,
																	'edit',
																	DEDALO_DATA_LANG,
																	$target_section_tipo);
				$component->set_dato($current_value);
				$component->Save();

				debug_log(__METHOD__." Updated target section component $current_tipo [$curren_modelo_name] to ".to_string($current_value), logger::DEBUG);
			}
		}
		#dump($propiedades, ' propiedades');	die("section_id: $section_id B");


		#
		# COMPONENTS
		# Format:
		# value: stdClass Object
		# (
		#    [rsc85] => a
		#    [rsc86] => b
		# )
		#
		foreach ($ar_data as $current_tipo => $current_value) {
			
			$curren_modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
			$component = component_common::get_instance($curren_modelo_name,
														$current_tipo,
														$section_id,
														'edit',
														DEDALO_DATA_LANG,
														$target_section_tipo);	
			$component->set_dato( $current_value );
			$component->Save();
		}


		#
		# RETURN LOCATOR OBJECT OF CREATED SECTION
		$locator = new locator();
			$locator->set_section_id($section_id);
			$locator->set_section_tipo($target_section_tipo);
				#dump($locator,'locator');

		return $locator;

	}//end create_new_autocomplete_record



	/**
	* ADD_LOCATOR
	*/
	public function add_locator($rel_locator) {
		# add inverse locator into the destination section
		$section_to_add = section::get_instance($rel_locator->section_id, $rel_locator->section_tipo);

		$autocomplete_inverse_locator = new locator();
			$autocomplete_inverse_locator->set_section_id($this->parent);
			$autocomplete_inverse_locator->set_section_tipo($this->section_tipo);			
			$autocomplete_inverse_locator->set_component_tipo($this->tipo);

		$section_to_add->add_inverse_locator($autocomplete_inverse_locator);
		$section_to_add->Save();



		return $rel_locator;
	}

	public function remove_locator($rel_locator) {

		# Remove inverse locator into the destination section
		$section_to_remove = section::get_instance($rel_locator->section_id, $rel_locator->section_tipo);

		$autocomplete_inverse_locator = new locator();
			$autocomplete_inverse_locator->set_section_id($this->parent);
			$autocomplete_inverse_locator->set_section_tipo($this->section_tipo);			
			$autocomplete_inverse_locator->set_component_tipo($this->tipo);

		$section_to_remove->remove_inverse_locator($autocomplete_inverse_locator);
		$section_to_remove->Save();

		return $rel_locator;
	}

	public function remove_inverse_locator_reference($rel_locator) {

		$dato 			= $this->get_dato();		#dump($dato, ' dato  1 ++ '.to_string());
		foreach ((array)$dato as $key => $current_locator) {
			
			if ($current_locator->section_id==$rel_locator->section_id &&
				$current_locator->section_tipo==$rel_locator->section_tipo) {
				// Remove all references, to whole section and partial section matches
				unset($dato[$key]);
			}
		}
		# maintain array index after unset value. ! Important for encode json as array later (if keys are not correlatives, object is created)
		$dato = array_values($dato);

		$this->set_dato($dato);

		return true;
	}



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
	*/
	public static function render_list_value($value, $tipo, $parent, $modo, $lang, $section_tipo, $section_id) {

		$component 	= component_common::get_instance(__CLASS__,
													 $tipo,
												 	 $parent,
												 	 'list',
													 DEDALO_DATA_NOLAN,
												 	 $section_tipo);

		
		# Use already query calculated values for speed
		$ar_records   = (array)json_handler::decode($value);
		$component->set_dato($ar_records);
		$component->set_identificador_unico($component->get_identificador_unico().'_'.$section_id); // Set unic id for build search_options_session_key used in sessions
		
		return  $component->get_valor($lang);
		
	}#end render_list_value


}
?>