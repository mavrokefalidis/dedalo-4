<?php
/**
* COMPONENT_COMMON
* Common methods of all components
*
*/
abstract class component_common extends common {

	# GENERAL VARS	
	protected $tipo;					# string component tipo in structur ex ('dd22') eq. terminoID
	protected $parent;					# int parent section_id
	protected $section_tipo;			# string parent section tipo
	protected $lang;					# string lang en estructura ('lg-esp')
	protected $valor_lang;				# string Idioma del valor final del componente (si es una lista de valor, el idioma del campo al que apunta que puede ser traducible aunque el componente no lo sea dato"1" valor:"Si" o "yes")
	protected $traducible;				# string definido en tesauro (si/no)
	protected $modo;					# string default edit
	protected $dato;					# object dato (json ecoded in db)
	protected $valor;					# string usually dato
	public $version_date;				# date normalmente despejado de time machine y asignado al component actual

	# STRUCTURE DATA
	public $RecordObj_dd;				# obj ts
	protected $modelo;
	protected $norden;
	protected $label;					# etiqueta

	protected $required;				# field is required . Valorar de usar 'Usable en Indexación' (tesauro) para gestionar esta variable
	protected $debugger;				# info for admin
	protected $ejemplo;					# ex. 'MO36001-GA'
	protected $ar_tools_name = array('tool_time_machine','tool_lang','tool_replace_component_data');
	protected $ar_tools_obj;
	protected $ar_authorized_tool_name;

	protected $exists_dato_in_any_lan = false;
	protected $dato_resolved;

	# Idioma esperado para este componente (usado para verificar que la estrucutra está bien formada)
	protected $expected_lang;

	# parent section obj (optional, util for component_av...)
	public $section_obj;

	# referenced section tipo (used by component_autocomplete, compoent_radio_button.. for set target section_tipo (propiedades) - aditional to referenced component tipo (TR)- )
	public $referenced_section_tipo;

	# CACHE COMPONENTS INTANCES
	#public static $ar_component_instances = array();	# array chache of called instances of components

	public $render_vars;

	# search_input_name. injected for records search
	public $search_input_name;


	/**
	* GET_INSTANCE
    * Singleton pattern
    * @returns array array of component objects by key
    */
    public static function get_instance($component_name=null, $tipo, $parent=null, $modo='edit', $lang=DEDALO_DATA_LANG, $section_tipo=null, $cache=true) {

    	# TIPO : MANDATORY
    	if (empty($tipo)) {
			throw new Exception("Error: on construct component : tipo is mandatory. tipo:$tipo, parent:$parent, modo:$modo, lanfg:$lang", 1);
		}	

		# PARENT : OPTIONAL (On save component, new section is created)
    	#if (empty($parent)) {
    		#if(SHOW_DEBUG===true) {
    	 		#dump($component_name,"component_name");
    	 	#}  		
    	#}

    	# SECTION_TIPO : OPTIONAL (if empty, section_tipo is calculated from: 1. page globals, 2. structure -only useful for real sections-)
		if (empty($section_tipo)) {
			/*
			$section_tipo = component_common::resolve_section_tipo($tipo);
			debug_log(__METHOD__." Called component without section tipo ".to_string($tipo), logger::DEBUG);
			*/
			trigger_error("Sorry. resolve_section_tipo is not supported anymore. Please fix this call ASASP");
			if(SHOW_DEBUG===true) {
				dump($section_tipo, ' DEBUG WARNING: TRIGGERED resolve_section_tipo from: '.to_string($tipo));
				dump(debug_backtrace(), ' debug_backtrace '.to_string());
			}
			return null;	
		}
		
    	if(SHOW_DEBUG===true) {
			if ( !empty($component_name) && strpos($component_name, 'component_')===false ) {
				dump($tipo," tipo");
				throw new Exception("Error Processing Request. section or ($component_name) intented to load as component", 1);				
			}
			if ( is_numeric($tipo) || !is_string($tipo) || !RecordObj_dd::get_prefix_from_tipo($tipo) ) {
				dump($tipo," tipo");
				throw new Exception("Error Processing Request. trying to use wrong var: '$tipo' as tipo to load as component", 1);				
			}
			if ( (!empty($parent) 
				 && ( (!is_numeric($parent) || abs($parent)<1)) && strpos($parent, DEDALO_SECTION_ID_TEMP)===false) )
				{
				dump($parent," parent");
				throw new Exception("Error Processing Request. trying to use wrong var: '$parent' as parent to load as component", 1);				
			}			
			$ar_valid_modo = array('edit','list','search','simple','list_tm','tool_portal','tool_lang','edit_tool','indexation','selected_fragment','tool_indexation','tool_transcription','print','edit_component','load_tr','update','portal_list','list_thesaurus','portal_list_view_mosaic',"edit_in_list");
			if ( empty($modo) || !in_array($modo, $ar_valid_modo) ) {
				#dump($modo," modo");
				#throw new Exception("Error Processing Request. trying to use wrong var: '$modo' as modo to load as component", 1);			
				debug_log(__METHOD__." trying to use empty or invalid modo: '$modo' as modo to load component $tipo. modo: ".to_string($modo), logger::DEBUG);	
			}
			if ( empty($lang) || strpos($lang, 'lg-')===false ) {
				dump($lang," lang");
				throw new Exception("Error Processing Request. trying to use wrong var: '$lang' as lang to load as component", 1);				
			}
			if (!empty($section_tipo)) {
				# Verify modelo_name is section
				$section_modelo_name = RecordObj_dd::get_modelo_name_by_tipo($section_tipo,true);
				if ($section_modelo_name!=='section') {
					dump($section_tipo," section_tipo - section_modelo_name: $section_modelo_name");
					if (empty($section_modelo_name)) {
						$msg = "Error. Current section ($section_tipo) don't exists or model is missing. Please fix structure ASAP";
						throw new Exception($msg, 1);
					}
					throw new Exception("Error Processing Request. Trying to use: $section_modelo_name ($section_tipo) as section. Verified modelo is: $section_modelo_name", 1);
				}				
				# Verify this section is a invalid resource call
				$ar_resources = array('rsc2','rsc75','rsc3','rsc4');
				if (in_array($section_tipo, $ar_resources) && $tipo!=='rsc88') {
					dump(debug_backtrace(), ' debug_backtrace '.to_string());
					#throw new Exception("Error Processing Request. Direct call to resource section_tipo ($section_tipo) is not legal", 1);
					debug_log(__METHOD__." ERROR - Error Processing Request. Direct call to resource section_tipo ($section_tipo) is not legal".to_string(), logger::ERROR);
				}else if($tipo!='dd200'){
					# Verify this section is from current component tipo
					$ar_terminoID_by_modelo_name = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo, 'section', 'parent');
					if (!isset($ar_terminoID_by_modelo_name[0])) {
						debug_log(__METHOD__." $ar_terminoID_by_modelo_name is empty for tipo, ".to_string($ar_terminoID_by_modelo_name), logger::DEBUG);
					}
					$calculated_section_tipo = $ar_terminoID_by_modelo_name[0];
					$real_section 			 = section::get_section_real_tipo_static($section_tipo);
					$is_real 				 = $real_section===$section_tipo ? true : false;
					if ( $is_real && $section_tipo!=$calculated_section_tipo && $modo!=='search' && SHOW_DEBUG===true) {
						#dump(debug_backtrace(), ' debug_backtrace '.to_string());
						#throw new Exception("Error Processing Request. Current component ($tipo) is not children of received section_tipo: $section_tipo.<br> Real section_tipo is: $real_section and calculated_section_tipo: $calculated_section_tipo ", 1);
					}
				}				
			}
		
    		if (is_array($parent)) {
    			dump(debug_backtrace(), 'debug_backtrace()');
				dump($parent, ' parent');
			}
    	}//end if(SHOW_DEBUG===true)
    	
    	# Direct construct without cache instance
		# Use this config in imports
		if ($cache===false) {
			return new $component_name($tipo, $parent, $modo, $lang, $section_tipo);
		}
    	
		static $ar_component_instances;

		# key for cache
    	$key = $tipo .'_'. $section_tipo .'_'. $parent .'_'. $lang;

    	$max_cache_instances = 160; // 500
		$cache_slice_on 	 = 40; // 200 //$max_cache_instances/2;  	

    	# OVERLOAD : If ar_component_instances > 99 , not add current element to cache to avoid overload
    	if ( isset($ar_component_instances) && count($ar_component_instances)>$max_cache_instances ) {
    		$ar_component_instances = array_slice($ar_component_instances, $cache_slice_on, null, true);
    		if(SHOW_DEBUG===true) {
    			#debug_log(__METHOD__." Overload components prevent. Unset first cache item [$key]");
    		}
    	}
  
    	# unset($ar_component_instances);
    	# FIND CURRENT INSTANCE IN CACHE	
    	if ( !isset($ar_component_instances) || !array_key_exists($key, $ar_component_instances) ) {

    		if (empty($component_name)) {
				$component_name = RecordObj_dd::get_modelo_name_by_tipo($tipo, true);
					#dump($current_component_name, ' current_component_name');
			}			
			if (strpos($component_name, 'component_')===false) {
				if(SHOW_DEBUG===true) {
					throw new Exception("Error Processing Request. Ilegal component: '$component_name' on ".__METHOD__, 1);					
				}
				return null;
			}
	
			# __CONSTRUCT : Store new component in static array var
			$ar_component_instances[$key] = new $component_name($tipo, $parent, $modo, $lang, $section_tipo);
    		   		
    	}else{
    		
    		# Change modo if need
    		if ($ar_component_instances[$key]->get_modo()!==$modo) {
    			$ar_component_instances[$key]->set_modo($modo);
    		} 		   			
    	}
    	

    	if(SHOW_DEBUG===true) {
    		# Verify 'component_name' and 'tipo' are correct
    		$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
    		if (!empty($component_name) && $component_name!= $modelo_name) {
    			throw new Exception("Error Processing Request. Inconsistency detected with get_instance 'tipo' ($tipo). 
    								 Expected model is ($modelo_name) and received model is ($component_name)", 1);
    		}
    		#if (isset($ar_component_instances)) dump( array_keys($ar_component_instances), ' ar_component_instances');
    		#debug_log('ar_component_instances: '.count($ar_component_instances));
    			#dump($key," key");
    	}

    	return $ar_component_instances[$key];
    }//end get_instance



    # __CONSTRUCT
	public function __construct($tipo=NULL, $parent=NULL, $modo='edit', $lang=DEDALO_DATA_LANG, $section_tipo=null) {

		# TIPO : Test valid tipo					
		if ( empty($tipo) ) {
			$msg = "Component common: valid 'tipo' value is mandatory!";
			$GLOBALS['log_messages'][] = $msg;
			throw new Exception($msg, 1);
		}elseif ($tipo==='dummy') {
			throw new Exception("Error dummy caller!!", 1);	
		}
		$this->tipo = $tipo;

		# PARENT : Test valid parent
		#if ( $parent === NULL ) {			
		#	$msg = "Component common: valid 'parent' value is mandatory! ";
		#	throw new Exception($msg, 1);			
		#}
		$this->parent = $parent;

		# MODO
		if ( empty($modo) ) {
			$modo = 'edit';
		}
		$this->modo = $modo;
		if ($this->modo==='print') {
			$this->print_options = new stdClass();
		}

		# LANG : Test valid lang		
		if(isset($this->lang)) {
			# LANG : Overwrite var '$lang' with previous component declatarion of '$this->lang'
			$lang = $this->lang;
		}elseif ( empty($lang) ) {
			$msg = __METHOD__.' Valid \'lang\' value is mandatory! Default DEDALO_DATA_LANG ('.DEDALO_DATA_LANG.') is used';
			$GLOBALS['log_messages'][] = $msg;
			trigger_error($msg);
			$lang = DEDALO_DATA_LANG;
		}
		$this->lang = $lang;
		
		# SECTION_TIPO
		# SECTION_TIPO : OPTIONAL (if empty, section_tipo is calculated from: 1. page globals, 2. structure -only useful for real sections-)
		if (empty($section_tipo)) {
			$section_tipo = component_common::resolve_section_tipo($tipo);
			debug_log(__METHOD__." Calculated section tipo from tipo ($tipo) !!!!!! Fix ASAP ".to_string(), logger::ERROR);
		}
		$this->section_tipo = $section_tipo;


		# STRUCTURE DATA : common::load_structure_data()
		# Fijamos el tipo recibido y cargamos la estructura previamente para despejar si este tipo es traducible o no
		# y fijar de nuevo el lenguaje en caso de no ser traducible		
		parent::load_structure_data();

		# LANG : Check lang
		# Establecemos el lenguaje preliminar (aunque todavía no están cargados lo datos de matrix, ya tenemos la información de si es o no traducible
		# a partir de la carga de la estructura)
		if ($this->traducible==='no') {
			$this->lang = DEDALO_DATA_NOLAN;
		}		
				
		$this->ar_tools_obj		= false;
		$this->debugger			= "tipo:$this->tipo - norden:$this->norden - modo:$this->modo - parent:$this->parent";

		/* OLD WAY
		# MATRIX DATA : Load matrix data 
		$this->load_component_dato();
	
		# PROPIEDADES: DATO_DEFAULT : Default data try
		# If 'propiedades:dato_default' exists, use this value as initial value, save component data and reload component data
		if( ($this->dato===null || $this->dato==='' || $this->dato===false)
			&& ( $this->modo==='edit' ) // && empty($this->parent) (Now always have parent!)
			) {
			
			$propiedades = $this->RecordObj_dd->get_propiedades();
			if(!empty($propiedades)) {

				$propiedades = json_handler::decode($propiedades);
					#dump($propiedades->dato_default,'$propiedades->dato_default');
				if(isset($propiedades->dato_default)) {

					#dump($propiedades->dato_default,'$propiedades->dato_default intentando salvar desde tipo:'.$this->tipo." parent:".$this->parent);
					$dato_default = $propiedades->dato_default;

					# Method Used
					if(isset($propiedades->dato_default->method)) {
						$dato_default = $this->get_method( (string)$propiedades->dato_default->method );
					}

					$this->set_dato($dato_default);
					$this->id 	= $this->Save();

					# INFO LOG
					if(SHOW_DEBUG===true) {
						$msg = " Created ".get_called_class()." $this->label id:$this->id, tipo:$tipo, section_tipo:$section_tipo, modo:$modo with default data from 'propiedades': ".to_string($propiedades->dato_default);
						debug_log(__METHOD__.$msg);
					}

					$this->load_component_dato();
				}
			}
		}//end if
		*/

		# SET_DATO_DEFAULT (new way 28-10-2016)
		if ($this->modo==='edit') {
			$this->set_dato_default();
		}				
	}//end __construct



	/**
	* SET_DATO_DEFAULT
	* Set dato default when propiedades->dato_default exists and current component dato is empty
	* propiedades are loaded always (structure data) at begining of build component. Because this
	* is more fast verify if is set 'dato_default' and not load component data always as before 
	* @return bool true
	*/
	private function set_dato_default() {
		
		# propiedades is object or null
		$propiedades = $this->get_propiedades();
	
		if(isset($propiedades->dato_default)) {

			# MATRIX DATA : Load matrix data
			$this->load_component_dato();

			$dato = $this->dato;
			if (empty($dato)) {
		
				#dump($propiedades->dato_default,'$propiedades->dato_default intentando salvar desde tipo:'.$this->tipo." parent:".$this->parent);
				$dato_default = $propiedades->dato_default;

				# Method Used
				if(isset($propiedades->dato_default->method)) {
					$dato_default = $this->get_method( (string)$propiedades->dato_default->method );
				}

				$this->set_dato($dato_default);
				$this->id 	= $this->Save();

				# INFO LOG
				if(SHOW_DEBUG===true) {
					$msg = " Created ".get_called_class()." \"$this->label\" id:$this->id, tipo:$this->tipo, section_tipo:$this->section_tipo, modo:$this->modo with default data from 'propiedades': ".json_encode($propiedades->dato_default);
					debug_log(__METHOD__.$msg);
				}

				# MATRIX DATA : Reload matrix data again
				$this->load_component_dato();
			}
		}

		return true;
	}//end set_dato_default


	# define tipo
	protected function define_tipo($tipo) {	$this->tipo = $tipo; }
	# define lang
	protected function define_lang($lang) {	$this->lang = $lang; }
	# define modo
	protected function define_modo($modo) {	$this->modo = $modo; }



	/**
	* SET_DATO
	* @return 
	*/
	public function set_dato($dato) {		

		parent::set_dato($dato);

		# Fix this component as data loaded to avoid overwite current dato setted with database dato
		# Set as loaded
		$this->bl_loaded_matrix_data = true;
	}//end set_dato


	
	/**
	* GET_DATO
	*/
	protected function get_dato() {

		if(isset($this->dato_resolved)) {			
			return $this->dato_resolved;
		}
		
		/*
		#
		# IS TEMP CASE
		# Sometimes we need use component as temporal element without save real data to database. Is this case
		# data is saved to session as temporal data
		if (isset($this->is_temp) && $this->is_temp===true) {
			$temp_data_uid = $this->tipo.'_'.$this->parent.'_'.$this->lang.'_'.$this->section_tipo;
			if (isset($_SESSION['dedalo4']['component_temp_data'][$temp_data_uid])) {
				$this->dato = $_SESSION['dedalo4']['component_temp_data'][$temp_data_uid];
			}else{
				$this->dato = null;
			}

		}else{

			# MATRIX DATA : Load matrix data 
			$this->load_component_dato();	
		}
		*/
				
		# MATRIX DATA : Load matrix data 
		$this->load_component_dato();

		return $this->dato; # <- Se aplicará directamente el fallback de idioma para el modo list
	}//end get_dato



	# GET_DATO_UNCHANGED
	# Recover component var 'dato' without change type or other custom component changes
	# This is a easy way to access internal protected var 'dato' from out of component (like section::save_component_dato) 
	public function get_dato_unchanged() {
		return $this->dato;
	}//end get_dato_unchanged

	# DATO REAL
	/*
	public function get_dato_real() {
		$dato_real = $this->get_dato();

		return $dato_real;
	}//end get_dato_real
	*/



	/**
	* LOAD MATRIX DATA
	* Get data once from matrix about parent, dato
	*/
	protected function load_component_dato() {
		
		if( empty($this->parent) || $this->modo==='dummy' || $this->modo==='search') {

			# Experimental (devolvemos como que ya se ha intentado cargar, aunque sin id)
			#$this->bl_loaded_matrix_data = true;
			return null;
		}

		
		if( $this->bl_loaded_matrix_data!==true ) {
			# Experimental (si ya se ha intentado cargar pero con sin id, y ahora se hace con id, lo volvemos a intentar)
			#if( !$this->bl_loaded_matrix_data || ($this->bl_loaded_matrix_data && intval($this->id)<1) ) {
							
				if (empty($this->section_tipo)) {
					if(SHOW_DEBUG===true) {
						$msg = " Error Processing Request. section tipo not found for component $this->tipo";
						#throw new Exception("$msg", 1);
						debug_log(__METHOD__.$msg);									
					}
				}					
				$section = section::get_instance($this->parent, $this->section_tipo);

			
			# Fix dato
			# El lang_fallback, lo haremos directamente en la extracción del dato del componente en la sección y sólo para el modo list.
			$lang_fallback=false;
			if ($this->modo==='list') {
				$lang_fallback=true;
			}
			$this->dato = $section->get_component_dato($this->tipo, $this->lang, $lang_fallback);
				#dump($this->dato,"this->dato"); #die();
			
			# Set as loaded
			$this->bl_loaded_matrix_data = true;
		}
	}//end load_component_dato



	/**
	* RESOLVE_SECTION_TIPO
	* @param string $tipo Component tipo
	* @return string $section_tipo
	*/
	public static function resolve_section_tipo($tipo) {
		
		if (defined('SECTION_TIPO')) {
			# 1 get from page globals
			$section_tipo = SECTION_TIPO;
		}else{
			# 2 calculate from structure -only useful for real sections-
			$section_tipo = component_common::get_section_tipo_from_component_tipo($tipo);
			if(SHOW_DEBUG===true) {
				debug_log(__METHOD__." WARNING: calculate_section_tipo:$section_tipo from structure for component $tipo Called by:".debug_backtrace()[0]['function']);
				#dump(debug_backtrace()," debug_backtrace");
				if ($section_tipo===DEDALO_SECTION_USERS_TIPO || $section_tipo===DEDALO_SECTION_PROJECTS_TIPO) {
					debug_log(__METHOD__." WARNING SECTION BÁSICA!! Called by:".debug_backtrace()[0]['function'], 1);
				}
			}
		}

		return $section_tipo;		
	}//end resolve_section_tipo



	/**
	* FIX_LANGUAGE_NOLAN
	*/
	protected function fix_language_nolan() {

		$this->expected_lang = DEDALO_DATA_NOLAN;
		return null;

		#dump($this," fix_language_nolan ");
		# Fix lang always
		$this->lang = DEDALO_DATA_NOLAN;
		# Fix traducible
		$this->traducible = 'no';
	}//end fix_language_nolan



	/**
	* GET_COMPONENT_CACHE_KEY_NAME
	*/
	public function get_component_cache_key_name() {
		return DEDALO_DATABASE_CONN.'_component_get_html_'.$this->get_identificador_unico();
	}//end get_component_cache_key_name



	/**
	* GET HTML CODE . RETURN INCLUDE FILE __CLASS__.PHP
	* @return $html
	*	Get standar path file "DEDALO_LIB_BASE_PATH .'/'. $class_name .'/'. $class_name .'.php'" (ob_start)
	*	and return rendered html code
	*/
	public function get_html() {

		if(SHOW_DEBUG===true) {
			$this->start_time= microtime(1);
			$start_time 	 = start_time();
			global$TIMER;$TIMER[__METHOD__.'_'.get_called_class().'_IN_'.$this->tipo.'_'.microtime(1)]=microtime(1);
		}

			#
			# DEDALO_CACHE_MANAGER : Read from cache if var exists ##
			if(DEDALO_CACHE_MANAGER===true && CACHE_COMPONENTS===true) {
				# No guardamos los componentes de actividad en cache
				if (!in_array($this->tipo, logger_backend_activity::$ar_elements_activity_tipo)) {
					$cache_key_name = $this->get_component_cache_key_name();					
					if (cache::exists($cache_key_name)) {
						#dump($cache_key_name,"COMPONENT SHOW FROM CACHE");
						#debug_log("INFO: readed data from component cache key: $cache_key_name");
						# Notify for load component js/css
						# Ojo! los portales auto-notifican a sus componentes, (notify_load_lib_element_tipo_of_portal) por lo que 
						# haría falta una forma de ejecutar esto aun cuando se usan desde cache..
						//common::notify_load_lib_element_tipo($modelo_name, $modo);
						return cache::get($cache_key_name);
					}
				}					
			}
			# /DEDALO_CACHE_MANAGER #################################
		

		#
		# HTML BUFFER
		ob_start();
		include ( DEDALO_LIB_BASE_PATH .'/'. get_called_class() .'/'. get_called_class() .'.php' );
		$html =  ob_get_clean();


			#
			# DEDALO_CACHE_MANAGER : Set cache var #################
			if(DEDALO_CACHE_MANAGER===true && CACHE_COMPONENTS===true) {
				#if(strpos($cache_key_name, 'list')!=false) 
				cache::set($cache_key_name, $html);
			}
			# /DEDALO_CACHE_MANAGER #################################
			


		if(SHOW_DEBUG===true) {
			global$TIMER;$TIMER[__METHOD__.'_'.get_called_class().'_OUT_'.$this->tipo.'_'.microtime(1)]=microtime(1);
			$total=round(microtime(1)-$this->start_time,3)*1000;	
			if ($total>0.080) {
				#dump($total, ' total ++ '.$this->tipo .' '. get_called_class() );
			}
			if($this->modo==='edit')
			$html = str_lreplace('</div>', "<span class=\"debug_info debug_component_total_time\">$total ms $this->modo</span></div>", $html);
		}		

		return $html;
	}//end get_html



	/**
	* SAVE
	* Save component data in matrix using parent section
	* Verify all necessary vars to save and call section 'save_component_dato($this)'
	* @see section->save_component_dato($this)
	* @return int $section_matrix_id
	*/
	public function Save() {

		# MAIN VARS	
		$section_tipo	= $this->get_section_tipo();
		$parent 		= $this->get_parent();
		$tipo 			= $this->get_tipo();
		$lang 			= $this->get_lang();
		if (empty($lang)) {
			$lang = DEDALO_DATA_LANG;
		}
		/* Innecesario ???
		# Si sabemos que el elemento no es traducible, fijamos su 'lang' en 'lg-nolan' (DEDALO_DATA_NOLAN)
		if ($this->traducible=='no') {
			$lang = DEDALO_DATA_NOLAN;
		}
		*/
		

		# PARENT : Verify parent
		if(abs($parent)<1 && strpos($parent, DEDALO_SECTION_ID_TEMP)===false) {
			if(SHOW_DEBUG===true) {
				dump($this, "this section_tipo:$section_tipo - parent:$parent - tipo:$tipo - lang:$lang");
				throw new Exception("Error Processing Request. Inconsistency detected: component trying to save without parent ($parent) ", 1);;
			}			
			die("Error. Save component data is stopped. Inconsistency detected. Contact with your administrator ASAP");		
		}

		# Verify component minumun vars before save
		if( empty($parent) || empty($tipo) || empty($lang) )
			throw new Exception("Save: More data are needed!  section_tipo:$section_tipo, parent:$parent, tipo,$tipo, lang,$lang", 1);
		

		# DATO
		$dato = $this->dato;
			#dump($dato,"dato en save del component common");

	
		#
		# IS TEMP CASE
		# Sometimes we need use component as temporal element without save real data to database. Is this case
		# data is saved to session as temporal data
		/*
		if (isset($this->is_temp) && $this->is_temp===true) {
			$temp_data_uid = $tipo.'_'.$parent.'_'.$lang.'_'.$section_tipo;
			$_SESSION['dedalo4']['component_temp_data'][$temp_data_uid] = $dato ;
			if(SHOW_DEBUG===true) {
				debug_log("INFO: IS_TEMP: saved dato from component $temp_data_uid");
			}
			dump($dato, ' dato ++ '.to_string($temp_data_uid));
			return false;
		}
		*/
		

		# SECTION : Preparamos la sección que será la que se encargue de salvar el dato del componente
		$section 	= section::get_instance($parent, $section_tipo);
		$section_id = $section->save_component_dato($this);
			#dump($section_id, ' section_id');

		# ID : Check valid id returned
		if (abs($section_id)<1 && strpos($section_id, DEDALO_SECTION_ID_TEMP)===false) {
			throw new Exception("Save: received id ($section_id) not valid!", 1);
		}
		#dump($section_id,"section_id");		


		# ACTIVITY
		$this->save_activity();


		# DEDALO_CACHE_MANAGER : Delete cache of current component html	
		if(DEDALO_CACHE_MANAGER===true && CACHE_COMPONENTS===true) {
			# No borramos la chache de los componentes de activity ya que no son editables y por tanto no cambian nunca
			if (!in_array($this->tipo, logger_backend_activity::$ar_elements_activity_tipo)) {				
				# Delete all caches of current tipo
				cache::del_contains($this->tipo);
			}
			#debug_log(__METHOD__." Saved dato of component $this->tipo ($this->label) ");
		}

		# RETURN MATRIX ID
		return (int)$section_id;
	}//end Save



	/**
	* SAVE_ACTIVITY
	* @return 
	*/
	public function save_activity() {

		# ACTIVITY
		# Prevent infinite loop saving self
		if (!in_array($this->tipo, logger_backend_activity::$ar_elements_activity_tipo)) {
			try {
				# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
				$matrix_table 	= common::get_matrix_table_from_tipo($this->section_tipo);
				logger::$obj['activity']->log_message(
					'SAVE',
					logger::INFO,
					$this->tipo,
					null,
					array(	"msg"			=> "Saved component data",							
							"tipo"			=> $this->tipo,
							"parent"		=> $this->parent,
							"lang"			=> $this->lang,
							"top_id"		=> (TOP_ID ? TOP_ID : $this->parent),
							"top_tipo"		=> (TOP_TIPO ? TOP_TIPO : $this->section_tipo),
							"component_name"=> get_called_class(),
							"table"			=> $matrix_table,
							"section_tipo"	=> $this->section_tipo
						 )
				);
			} catch (Exception $e) {
			    if(SHOW_DEBUG===true) {
			    	$msg = 'Exception: ' . $e->getMessage();
			    	trigger_error($msg);
			    }
			}//end try catch
		}//end if (!in_array($tipo, logger_backend_activity::$ar_elements_activity_tipo))
	}//end save_activity



	/**
	* GENERATE_JS
	* @see component_radio_button.php
	*/
	/*
	array('js' => array(
					array('trigger'	=> array(
											"1" => array('component_common.show(\'dd658\')'),
											"3" => array('component_common.hide(\'dd658\')')
											),
				),
	);
     */
	public function generate_js() {

		$propiedades = $this->RecordObj_dd->get_propiedades();
		if(empty($propiedades)) return null;

		// JSON DECODE
		$propiedades = $this->get_propiedades();
			#dump($propiedades->js,'$propiedades->js');

		if(isset($propiedades->js)) {

			$propiedades_js = json_encode($propiedades->js);
			$wrapper_id 	= 'wrapper_'.$this->get_identificador_unico();

			$js_code  ='';
			$js_code .="\n<script>";
			$js_code .="component_common.parse_propiedades_js($propiedades_js,'$wrapper_id')";
			$js_code .="</script>\n";
				#dump($js_code,'js_code');

			return $js_code;
		}//end if(isset($propiedades->js))
	}//end generate_js



	public function generate_js_OLD() {

		$js_code='';
		$identificador_unico	= $this->get_identificador_unico();
		#$parent 				= $this->get_parent();

		$propiedades = $this->RecordObj_dd->get_propiedades();
		if(!empty($propiedades)) {

			$propiedades = json_decode($propiedades);
				#dump($propiedades->js,'$propiedades->js');

			if(isset($propiedades->js)) {

				$js_code .="\n<script>\n$(document).ready(function() {";

				#$js_code .= "\n  console.log( $('#wrapper_$identificador_unico') ); ";

				foreach ($propiedades->js as $key => $ar_values) {

					# INTERPRTETACIÓN JSON
					$ar_event = $ar_values->event;
					$ar_valor = $ar_values->valor;
						#dump($ar_event,'$ar_event');


					foreach ($ar_event as $current_event) {

						#$js_code .= " var wrap_obj = $('#wrapper_{$identificador_unico}')";
						#$js_code .= "\n  $('document.body').on('$current_event', '#wrapper_{$identificador_unico}', function(event) {";
						$js_code .= "\n  $('#wrapper_{$identificador_unico}').on('$current_event', function(event) {";

						#$js_code .= "\n var wrapper = document.getElementById('wrapper_{$identificador_unico}')";
						#$js_code .= "\n wrapper.$current_event = function(event){";

						#$js_code .= "\n	 alert( $(this).data('dato')  )";
						$js_code .= "\n  console.log( event.target.value )";
						#$js_code .= "\n  console.log( wrapper.dataset.dato )";
						#$js_code .= "\n  console.log( event.currentTarget.dataset.dato )";
						#$js_code .= "\n  console.log( event.currentTarget.attributes['data-dato'] )";
						#$js_code .= "\n  console.log( event.delegateTarget.attributes['data-dato'].nodeValue )";

						#$js_code .= "\n  switch ( $(this).data('dato') ) {";
						$js_code .= "\n  switch ( event.target.value ) {";
						#$js_code .= "\n  switch ( event.srcElement.value ) {";

						foreach ($ar_valor as $valor => $ar_accion) {

							$js_code .= "\n   case '$valor' : ";	#$( "input[name*='man']" ).val( "has man in it!" );

							#foreach ($ar_accion as $accion) {
								foreach ($ar_accion as $current_accion) {

									if(strpos($current_accion, 'contains')) {

									}

									#$current_accion = str_replace('$parent', $parent, $current_accion);

									$js_code .= "component_common.". $current_accion ." ;";
									#$js_code .= $current_accion ." ;";
								}
							#}

							$js_code .= "\n   break;";
						}
						$js_code .= "\n  }";

						$js_code .= "\n });";

					}//end foreach ($ar_event as $current_event) {

				}//end foreach ($propiedades->js as $key => $ar_values)

				$js_code .="\n});\n</script>\n";

			}//end if(isset($propiedades->js))

			#dump($js_code,'js_code');
		}

		return $js_code;
	}//end generate_js_OLD


	
	/**
	* FILTRO
	* Consulta si el parent (la sección a que pertenece) está autorizada para el usuario actual
	* @return bool(true/false)
	* Devuelve false si NO es autorizado
	*/
	function get_filter_authorized_record() {
		# NOTA : Obviamos esta comprobación en la nueva estructura de sección (json). Evaluar si 
		# realmente es necesaria cuando sea posible.				
		return true;	
	}//end get_filter_authorized_record
	


	# GET_EJEMPLO
	protected function get_ejemplo() {
		return $this->debugger;
		if(empty($this->ejemplo)) return "example: 'MO-15-5620-GANDIA'";
		return parent::get_ejemplo();
	}//end get_ejemplo



	/**
	* GET_REQUIRED
	*/
	private function get_required() {
		if($this->required==='si') {
			return false;
		}else{
			return true;
		}
	}//end get_required



	/**
	* GET_AR_TOOLS_OBJ
	*/
	public function get_ar_tools_obj() {
		if($this->ar_tools_obj===false) {
			$this->load_tools();
		}
		#dump(null,'called $this->ar_tools_obj');
		#dump($this->ar_tools_obj," this->ar_tools_obj");
		return $this->ar_tools_obj;
	}//end get_ar_tools_obj



	/**
	* LOAD SPECIFIC TOOL
	* Note: Used in class.inspector to load relation tool
	* @param string $tool_name
	* @return object | null $tool_object
	*/
	public function load_specific_tool($tool_name) {
		
		$tool_obj = null;

		if ($tool_name==='tool_relation') {
			if(SHOW_DEBUG===true) {
				#debug_log(__METHOD__." DESACTIVA LA CARGA DE TOOL RELATION ".__METHOD__);				
			}
			return $tool_obj;
		}

		$authorized_tool = component_security_tools::is_authorized_tool_for_logged_user($tool_name);
		if ($authorized_tool===true) {
			require_once(DEDALO_LIB_BASE_PATH . '/tools/'.$tool_name.'/class.'.$tool_name.'.php');
			$tool_obj = new $tool_name($this);
		}

		return $tool_obj;
	}//end load_specific_tool



	/**
	* GET_AR_TOOLS_NAME
	* @return array $ar_tools_name
	*/
	protected function get_ar_tools_name() {
		# Default tools
		$ar_tools_name = $this->ar_tools_name;

		$propiedades = $this->get_propiedades();
		if (isset($propiedades->ar_tools_name)) {
			#dump($propiedades->ar_tools_name, ' propiedades ++ '.to_string());
			foreach ((array)$propiedades->ar_tools_name as $current_name => $obj_tool) {	
				#dump( $obj_tool, ' obj_tool ++ '.to_string($current_name));
				$ar_tools_name[] = $current_name;
			}
			if(SHOW_DEBUG===true) {
				#dump($propiedades->ar_tools_name, ' ar_tools_name ADDED TOOLS FROM PROPIEDADES . total: '.to_string( $ar_tools_name ));
			}
		}
			
		return (array)$ar_tools_name;
	}//end get_ar_tools_name




	/**
	* LOAD TOOLS
	*/
	public function load_tools() {

		if(strpos($this->modo, 'edit')===false ){
			if(SHOW_DEBUG===true) {
				#trigger_error("Innecesario cargar los tools aquí. Modo: $this->modo");
			}
			return null;
		} 

		# Si no estamos logeados, no es necesario cargar los tools
		if(login::is_logged()!==true) return null;
	
		# Load all tools of current component
		$ar_tools_name = $this->get_ar_tools_name();
			#dump($ar_tools_name,'ar_tools_name PRE AUTH');

		$traducible = $this->RecordObj_dd->get_traducible();
			#dump($traducible,"traducible");
		
		if ($traducible==='no' || $this->lang===DEDALO_DATA_NOLAN) {
			$key = array_search('tool_lang',$ar_tools_name);
			if($key!==false){
			    unset($ar_tools_name[$key]);			    	
			}
		}
		#dump($ar_tools_name,'ar_tools_name PRE AUTH');

		# Create obj tools array
		if( is_array($ar_tools_name)) foreach ($ar_tools_name as $tool_name) {

			$authorized_tool = component_security_tools::is_authorized_tool_for_logged_user($tool_name);
				#dump($authorized_tool," authorized_tool");

			if ($authorized_tool===true) {				

				# INDEXATION TOOL CASE : When current tool have 'indexation' name, test thesaurus permissions for avoid inconsistencies
				if (strpos($tool_name, 'indexation')!==false) {					
					$ts_permissions = (int)security::get_security_permissions(DEDALO_TESAURO_TIPO, DEDALO_TESAURO_TIPO);
					if ($ts_permissions<1) continue;	# Skip this tool
				}				

				# Authorized tools names
				if (!in_array($tool_name, (array)$this->ar_authorized_tool_name)) {
					$this->ar_authorized_tool_name[] = $tool_name;
				}				
			}
		}
		
		return $this->ar_authorized_tool_name;
	}//end load_tools
	


	/**
	* GET VALOR
	* LIST:
	* GET VALUE . DEFAULT IS GET DATO . OVERWRITE IN EVERY DIFFERENT SPECIFIC COMPONENT
	*/
	public function get_valor() {

		$valor = self::get_dato();

		if(SHOW_DEBUG===true) {
			if (!is_null($valor) && !is_string($valor) && !is_numeric($valor)) {
				dump(debug_backtrace(), 'get_valor debug_backtrace() ++ '.to_string());
				debug_log(__METHOD__." WARNING: CURRENT 'valor' in $this->tipo is NOT valid string. Type is:\"".gettype($valor).'" - valor:'.to_string($valor), logger::WARNING);
			}
		}
			
		if(!is_array($valor)) return $valor;

		return "<em>No string value</em>";
	}//end get_valor



	/**
	* GET_VALOR_EXPORT
	* Return component value sended to export data
	* @return string $valor
	*/
	public function get_valor_export( $valor=null, $lang=DEDALO_DATA_LANG, $quotes, $add_id ) {
		
		if (is_null($valor)) {
			$valor = $this->get_valor($lang);
		}

		if(SHOW_DEBUG===true) {
			#$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($this->tipo,true);
			#return "COMMON[$modelo_name]: ".to_string($valor);
		}

		return to_string($valor);
	}//end get_valor_export	



	/**
	* DATO IN DEFAULT LANG
	*/
	protected function get_dato_default_lang() {

		if (empty($this->parent)) {
			trigger_error("Sorry. Few vars on get_dato_default_lang");
			return false;
		}

		if ($this->lang === DEDALO_DATA_LANG_DEFAULT) {
			
			$dato = $this->get_dato();

		}else{

			$parent 		= $this->get_parent();
			$tipo			= $this->get_tipo();
			$section_tipo 	= $this->get_section_tipo();	

			$current_component_name	= get_class($this);
			$component_obj			= component_common::get_instance($current_component_name, $tipo, $parent, 'edit', DEDALO_DATA_LANG_DEFAULT, $section_tipo);
			$dato					= $component_obj->get_dato();
		}		

		return $dato;
	}//end get_dato_default_lang



	/**
	* GET_DATO_NO_TRADUCIBLE
	* Despeja el único dato de este componente.
	* Si hay mas de 1 generará un error de consistencia
	* @see self::get_dato()
	*/
	protected function get_dato_no_traducible() {
		trigger_error("En proceso : get_dato_no_traducible");

		$parent 		= self::get_parent();
		$tipo			= self::get_tipo();
		$section_tipo	= self::get_section_tipo();

		if (empty($parent) || empty($tipo)) {
			throw new Exception("Few vars on get_dato_default_lang", 1);
		}

		# Búsqueda
		$arguments=array();
		$arguments['parent']= $parent;
		$arguments['tipo'] 	= $tipo;
		$matrix_table 		= common::get_matrix_table_from_tipo($section_tipo);
		$RecordObj_matrix	= new RecordObj_matrix($matrix_table,NULL);
		$ar_id				= $RecordObj_matrix->search($arguments);

		if(empty($ar_id)) {

			$dato 				= NULL;

		}else if(count($ar_id)>1) {

			if (SHOW_DEBUG===true) dump($ar_id,'$ar_id');
			throw new Exception("Error: Inconsistency: More than one record founded!", 1);

		}else{

			$current_id			= $ar_id[0];

			# Despejamos el dato
			$current_class_name	= get_class($this);
			$component_obj		= component_common::get_instance($current_class_name, $tipo, $parent, 'edit', $lang=DEDALO_DATA_NOLAN, $this->section_tipo);	#($id=NULL, $tipo=NULL, $modo='edit', $parent=NULL, $lang=DEDALO_DATA_LANG)
			$lang 				= $component_obj->get_lang();
			$dato				= $component_obj->get_dato();

			if ($lang !== DEDALO_DATA_NOLAN) {
				trigger_error("Error. Incorrect lang ($lang) for current component ($current_class_name - $tipo - $current_id). Expected  ".DEDALO_DATA_NOLAN);
			}
		}

		return $dato ;
	}//end get_dato_no_traducible



	/**
	* GET DATO AS STRING
	* Get dato formated as string
	*/
	function get_dato_as_string() {

		$dato = $this->get_dato();
		#return var_export($dato,true);		

		if(is_array($dato)) {
			$string = 'Array: ';
			foreach ($dato as $key => $value) {
				if(is_array($value)) $value = 'array '.implode(', ', $value );
				if (is_string($value)) {
					$string .= $key .':'. $value .', ';
				}				
			}
			if(strlen($string)>2) $string = substr($string, 0,-2);
			return $string;
		}else if (is_object($dato)) {
			#$string = 'Object: ' . get_class($dato); #dump($string ,'$string ');die();
		}else if (is_int($dato)) {
			$string = 'Int: ' . $dato;
		}else if (is_string($dato)) {
			$string = 'Str: ' . $dato;
		}
		return $dato;
	}//end get_dato_as_string



	/**
	* GET_DEFAULT_COMPONENT
	* Devuelve el componente para el lenguaje por defecto del tipo y parent actual
	* Ejemplo: el dato de un componente (traducible) en el idioma actual está vacio (no se ha creado registro en matrix todavía). Cargamos el componente 
	* en el idioma por defecto para poder acceder a las tools de lenguaje (necesitan un id_matrix para cargarse) y mostramos el icono del tool para hacer
	* un traducción automática o hacer notar que existe información en otro idioma (en el principal)
	*/
	protected function get_default_component() {

		if (empty($this->parent)) {
			trigger_error("Sorry. Few vars parent:$parent - tipo:$tipo");
			return false;
		}

		$parent 		= $this->get_parent();
		$tipo			= $this->get_tipo();
		$section_tipo 	= $this->get_section_tipo();

		# No existe registro en este idioma. Buscamos con el idioma de datos por defecto DEDALO_DATA_LANG_DEFAULT

			# SECTION : DIRECT SEARCH
			$arguments=array();
			$arguments["section_id"]= $parent;
			$arguments["datos#>>'{components,$tipo,dato,".DEDALO_DATA_LANG_DEFAULT."}':!="] = 'null';
			
			$matrix_table			= common::get_matrix_table_from_tipo($section_tipo);
			$JSON_RecordObj_matrix	= new JSON_RecordObj_matrix($matrix_table,NULL,$section_tipo);
			$ar_id					= $JSON_RecordObj_matrix->search($arguments);

				#dump($ar_id,"ar_id $tipo - $matrix_table",$arguments);die();
			/* OLD WORLD
			$arguments=array();
			$arguments['parent']= $parent;
			$arguments['tipo'] 	= $tipo;
			$arguments['lang'] 	= DEDALO_DATA_LANG_DEFAULT ;

			$matrix_table 		= common::get_matrix_table_from_tipo($section_tipo);
			$RecordObj_matrix	= new RecordObj_matrix($matrix_table,NULL);
			$ar_id				= $RecordObj_matrix->search($arguments);
				#dump($ar_id,'ar_id '.print_r($arguments,true));
			*/

		# Existe registro matrix para este componente en su idioma principal
		if(!empty($ar_id[0])) {
			$current_id		= $ar_id[0];

			# Despejamos el dato
			$current_class_name	= get_class($this);
			$component_obj		= component_common::get_instance($current_class_name, $tipo, $current_id, 'edit', DEDALO_DATA_LANG_DEFAULT, $this->section_tipo);
			
			return $component_obj;

		# No existe registro en el idioma principal
		}else{

			return null;
		}
	}//end get_default_component



	/**
	* COMPONENT IS RELATIONABLE
	* @return bool(true/false)
	*/
	protected function is_relationable() {

		$component_name 			= get_class($this);
		$ar_components_reationables = array('component_text_area');

		if( in_array($component_name, $ar_components_reationables) )
			return true;

		return false;
	}//end is_relationable



	/**
	* GET MODIFICATION DATE
	*/
	function get_mod_date() {

		$RecordObj_time_machine = $this->get_last_time_machine_obj();						#dump($RecordObj_time_machine );

		if(is_object($RecordObj_time_machine)) {
			return $RecordObj_time_machine->get_timestamp();
		}

		return null;
	}//end get_mod_date



	/**
	* GET MODIFICATED BY USER
	*/
	function get_mod_by_user_name() {

		$RecordObj_time_machine = $this->get_last_time_machine_obj();

		if(is_object($RecordObj_time_machine)) {
			return $RecordObj_time_machine->get_userID();
		}

		return null;
	}//end get_mod_by_user_name



	/**
	* GET_LAST_TIME_MACHINE_OBJ
	* @return object $RecordObj_time_machine
	*/
	function get_last_time_machine_obj() {		
		
		if(empty($this->parent)) return null;

		if (isset($this->RecordObj_time_machine)) {
			return $this->RecordObj_time_machine;
		}

		$arguments=array();
		$arguments['section_id']	= $this->parent;
		$arguments['tipo']			= $this->tipo;
		$arguments['section_tipo']	= $this->section_tipo;
		$arguments['lang']			= $this->lang;
		$arguments['order_by_desc']	= 'timestamp';
		$arguments['sql_limit']		= 1;
		$RecordObj_time_machine		= new RecordObj_time_machine(null);
		$ar_id						= (array)$RecordObj_time_machine->search($arguments);				#dump($ar_id,'ar_id '.to_string($arguments));

		if(count($ar_id)>0) {
			$last_tm_record_id 				= $ar_id[0];
			$this->RecordObj_time_machine	= new RecordObj_time_machine($last_tm_record_id);			#dump($RecordObj_time_machine);

			return $this->RecordObj_time_machine;
		}

		return null;		
	}//end get_last_time_machine_obj



	/**
	* GET_VALOR_FROM_AR_LOCATORS
	* Return resolved string from all values of all locators. Used by component_portal 
	* @param object $request_options 
	* @return object $valor_from_ar_locators {result,info}
	*/
	public function get_valor_from_ar_locators( $request_options ) {

		$start_time = microtime(1);
		$valor_from_ar_locators	= new stdClass();

		#dump($this, ' this');
		$options = new stdClass();
			$options->lang 				= DEDALO_DATA_LANG;
			$options->data_to_be_used 	= 'valor';
			$options->separator_fields 	= ', ';
			$options->separator_rows 	= '<br>';
			$options->separator_fields 	= ', ';
			foreach ($request_options as $key => $value) {
				if (property_exists($options, $key)) $options->$key = $value;
			}

		#
		# LOCATORS (If empty, return '')
		$ar_locators = (array)$this->get_dato();
			#dump($ar_locators, ' ar_locators');
			if (empty($ar_locators)) {
				$valor_from_ar_locators->result='';
				$valor_from_ar_locators->debug='No locators found '.$this->get_tipo();
				return $valor_from_ar_locators;
			}

		#
		# TERMINOS_RELACIONADOS . Obtenemos los terminos relacionados del componente actual	
		$ar_terminos_relacionados = (array)$this->RecordObj_dd->get_relaciones();
			#dump($ar_terminos_relacionados, ' ar_terminos_relacionados');
		
		#
		# FIELDS AND MATRIX_TABLE
		$fields=array();
		foreach ($ar_terminos_relacionados as $key => $ar_value) {
			
			$modelo 	 = key($ar_value);			
			$tipo 		 = $ar_value[$modelo];
			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			if ($modelo_name==='section') {
			#if ($modelo===MODELO_SECTION) { // 
				$section_tipo = $tipo;
				$matrix_table = common::get_matrix_table_from_tipo( $section_tipo );							
			}else{
				$fields[] = $tipo;
			}
		}
		#dump($matrix_table, ' matrix_table ');

		#
		# SELECT : Selector de terminos relacionados en DB
		$strQuery_select='';		
		foreach ($fields as $current_tipo) {						
			# SELECCIÓN EN EL LENGUAJE ACTUAL			
			$RecordObj_dd 	= new RecordObj_dd($current_tipo);
			$current_lang 	= $RecordObj_dd->get_traducible() ==='no' ? DEDALO_DATA_NOLAN : $options->lang;
			$strQuery_select .= "\n datos #>>'{components,$current_tipo,$options->data_to_be_used,$current_lang}' AS $current_tipo";
			if($current_tipo !== end($fields)) $strQuery_select .= ',';
		}
		#dump($strQuery_select, '$strQuery_select');
		
		#
		# WHERE : Filtro de locators en DB
		$strQuery_where='';
		foreach ($ar_locators as $current_locator) {			
			$current_section_id 	= $current_locator->section_id;
			$current_section_tipo 	= $current_locator->section_tipo;
			
			$strQuery_where .= "\n (section_id = $current_section_id AND section_tipo = '$current_section_tipo') OR";			
		}
		if (!empty($strQuery_where)) {
			$strQuery_where = substr($strQuery_where, 0, -2);
		}
		$strQuery_where = '('.$strQuery_where.')';
		#dump($strQuery_where, ' strQuery_where');

		# QUERY
		$strQuery = "-- ".__METHOD__."\n SELECT $strQuery_select FROM $matrix_table WHERE $strQuery_where";
			#dump($strQuery, ' strQuery');

		$result	  = JSON_RecordObj_matrix::search_free($strQuery);
		$ar_final = array();
		while ($rows = pg_fetch_assoc($result)) {
			$string ='';
			foreach ($fields as $current_tipo) {
				$string .= (string)$rows[$current_tipo];
				if($current_tipo !== end($fields)) $string .= $options->separator_fields;		
			}
			$ar_final[] = $string;
		}//end while

		$valor_from_ar_locators->result = implode($options->separator_rows, $ar_final);
		#dump($valor, ' valor');

		if(SHOW_DEBUG===true) {
			$html_info='';
			$limit_time=SLOW_QUERY_MS/100;			
			$total_list_time = round(microtime(1)-$start_time,3);
			$style='';
			if ($total_list_time>$limit_time || $total_list_time>0.020) {
				$style = "color:red";
			}			
			$html_info .= "<div class=\"debug_info ar_list_of_values_debug_info\" style=\"{$style}\" onclick=\"$(this).children('pre').toggle()\"> Time: ";
			$html_info .= $total_list_time;
			$html_info .= "<pre style=\"display:none\"> ".$strQuery ."</pre>";
			$html_info .= "</div>";		
			$valor_from_ar_locators->debug = $html_info;			
			if ($total_list_time>$limit_time) {
				debug_log(__METHOD__.' '.$total_list_time."ms. SLOW QUERY: ".$strQuery);
			}
			#debug_log(__METHOD__.' '.$total_list_time."ms. QUERY: ".$strQuery);
		}//end if(SHOW_DEBUG===true)

		return (object)$valor_from_ar_locators;		
	}//end get_valor_from_ar_locators
	


	/**
	* AR LIST OF VALUES
	* Used by comonent_check_box, comonent_radio_button, comonent_select and comonent_autocomplete
	*
	* @param string $lang default 'DEDALO_DATA_LANG'
	* @param string $id_path default false
	* @param string $referenced_section_tipo 
	* @param string $filter_custom
	*
	* @return object ar_list_of_values {result,info}
	* Format:
	*	Object {
	*		"result": Array (
	*	    		[{"section_id":"9","section_tipo":"dd882"}] => Hombre
	*	    		[{"section_id":"10","section_tipo":"dd882"}] => Mujer
	*				),
	*		"strQuery":"SELECT ....",
	*		"others":"..."
	* 	} parent
	*/
	public function get_ar_list_of_values($lang=DEDALO_DATA_LANG, $id_path=false, $referenced_section_tipo=false, $filter_custom=false, $value_container='valor') {
	
		if(SHOW_DEBUG===true) {
			global$TIMER;$TIMER[__METHOD__.'_IN_'.$this->tipo.'_'.$this->modo.'_'.$this->parent.'_'.microtime(1)]=microtime(1);;
		}

		$use_cache = true; // Default false
		if ($this->modo==='list') {
			$use_cache = true; // Used in section list for speed
		}		

		if(isset($this->ar_list_of_values)) {
			if(SHOW_DEBUG===true) {
				#debug_log(__METHOD__." get_ar_list_of_values already is calculated..");
			}
			return $this->ar_list_of_values;
		}

		static $list_of_values_cache;
	
		$uid = $this->tipo.'_'.$this->modo.'_'.$this->lang.'_'.$referenced_section_tipo.'_'.rawurlencode($filter_custom);	//.'_'.$this->parent
			#dump($uid, ' uid ++ '.to_string());
		if($use_cache===true && isset($list_of_values_cache[$uid])) {
			if(SHOW_DEBUG===true) {
				//debug_log(__METHOD__." +++ Returned get_ar_list_of_values already is calculated in list_of_values_cache.. ($uid)");
				//dump($uid, ' uid :: '.to_string($referenced_section_tipo));
			}
			return $this->ar_list_of_values = $list_of_values_cache[$uid];
		}

	
		#if ($this->modo =='list' && isset($_SESSION['config4']['get_ar_list_of_values'][$uid]) ) {
		#	return $_SESSION['config4']['get_ar_list_of_values'][$uid];
		#}

		$start_time = microtime(1);

		# vars
		$list_of_values	= new stdClass();
		$ar_final 		= array();
		$tipo 			= $this->tipo;
		
		#
		# AR_TERMINOS_RELACIONADOS
		$ar_terminos_relacionados = (array)$this->RecordObj_dd->get_relaciones();
			#dump($ar_terminos_relacionados, ' ar_terminos_relacionados ++ '.to_string());
			if (empty($ar_terminos_relacionados)) {
				#throw new Exception("Error Processing Request. List of values without TR. Please review structure ($tipo)", 1);
				$msg = "Error Processing Request. List of values without TR. Please review structure ($tipo) <br> Nota: esta función NO está acabada. Falta contemplar los casos en que el dato se accede directamente (Ver versión anterior abajo)"; 
				trigger_error($msg);
				$list_of_values->result   = (array)$ar_final;
				$list_of_values->msg 	  = (string)$msg;
				$list_of_values->strQuery = null;

				return $list_of_values;
			}

			$fields 				= array();
			$section_tipo_related 	= $referenced_section_tipo;
			$matrix_table 			= false;
			foreach ($ar_terminos_relacionados as $key => $ar_value) {
				$modelo 	 = key($ar_value);
				$tipo 		 = $ar_value[$modelo];
				$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
				if ($modelo_name==='section' && $section_tipo_related===false) {
				#if ($modelo===MODELO_SECTION && $section_tipo_related===false) {
					$section_tipo_related 	= $tipo;	// Fix section tipo related	
					$matrix_table 			= common::get_matrix_table_from_tipo($section_tipo_related); // Fix matrix table
				}else{
					$fields[] = $tipo;
				}
			}
			$terminoID_valor = reset($fields); // Select first field tipo
			#if (!$section_tipo_related) {					
			#	$section_tipo_related = $this->get_section_tipo_from_component_tipo($terminoID_valor); // // Fix section tipo related
			#}
			if ($matrix_table===false) {
				$matrix_table = common::get_matrix_table_from_tipo($terminoID_valor); // Fix matrix table
			}
			$ar_terminos_relacionados = $fields;

			

		#
		# STRQUERY_SELECT SELECTOR
		$strQuery_select='';
			$strQuery_where='';
			#datos #>'{components}' ? 
			foreach ($ar_terminos_relacionados as $current_tipo) {
				
				/* 
				# SELECCIÓN CON UN LENGUANJE
				$RecordObj_dd 	= new RecordObj_dd($current_tipo);
				$current_lang 	= ($RecordObj_dd->get_traducible() =='no' ? DEDALO_DATA_NOLAN : $lang);
				$strQuery_select .= JSON_RecordObj_matrix::build_pg_select('btree','datos',$current_tipo,'dato',$current_lang);
				*/
				# SELECCIÓN CON TODOS LOS LENGUAJES
				$strQuery_select .= "datos #>>'{components,$current_tipo,$value_container}' AS $current_tipo " ;
				# SELECCIÓN EN EL LENGUAJE ACTUAL (SÓLO PARA ORDENAR)			
				$RecordObj_dd 	= new RecordObj_dd($current_tipo);
				$current_lang 	= $RecordObj_dd->get_traducible()==='no' ? DEDALO_DATA_NOLAN : $lang;
				$strQuery_select .= ", datos #>>'{components,$current_tipo,$value_container,$current_lang}' AS {$current_tipo}_lang " ;

				# WHERE CLAUSE
				#$strQuery_where = "datos #>'{components}' ? '$current_tipo'";
				$strQuery_where = "section_tipo = '$section_tipo_related'";
				if ( $current_tipo !== end($ar_terminos_relacionados) ) {
					$strQuery_where  .=" AND ";
					$strQuery_select .=", \n\t\t\t\t\t";
				}
			}
			#dump($strQuery_select, '$strQuery_select');


		#
		# PROPIEDADES : Filtrado por propiedades (opcional)
			$filter_propiedades='';
			
			#
			# filtered_by_field_value
			if (isset($this->propiedades->filtered_by_field_value)) {
				#trigger_error("Sorry: Working here");
				#dump($this->propiedades->filtered_by_field_value," filtered_by_field_value - $parent_section_tipo - ".TOP_TIPO);
				/*
				ejemplo:
					{
					    "filtered_by_field_value": {
					        "dd508": "ich32"
					    }
					}
				*/							
				# 1 Obtenemos el valor actual del value_component_tipo 
				$value_component_tipo  	 		= reset($this->propiedades->filtered_by_field_value);
				$value_section_tipo 			= $value_component_tipo;	// SOLUCIONAR EL TENER EL DATO DE SECCIÓN EN PROPIEDADES !!!
				$matrix_table_tipo 				= common::get_matrix_table_from_tipo($value_section_tipo);				
				if (!$id_path) {
				$id_path 						= (string)tools::get_id_path(null);
				}
				$ar_id 							= (array)explode(',', $id_path);
				#dump($ar_id,'$ar_id');
				$id_query='';
				$strQuery_select_tipo='';
				$RecordObj_dd_tipo 	= new RecordObj_dd($value_component_tipo);
				$current_lang_tipo 	= ($RecordObj_dd_tipo->get_traducible()==='no' ? DEDALO_DATA_NOLAN : $lang);
				$strQuery_select_tipo .= JSON_RecordObj_matrix::build_pg_select('btree','datos',$value_component_tipo,'dato',$current_lang_tipo);
					#dump($strQuery_select_tipo," strQuery_select_tipo");
				
				foreach ($ar_id as $key => $currrent_id) {
					$ar_locator = (array)explode('.',$currrent_id);
					$current_section_tipo 	= $ar_locator[0];
					$current_section_id 	= $ar_locator[1];
	
					$id_query .= "( section_tipo='$current_section_tipo' AND ";
					$id_query .= "section_id=$current_section_id )";
					if ( $currrent_id !== end($ar_id) ) $id_query .=" OR ";
				}
				$query= "
						SELECT $strQuery_select_tipo
						FROM $matrix_table_tipo
						WHERE
						$id_query AND (
						datos #>'{components}' ? '$value_component_tipo');";
						#AND datos #>>'{section_tipo}' = '$value_component_section_tipo'
				#dump($query,'$query');
				$result			 = JSON_RecordObj_matrix::search_free($query);
				$rows 			 = (array)pg_fetch_assoc($result);
				$filter_locator  = reset($rows);
				#dump($value_component_tipo," value_component_tipo - value_component_section_tipo:$value_component_section_tipo - id_path:$id_path - ".print_r($ar_id,true). " query: $query - parent_id: $parent_id");
											
				#$component  = component_common::get_instance(null, $value_component_tipo, $parent_id, 'edit', DEDALO_DATA_LANG, $value_component_section_tipo);
				if(SHOW_DEBUG===true) {
					debug_log(__METHOD__." Verificar section tipo en la llamada del componente..");
				}
				#$p_value 	= $component->get_dato_unchanged();
				#if(SHOW_DEBUG===true) {
					#dump($p_value,"p_value $value_component_tipo");
				#}
					

				$p_key 			= key($this->propiedades->filtered_by_field_value);
				$RecordObj_dd 	= new RecordObj_dd($p_key);
				$current_lang 	= ($RecordObj_dd->get_traducible()==='no' ? DEDALO_DATA_NOLAN : $lang);
				$filter_propiedades .= "AND ".JSON_RecordObj_matrix::build_pg_filter('gin','datos',$p_key,$current_lang,$filter_locator);
					#dump($filter_propiedades,'$filter_propiedades');
			}//end if (isset($this->propiedades->filtered_by_field_value)) {


			#
			# FILTERED_BY
			# dump( end($this->propiedades->filtered_by) );
			if (isset($this->propiedades->filtered_by)) {
				/*
				ejemplo:
					{"filtered_by": {
					        "rsc90": [{
					            "section_id":"34","section_tipo":"dd914"
					        }]
					    }
					}
				*/
				$end_value = end($this->propiedades->filtered_by);
				foreach($this->propiedades->filtered_by as $p_key => $p_value) {					

					$current_component_tipo = $p_key;
					$current_value  		= $p_value;
					$current_value_flat 	= json_encode($current_value);

					$RecordObj_dd = new RecordObj_dd($current_component_tipo);					
					$current_lang = $RecordObj_dd->get_traducible()==='no' ? DEDALO_DATA_NOLAN : DEDALO_DATA_LANG;

					$filter_propiedades .= "AND datos#>'{components,$current_component_tipo,dato,$current_lang}' @> '$current_value_flat'::jsonb ";
					if ($p_value!==$end_value) $filter_propiedades .=" \n";
					/*
					#$p_value = json_encode($p_value);
					#dump($p_value," p_value:$p_key");
					#$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($p_key);								
					$RecordObj_dd 	= new RecordObj_dd($p_key);
					$current_lang 	= ($RecordObj_dd->get_traducible() =='no' ? DEDALO_DATA_NOLAN : $lang);
					$filter_propiedades .= "AND ".JSON_RecordObj_matrix::build_pg_filter('btree','datos',$p_key,$current_lang,$p_value);					
					#dump($filter_propiedades," filter_propiedades");
					//AND datos#>'{components,rsc90,dato,lg-nolan}' @> '[{"section_tipo": "dd911","section_id": "3"}]'::jsonb
					//AND datos #>'{components,rsc90,dato,lg-nolan}' ?| array['section_id','section_tipo'] '
					if ( $p_value != end($this->propiedades->filtered_by) ) $filter_propiedades .=" \n";
					*/
				}
			}


			#
			# filtered by REFERENCED_SECTION_TIPO (optional)
			if ($referenced_section_tipo!==false) {
				#dump($referenced_section_tipo, ' referenced_section_tipo ++ '.to_string());
				foreach ((array)$referenced_section_tipo as $current_section_tipo) {
					$filter_propiedades .= "\n-- referenced_section_tipo \n AND section_tipo = '$current_section_tipo' ";
				}				
			}
			

		#
		# MAIN QUERY
		$strQuery="
				-- ".__METHOD__."
				SELECT section_id, section_tipo, $strQuery_select
				FROM \"$matrix_table\" WHERE
				$strQuery_where  $filter_propiedades $filter_custom				
				ORDER BY {$terminoID_valor}_lang ASC
				";
		#dump($strQuery,'$strQuery');
		
		$strQuery = sanitize_query($strQuery);
		$result	  = JSON_RecordObj_matrix::search_free($strQuery);

		$ar_final = array();		
		while ($rows = pg_fetch_assoc($result)) {
			
			#dump($rows, ' rows ++ '.to_string($referenced_section_tipo));
			
			$locator  = new locator();
				$locator->set_section_id( $rows['section_id'] );
				$locator->set_section_tipo( $rows['section_tipo'] );
				
			$valor = (string)'';
			/*
			foreach ($ar_terminos_relacionados as $current_tipo) {
				$valor .= $rows[$current_tipo];
				if ( count($ar_terminos_relacionados)>1 && $current_tipo != end($ar_terminos_relacionados) ) $valor .=" ";
			}
			*/

			foreach ($ar_terminos_relacionados as $current_tipo) {

				# ROW format is string json with all langs data like '{"lg-cat": "No", "lg-eng": "No", "lg-eus": null, "lg-fra": null, "lg-spa": "No"}'
				# dump($rows[$current_tipo], '$rows[$current_tipo]');
				$val = json_decode($rows[$current_tipo]);
					#dump($val, ' val ++ '.to_string());
					

				#$RecordObj_dd 	= new RecordObj_dd($current_tipo);
				#$current_lang 	= ($RecordObj_dd->get_traducible() =='no' ? DEDALO_DATA_NOLAN : $lang);
				
				$lang_nolang 	= DEDALO_DATA_NOLAN;
				$lang_current 	= $lang;
				# lang never must be DEDALO_DATA_NOLAN
				if ($lang_current===DEDALO_DATA_NOLAN) {
					$lang_current = DEDALO_DATA_LANG;
				}
				$lang_default 	= DEDALO_DATA_LANG_DEFAULT;	//'lg-spa';

				# dump($val, ' val ++ ' .DEDALO_DATA_LANG.' '.to_string($lang_current));
				# LANG FALLBACK 
				switch (true) {
					# COMPONENT HAS CHANGED TRANSLATABLE / NON TRANSLATABLE AND TWO DATA IS STORED
					case (isset($val->$lang_nolang) && isset($val->$lang_current) && $val->$lang_nolang!==$val->$lang_current):
						debug_log(__METHOD__." component current_tipo:$current_tipo parent:$this->parent referenced_section_tipo:$referenced_section_tipo have double data ($lang_current / $lang_nolang). $lang_current was used, but please review this ASAP to avoid inconsistencies", logger::WARNING);
						# Don't break here

					# SET NOLAN (current component is not translatable)
					case (isset($val->$lang_nolang) && !isset($val->$lang_current)):
						
						# Only string and numbers are supported now
						/*
						if (is_array($val->$lang_nolang) || is_object($val->$lang_nolang)) {
							$val->$lang_nolang = '';
							if(SHOW_DEBUG===true) {
								#dump(debug_backtrace(), 'debug_backtrace()');
							}
							#dump($val->$lang_nolang, 'WARNING: (ar_list_of_values) $val->$lang_nolang expected string instead array (ONLY STRINGS AR SUPPORTED FOR NOW) var - '.$current_tipo);
						}
						*/
						$valor .= to_string($val->$lang_nolang);
						break;
					# SET LANG CURRENT REQUEST (current component is translatable)
					case (isset($val->$lang_current)):
						#if (is_string($val->$lang_current)) {
							if(SHOW_DEBUG===true) {
								#dump($val->$lang_current, ' val->$lang_current ++ '.to_string());;
							}
							$valor .= to_string($val->$lang_current);
						#}						
						break;
					# SET DEFAULT LANG FOR LIST OF VALUES
					case (isset($val->$lang_default)):
						$valor .= component_common::decore_untranslated( to_string($val->$lang_default) );
						break;
					# SET ANY VALUE FOUNDED (first value found)
					default:
						foreach ((array)$val as $key => $c_value) {
							if(!empty($c_value)) {
								$valor .= component_common::decore_untranslated( to_string($c_value) ); break;	// first value found in array of langs
							}
						}//end foreach ($val as $key => $c_value)
						break;
				}//end switch (true) {

				$valor .= ' '; # Add space between component values				
			}

			$ar_final[ json_encode($locator) ] = $valor;			
		}
		#dump($ar_final,'ar_final '.$strQuery);

		# Set object
		$list_of_values->result   = (array)$ar_final;
		$list_of_values->strQuery = (string)$strQuery;
		$list_of_values->msg      = (string)'ok';

		if(SHOW_DEBUG===true) {
			#if (strpos($strQuery, 'mdcat348')!==false) {
				#dump($ar_final,"ar_final en lang:$lang ".print_r($strQuery,true) ." -- tipo: $this->tipo");	#dump($this->tipo," ");
			#}
			$limit_time=SLOW_QUERY_MS/100;
			$html_info='';
			#dump($rows_data,"");
			$total_list_time = round(microtime(1)-$start_time,3);
			$style='';
			if ($total_list_time>$limit_time || $total_list_time>0.020) {
				$style = "color:red";
			}			
			$html_info .= "<div class=\"debug_info ar_list_of_values_debug_info\" style=\"{$style}\" onclick=\"$(this).children('pre').toggle()\"> Time: ";
			$html_info .= $total_list_time;
			$html_info .= "<pre style=\"display:none\"> ".$strQuery ."</pre>";
			$html_info .= "</div>";
			#echo "<div> Time To Generate section list: HTML: ".round(microtime(1)-$start_time,3)."</div>";
			
			$list_of_values->debug = $html_info;
			#if ($this->modo!='edit') {
			#	echo $html_info;
			#}
			#dump($strQuery," strQuery");
			if ($total_list_time>$limit_time) {
				debug_log(__METHOD__.' '.$total_list_time."ms. SLOW QUERY: ".$strQuery);
			}
			#debug_log(__METHOD__." QUERY: $strQuery total_list_time:$total_list_time - uid:$uid");
		}//end if(SHOW_DEBUG===true)

		#if ($this->modo ==='list') {
		#	$_SESSION['config4']['get_ar_list_of_values'][$uid] = $list_of_values;
		#}
		
		# CACHE
		if ($use_cache===true) {
			$list_of_values_cache[$uid] = $list_of_values;
		}

		if(SHOW_DEBUG===true) {
			global$TIMER;$TIMER[__METHOD__.'_OUT_'.$this->tipo.'_'.$this->modo.'_'.$this->parent.'_count_'.count($ar_final).'_'.microtime(1)]=microtime(1);
		}		
		
		# Fix var
		return $this->ar_list_of_values = $list_of_values;	
	}//end get_ar_list_of_values



	/**
	* GET CURRENT RECORD WITH LANG FALLBACK UNIFIED
	* A partir de tipo y parent del objeto recibido despeja cuales de los registros disponibles están en el idioma actual
	* (si son traducibles) y si no lo están busca el equivalente en el idioma de datos por defecto
	* @param $component_obj
	* @return $ar_final
	*	Array tipo => dato (like Array([dd156] => Documentos solamente))
	*/
	public static function get_current_record_with_lang_fallback_unified($component_obj) {

		# vars (need 'traducible', 'parent', 'tipo')
		$traducible 		= $component_obj->get_traducible();
		$parentID 			= $component_obj->get_parent();		if(empty($parentID)) { dump($component_obj,'$component_obj'); throw new Exception("parentID is empty", 1); };
		$tipo 				= $component_obj->get_tipo();
		$section_tipo 		= $component_obj->get_section_tipo();
		$matrix_table 		= common::get_matrix_table_from_tipo($section_tipo);
		$ar_final 			= array();

		# Despejamos todos sus hijos
		$arguments=array();
		$arguments['parent']	= $parentID;
		$arguments['tipo']		= $tipo;
		$RecordObj_matrix		= new RecordObj_matrix($matrix_table,NULL);
		$ar_records				= $RecordObj_matrix->search($arguments);
			#dump($ar_records,'$ar_records');


		# EMPTY RECORDS CASE
		if (empty($ar_records)) {
			if(SHOW_DEBUG===true) dump($component_obj,'$component_obj received obj');
			# Resultado vacío.
			#dump($ar_records,'ar_records', "arguments: ".print_r($arguments,true));
			trigger_error("No records found in matrix with arguments: ".print_r($arguments,true));

		# NORMAL CASE
		}else{

			foreach ($ar_records as $id) {

				# NO TRADUCIBLE
				if ($traducible==='no') {

					# Si hay mas de 1 registro lanzamos un error pues habrá una inconsistencia aquí
					if (count($ar_records)>1) {
						dump($ar_records,'$ar_records');
						throw new Exception("Inconsistency found. Too much records", 1);
					}

					$RecordObj_matrix		= new RecordObj_matrix($matrix_table,$id);
					$lang 					= $RecordObj_matrix->get_lang();
					if ($lang===DEDALO_DATA_NOLAN) {
						$dato 				= $RecordObj_matrix->get_dato();				#dump($dato,"dato para $terminoID_valor ".DEDALO_DATA_NOLAN);
						$ar_final[$tipo]	= $dato;
					}
				# TRADUCIBLE FALLBACKS
				}else{

					$RecordObj_matrix		= new RecordObj_matrix($matrix_table,$id);
					$lang 					= $RecordObj_matrix->get_lang();

					# 1 DEDALO_DATA_LANG
					if ($lang===DEDALO_DATA_LANG) {
						$dato 				= $RecordObj_matrix->get_dato();				#dump($dato,"dato para $terminoID_valor ".DEDALO_DATA_NOLAN);
						$ar_final[$tipo]	= $dato;
					}
					# 2 DEDALO_DATA_LANG_DEFAULT
					else if ($lang===DEDALO_DATA_LANG_DEFAULT){
						$dato 				= $RecordObj_matrix->get_dato();				#dump($dato,"dato para $terminoID_valor ".DEDALO_DATA_NOLAN);
						$ar_final[$tipo]	= component_common::decore_untranslated($dato);
					}

				}#if ($traducible==='no')

			}//END foreach

		}//END if (empty($ar_records))
		#dump($ar_final,"ar_final for $parentID - $tipo");
		return $ar_final;
		
	}//end get_current_record_with_lang_fallback_unified


	
	/**
	* GET_IMPLODED_AR_LIST_OF_VALUES
	* Despejamos la lista con los valores agrupados por "parents"
	* @return array $ar_list_of_values_formated
	*/
	private static function get_imploded_ar_list_of_values($ar_list_of_values) {

		$ar_list_of_values_formated = array();

		foreach($ar_list_of_values->result as $key => $value) {

			$string = '';
			if(is_array($value)) foreach($value as $key2) {
				if (is_array($key2)) foreach($key2 as $key3) {
					if (is_array($key3)) foreach ($key3 as $key4) {
						$string .= "$key4, ";
					}else{
						$string .= "$key3, ";
					}
				}else{
					$string .= "$key2, ";
				}
			}else{
				$string .= "$value, ";
			}
			#echo "\t - $key: $string <br>\n";
			$ar_list_of_values_formated[$key] = substr($string,0,-2);
		}

		return $ar_list_of_values_formated;
	}//end get_imploded_ar_list_of_values



	/**
	* DECORE UNTRANSLATED
	*/
	public static function decore_untranslated($string) {		
		return '<mark>'.to_string($string).'</mark>';
	}//end decore_untranslated



	/**
	* GET LANG NAME
	*/
	protected function get_lang_name() {

		$lang_name 	= '';

		$lang = $this->get_lang();
		if( !empty($lang) && strpos($lang, 'lg-')!==false ) {		
			$lang_name 	= lang::get_name_from_code( $lang, DEDALO_DATA_LANG );
		}		

		return $lang_name;
	}//end get_lang_name



	/**
	* ADD ELEMENT TO DATO ARRAY
	* Add element received to array (dato) and return resultant array
	* @param $element
	*	String
	* @param $dato
	*	Array of elements. Key=auto, Value=tag,  like '0=>861.0.0,1=>875.0.0'
	*//*
	public static function add_element_to_dato_array($element, Array $dato_array) {

		if(is_array($dato_array)) {
			array_push($dato_array, $element);
			$result = array_unique($dato_array);
		}else{
			$result = array($element);
		}
		#dump($result,'$result');
		return($result) ;
	}
	*/
	/**
	* REMOVE ELEMENT TO DATO ARRAY
	* Remove element received on array (dato) and return resultant array
	* !Important: force build new array to keep numeric key correlation (maintain json array format in matrix)
	* @param $element
	*	String full tag like '861.0.0'
	* @param $dato
	*	Array of elements. Key=auto, Value=tag,  like '0=>861.0.0,1=>875.0.0'
	*//*
	public static function remove_element_to_dato_array($element, $dato_array) {

		if(!is_array($dato_array)) return NULL;

		$ar_final = array();
		foreach ($dato_array as $current_target) {

			if ($current_target != $element) {
				# !Important: rebuilding array from index 0 (mantains json format)
				$ar_final[] = $current_target;
			}
		}

		return $ar_final;
	}
	*/


	
	/**
	* ADD_OBJECT_TO_DATO
	* Add received object to objects array
	*/
	public static function add_object_to_dato( $object, array $dato) {

		if (!is_object($object)) {
			throw new Exception("Error Processing Request. var 'object' is not of type object ", 1);			
		}
		if (get_class($object)==='locator') {
			$std_object = locator::get_std_class( $object );
		}else{
			$std_object = $object;
		}		

		$object_exists=false;
		foreach ($dato as $key => $current_object_obj) {
			/*
			if (!is_object($current_object_obj)) {
				if(SHOW_DEBUG===true) {
					throw new Exception("Error Processing Request. 'dato' elements are not objects. Please verify json_decode is called before use this method", 1);
				}
				trigger_error(__METHOD__ . "Sorry. Object expected. Nothing is added");
				break;
			}
			*/
			if ((object)$std_object==(object)$current_object_obj) {
				$object_exists=true; break;
			}
		}

		if ($object_exists===false) {
			$dato[] = $std_object;
		}

		return $dato;
	}//end add_object_to_dato



	/**
	* REMOVE_OBJECT_IN_DATO
	* Remove received object in objects array
	*/
	public static function remove_object_in_dato( $object, array $dato) {

		if (!is_object($object)) {
			throw new Exception("Error Processing Request. var 'object' is not of type object ", 1);			
		}
		if (get_class($object)==='locator') {			
			$std_object = locator::get_std_class( $object );
		}else{
			$std_object = $object;
		}

		$remove_key=false;
		foreach ($dato as $key => $current_object_obj) {
			if (!is_object($current_object_obj)) {
				if(SHOW_DEBUG===true) {
					throw new Exception("Error Processing Request. 'dato' elements are not objects. Please verify json_decode is called before use this method", 1);
				}
				trigger_error(__METHOD__ . "Sorry. Object expected. Nothing is removed");
				break;
			}
			if ((object)$std_object==(object)$current_object_obj) {
				$remove_key=$key;
				break;							
			}
		}

		if ($remove_key!==false) {
			
			unset($dato[$remove_key]);
			$dato = array_values($dato); # Re-index array dato (IMPORTANT FOR MAINTAIN JSON ARRAY FORMAT !!)					
		}		

		return $dato;
	}//end remove_object_in_dato



	/**
	* REMOVE_OBJECT_IN_DATO
	* Remove received locator in objects array comparing ar_properties
	*/
	public static function remove_locator_in_dato( $locator_obj, array $dato, $ar_properties=array('section_tipo','section_id')) {

		if (!is_object($locator_obj)) {
			throw new Exception("Error Processing Request. var 'object' is not of type object locator_obj ", 1);
		}
		

		$remove_key=false;
		foreach ($dato as $key => $current_locator_obj) {
			if (!is_object($current_locator_obj)) {
				if(SHOW_DEBUG===true) {
					throw new Exception("Error Processing Request. 'dato' elements are not objects. Please verify json_decode is called before use this method", 1);
				}
				trigger_error(__METHOD__ . "Sorry. Object locator expected. Nothing is removed");
				break;
			}
			if ( true===locator::compare_locators( $locator_obj, $current_locator_obj, $ar_properties ) ){
				$remove_key=$key;
				break;
			}
		}

		if ($remove_key!==false) {
			
			unset($dato[$remove_key]);
			$dato = array_values($dato); # Re-index array dato (IMPORTANT FOR MAINTAIN JSON ARRAY FORMAT !!)
		}

		return $dato;
	}//end remove_object_in_dato



	/**
	* DATO_ALREADY_EXISTS
	* Test if passed dato already esists in matrix
	* @return bool(true/false)
	*/
	public static function dato_already_exists($dato, $component_tipo, $lang=DEDALO_DATA_LANG, $section_tipo) {

		#dump($dato, " dato_already_exists component_tipo: $component_tipo");die();

		$arguments=array();
		$arguments["datos#>'{components, $component_tipo, dato,  $lang}'"] = json_encode($dato);
		$matrix_table			= common::get_matrix_table_from_tipo($section_tipo);
		$JSON_RecordObj_matrix	= new JSON_RecordObj_matrix($matrix_table,NULL,$section_tipo );
		$ar_records				= $JSON_RecordObj_matrix->search($arguments,$matrix_table);
			#dump($matrix_table, ' matrix_table ++ '.to_string($component_tipo));
			#dump($ar_records,"ar_records - current_matrix_table:$matrix_table $dato ".print_r($arguments,true)); die();

		if (count($ar_records)>0) {
			return true;
		}else{
			return false;
		}
	}//end dato_already_exists



	/**
	* GET_SECTION_TIPO_FROM_COMPONENT_TIPO
	*/
	public static function get_section_tipo_from_component_tipo($component_tipo) {

		debug_log(__METHOD__." DEPRECATED . Please avoid call this method. Will be REMOVED soon ".to_string(), logger::WARNING);

		if(SHOW_DEBUG===true) {
			#dump(debug_backtrace(), ' debug_backtrace '.to_string());
			#debug_log(__METHOD__." Please avoid call this method. Will be DEPRECATED soon ".to_string()); // sorry: ar_list_of_values uses this method..
		}		

		$section_tipo = null;

		# SRTUCTURE PARENTS
		$RecordObj_dd 		= new RecordObj_dd($component_tipo);
		$ar_parents_of_this = $RecordObj_dd->get_ar_parents_of_this($ksort=false);

		# MODELO
		foreach ($ar_parents_of_this as $current_tipo) {

			#if(!is_null($section_tipo)) {
			#	dump($ar_parents_of_this,'$ar_parents_of_this');
			#	throw new Exception("Error Processing Request. Inconsistent data. More than one section found!", 1);
			#}

			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
			if($modelo_name==='section') {
				$section_tipo = $current_tipo;
				break;
			}
		}		

		return $section_tipo;
	}//end get_section_tipo_from_component_tipo


	
	/**
	* GET_DIFFUSION_OBJ
	* @param stdClass Object $propiedades
	*/
	public function get_diffusion_obj( $propiedades ) {
		
		# Build object
		$diffusion_obj = new diffusion_component_obj();
			$diffusion_obj->component_name		= get_class($this);
			#$diffusion_obj->id 				= $this->get_id();	# Removed in b4
			$diffusion_obj->parent 				= $this->get_parent();
			$diffusion_obj->section_tipo 		= $this->get_section_tipo();
			$diffusion_obj->tipo 				= $this->get_tipo();
			$diffusion_obj->lang 				= $this->get_lang();
			$diffusion_obj->label 				= $this->get_label();
			#$diffusion_obj->dato 				= $this->get_dato();

			# initial_media_path
			#$section 							= section::get_instance($diffusion_obj->parent, $diffusion_obj->section_tipo );
			#$diffusion_obj->initial_media_path  = $section->get_initial_media_path();
			
			$diffusion_obj->initial_media_path = $this->get_initial_media_path();
				#dump($diffusion_obj->initial_media_path, ' diffusion_obj '.$diffusion_obj->section_tipo);

			/*
			$valor = $this->get_dato();
			$valor = to_string($valor);
			#$valor = filter_var($valor, FILTER_SANITIZE_STRING);
			$diffusion_obj->columns['valor'] 	= $valor;
			*/

		# Set standar 'valor' (Overwrite when need resolve dato. Ex. portals)
		$diffusion_obj->columns['valor'] = $this->get_valor();
			#dump($diffusion_obj,'$diffusion_obj');

		return $diffusion_obj;
	}//end get_diffusion_obj



	/**
	* GET_STATS_OBJ
	*/
	public function get_stats_obj( $propiedades ) {

		$stats_obj = new diffusion_stats_component_obj();
		$stats_obj = $this->get_dato();

		return $stats_obj;
	}//end get_stats_obj



	/**
	* GET_STATS_VALUE
	*/
	public static function get_stats_value( $tipo, $ar_value ) {

		$caller_component = get_called_class();

		#if($caller_component!='component_radio_button') return;
		#dump($ar_value,'ar_value '.$caller_component);

		if(!isset($stats_value))
		static $stats_value;


		# Formateamos el dato recibido
		if( is_array($ar_value) ) {

			foreach ($ar_value as $key => $value) {

				if(!isset($stats_value[$tipo][$value])) $stats_value[$tipo][$value] = 0;
				$stats_value[$tipo][$value] = $stats_value[$tipo][$value] + 1;
			}

		}else{

			$value = $ar_value;

			if(!isset($stats_value[$tipo][$value])) $stats_value[$tipo][$value] = 0;
			$stats_value[$tipo][$value] = $stats_value[$tipo][$value] + 1;
		}

		#if($caller_component==='component_autocomplete_ts')
		#dump($stats_value[$tipo],'$stats_value - '.$caller_component." - tipo:".$tipo);

		return $stats_value[$tipo];
	}//end get_stats_value



	/**
	* GET_STATS_VALUE_RESOLVED
	*/
	public static function get_stats_value_resolved( $tipo, $current_stats_value, $stats_model ,$stats_propiedades=NULL ) {

		$caller_component = get_called_class();

		#if($caller_component==='component_autocomplete_ts')
		#dump($current_stats_value ,'$current_stats_value '.$tipo ." $caller_component");

		foreach ($current_stats_value as $current_dato => $value) {

			if( empty($current_dato) ) {

				$current_dato = 'nd';
				$ar_final[$current_dato] = $value;

			}else{

				$current_component = component_common::get_instance($caller_component, $tipo, NULL, 'stats');
				$current_component->set_dato($current_dato);

				$valor = $current_component->get_valor();
					#dump($valor,'valor '.$caller_component. " - current_dato:$current_dato");

				$ar_final[$valor] = $value;
			}


		}//end foreach


		$label 		= RecordObj_dd::get_termino_by_tipo( $tipo,null,true ).':'.$stats_model;
		$ar_final 	= array($label => $ar_final );
			#dump($ar_final,'$ar_final '.$caller_component . " ".print_r($current_stats_value,true));

		return $ar_final;
	}//end get_stats_value_resolved



	/**
	* GET_COMPONENT_AR_LANGS
	* Devuelve un array con todos los idiomas usados por este componente a partir del dato de la sección que lo aloja
	* @return array $component_ar_langs
	*/
	public function get_component_ar_langs() {

		$component_ar_langs=array();

		$tipo 			= $this->tipo;
		$parent 		= $this->parent;

		if (empty($parent)) {
			trigger_error("Error: parent is mandatory for ".__METHOD__);
			if(SHOW_DEBUG===true) {
				dump($this,"this");
				throw new Exception("Error Processing Request", 1);				
			}
		}
		$section_tipo 	= $this->section_tipo;		
		$section 		= section::get_instance($this->parent,$section_tipo);
		$section_dato 	= $section->get_dato();

		if (isset($section_dato->components->$tipo->dato)) {
			$component_dato_full = $section_dato->components->$tipo->dato;
		}else{
			$component_dato_full = null;
		}
		
			#dump($component_dato_full,"component_dato_full $tipo");
		
		#$n = count( (array)$component_dato_full );
			#dump($n,"n");

		if ($component_dato_full!==null) {
			foreach ($component_dato_full as $key => $value) {
				$component_ar_langs[] = $key; // Old way
				/*
				$locator = new locator();
					$locator->set_section_tipo(DEDALO_LANGS_SECTION_TIPO);
					$locator->set_section_id(lang::get_section_id_from_code($key));
				
				$component_ar_langs[] = $locator;
				*/
			}
			#dump($component_ar_langs,"component_ar_langs");
		}		
		
		return (array)$component_ar_langs;	
	}//end get_component_ar_langs



	/**
	* GET_AR_AUTHORIZED_TOOL_NAME
	*/
	public function get_ar_authorized_tool_name() {
		if (self::get_permissions($this->section_tipo, $this->tipo)<=1) {
			return array();
		}
		
		if (!isset($this->ar_authorized_tool_name)) {
			$this->get_ar_tools_obj();
		}
		
		return (array)$this->ar_authorized_tool_name;
	}//end get_ar_authorized_tool_name



	/**
	* GET_COMPONENT_INFO
	* @param string $format
	*	Default 'json'
	*/
	public function get_component_info($format='json') {
		
		$component_info = new stdClass();			
			
			$component_info->ar_tools_name 	= (array)$this->get_ar_authorized_tool_name();
			$component_info->propiedades 	= (object)$this->get_propiedades();

		switch ($format) {
			case 'json':
				$component_info_json = json_handler::encode($component_info);
				#$component_info_json = rawurlencode($component_info_json);
				return $component_info_json;
				break;
			
			default:
				return $component_info;
				break;
		}

	}//end get_component_info

	

	public static function build_locator() {
		throw new Exception("DEPRECATED METHOD", 1);		
	}
	public static function build_locator_relation() {
		throw new Exception("DEPRECATED METHOD", 1);		
	}



	/*
	* GET_VALOR_LANG
	* Return the main component lang
	* If the component need change this langs (selects, radiobuttons...) overwritte this function
	*/
	public function get_valor_lang(){
		return $this->lang;
	}


	/*
	* GET_METHOD
	* Return the result of the method calculation into the component 
	*/
	public function get_method( $name ){
		/*
		if (method_exists($this,$name)) {
			return $this->$$name();
		}
		*/
		if (SHOW_DEBUG===true) {
			debug_log(__METHOD__.' This component don\'t have one method defined: '.$name, logger::WARNING);
		}
		return false;
	}//end get_method



	/**
	* GET_REFERENCED_TIPO
	* (used by component_autocomplete, component_radio_button.. )
	* @return string $this->referenced_tipo from TR of current component
	*/
	public function get_referenced_tipo() {

		if (isset($this->referenced_tipo)) return $this->referenced_tipo;

		# RELACIONES : Search and add relations to current component
		$relaciones = (array)$this->RecordObj_dd->get_relaciones();
			#dump($relaciones,'$relaciones');

		# ONLY >1 TR IS ALLOWED
		if(count($relaciones)<1 || !isset($relaciones[0])) {
			$tipo 		= $this->get_tipo();
			$termino 	= RecordObj_dd::get_termino_by_tipo($tipo,null,true);
			$modelo 	= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$msg = "Error Processing Request. invalid number of related components (".count($relaciones).") $termino";
			if(SHOW_DEBUG===true) {
				#dump($this,'this');				
				$msg .= "<hr> $modelo : $termino [$tipo] => relaciones = ". var_export($relaciones,true);
			}
			#throw new Exception($msg, 1);
		}
		
		#dump($relaciones, ' relaciones '.$this->tipo.'_'.$this->modo.'_'.$this->parent);
		$this->referenced_tipo = reset($relaciones[0]);
		#dump($referenced_tipo, ' referenced_tipo');
				
		return $this->referenced_tipo;
	}//end get_referenced_tipo



	/**
	* GET_REFERENCED_SECTION_TIPO
	* (used by component_autocomplete, component_radio_button.. for set target section_tipo (propiedades) - aditional to referenced component tipo (TR)- )
	* @return string $this->referenced_section_tipo from json propiedades section_tipo
	*/
	/*
		ACABARÁ UNIFICÁNDOSE AL COMPORTAMIENTO DE PORTAL Y AUTOCOMPLETE

	*/
	public function get_referenced_section_tipo($tipo) {

		if (isset($this->referenced_section_tipo)) return $this->referenced_section_tipo;		

		/* 
			NOTA: La convertimos en una alias de get_section_tipo_from_component_tipo

		#dump($this->propiedades," ");
		if (empty($this->propiedades) || !is_object($this->propiedades) || !property_exists($this->propiedades, 'section_tipo')) {
			#throw new Exception("Error Processing Request. ".get_class($this)." Propiedades -> section_tipo is mandatory ($this->tipo)", 1);			
		}

		$this->referenced_section_tipo = $this->propiedades->section_tipo;

		return $this->referenced_section_tipo;
		*/

		return $this->get_section_tipo_from_component_tipo($tipo);
	}//end get_referenced_section_tipo



	/**
	* GET_TARGET_SECTION_TIPO
	* Sección de la que se alimenta de registros el portal. No confundir con la sección en la que está el portal
	* OJO !! Se deprecó sin verificar las llamadas a ella (diffusion). Pasada a component portal que es quien la usa ahora mismo
	*/
	public function get_target_section_tipo__DEPRECATED() {
		
		if (!$this->tipo) return NULL;

		if(isset($this->target_section_tipo)) {
			#dump($this->target_section_tipo,"Already calculated [target_section_tipo]");
			return $this->target_section_tipo;
		}

		$ar_terminoID_by_modelo_name = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($this->tipo, 'section', 'termino_relacionado', $search_exact=true);
			#dump($ar_terminoID_by_modelo_name,'$ar_terminoID_by_modelo_name');

		if(SHOW_DEBUG===true) {

			if ( empty( reset($ar_terminoID_by_modelo_name) )) {
				$portal_name = RecordObj_dd::get_termino_by_tipo($this->tipo,null,true);
				throw new Exception("Error Processing Request. Please, define target section structure for portal: $portal_name - $this->tipo", 1);
			}
			if (count($ar_terminoID_by_modelo_name)!=1) {
				dump($ar_terminoID_by_modelo_name, '$ar_terminoID_by_modelo_name '."$this->tipo - section - termino_relacionado");
				throw new Exception("Error Processing Request. Structure element ($this->tipo) with more than one section related. Please fix ASAP. Count:".count($ar_terminoID_by_modelo_name), 1);				
			}
			#dump(count($ar_terminoID_by_modelo_name), 'count(var)');
		}

		$target_section_tipo = reset($ar_terminoID_by_modelo_name);
			#dump($target_section_tipo, '$target_section_tipo');
	

		# Fix value
		$this->target_section_tipo = $target_section_tipo;
		
		return $target_section_tipo;
	}//get_target_section_tipo__DEPRECATED



	/**
	* GET_TARGET_SECTION_TIPO --> TO DEPRECATE
	* Sección de la que se alimenta de registros el portal. No confundir con la sección en la que está el portal
	* OJO !! SE DEPRECÓ EN COMPONENT_COMMON SIN VERIFICAR LAS LLAMADAS A ELLA (DIFFUSION POR EJEMPLO). la vuelvo a activar en el componente portal porque es necesaria aquí.. (Paco 04-12-2015
	* Se desactivó porque ahora un portal puede tener más de 1 target_section (multi target..). Mantendremos este método por compatibilidad con lo anterior hasta solucionar el tema
	* de forma más "elegante". Se devolverá un array con todas, en orden. Por tanto es importante poner la principal la primera !!!
	* @return string tipo . first element of ar_target_section_tipo 
	* @see TO DEPRECATE
	*/
	public function get_target_section_tipo() {
		$ar_target_section_tipo = $this->get_ar_target_section_tipo();
		$main_target_section 	= reset($ar_target_section_tipo);		
		#
		# DEPRECATED SOON WARNING
		trigger_error("WARNING: this method: 'get_target_section_tipo' will be deprecated soon. First element is returned now, but, PLEASE USE 'get_ar_target_section_tipo' AND SELECT THE ELEMENT THAT YOU NEED ");
		
		return $main_target_section;
	}//end get_target_section_tipo


	/**
	* GET_AR_TARGET_SECTION_TIPO
	* Sección/es de la que se alimenta de registros el portal/autocomplete. No confundir con la sección en la que está el portal
	*/
	public function get_ar_target_section_tipo() {
		
		if (!$this->tipo) return NULL;

		if(isset($this->ar_target_section_tipo)) {
			#dump($this->target_section_tipo,"Already calculated [target_section_tipo]");
			return $this->ar_target_section_tipo;
		}

		$ar_terminoID_by_modelo_name = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($this->tipo, 'section', 'termino_relacionado', $search_exact=true);
			#dump($ar_terminoID_by_modelo_name,'$ar_terminoID_by_modelo_name');

		if(SHOW_DEBUG===true) {

			if ( empty( $ar_terminoID_by_modelo_name)) {
				$component_name = RecordObj_dd::get_termino_by_tipo($this->tipo,null,true);
				throw new Exception("Error Processing Request. Please, define target section structure for component: $component_name - $this->tipo", 1);
			}
			
			#dump(count($ar_terminoID_by_modelo_name), 'count(var)');
		}

		$ar_target_section_tipo = $ar_terminoID_by_modelo_name;
			#dump($ar_target_section_tipo, '$ar_target_section_tipo');
	

		# Fix value
		$this->ar_target_section_tipo = $ar_target_section_tipo;
		
		return (array)$ar_target_section_tipo;
	}//end get_ar_target_section_tipo
	


	/**
	* GET_AR_COMPONENTS_WITH_REFERENCES
	* Get model name array of components that can store references (locators)
	* @return array $ar_components_with_references
	*/
	public function get_ar_components_with_references() {		
		return array('component_portal','component_autocomplete','component_radio_button','component_check_box','component_select');
	}//end get_ar_components_with_references



	/**
	* RESOLVE_SEARCH_OPERATORS (temporal method)
	* @return string $sql_line
	*/
	public static function resolve_search_operators__DEPERECATED( $field_name, $string_to_resolve, $default_operator='=' ) {
		$sql_line = null;
		$ar_operators = array('<','>','<=','>=');
		foreach ($ar_operators as $current_operator) {
			if (strpos($string_to_resolve, $current_operator)!==false) {
				$sql_line = $field_name.$string_to_resolve;
				break;
			}
		}

		if (is_null($sql_line)) {
			$sql_line = $field_name . $default_operator . $string_to_resolve;
		}

		return (string)$sql_line;
	}//end resolve_search_operators

	


	/**
	* BUILD_SEARCH_COMPARISON_OPERATORS 
	* @return object stdClass $search_comparison_operators
	*/
	public function build_search_comparison_operators( $comparison_operators=array('ILIKE','LIKE','=','!=') ) {
		$search_comparison_operators = new stdClass();

		#
		# Overwrite defaults with 'propiedades'->SQL_comparison_operators
			if(SHOW_DEBUG===true) {
				#dump($this->propiedades, " this->propiedades ".to_string());;
			}		
			if(isset($this->propiedades->SQL_comparison_operators)) {
				$comparison_operators = (array)$this->propiedades->SQL_comparison_operators;
			}


		foreach ($comparison_operators as $current) {
			# Get the name of the operator in current lang 
			$operator = operator::get_operator($current);
			$search_comparison_operators->$current = $operator;
		}
		return (object)$search_comparison_operators;
	}//end build_search_comparison_operators


	/**
	* BUILD_SEARCH_LOGICAL_OPERATORS 
	* Default generic method
	* @return object stdClass $search_comparison_operators
	*/
	public function build_search_logical_operators( $logical_operators=array('AND','OR','NOT') ) {
		$search_logical_operators = new stdClass();

		#
		# Overwrite defaults with 'propiedades'->SQL_logical_operators
			if(SHOW_DEBUG===true) {
				#dump($this->propiedades, " this->propiedades ".to_string());;
			}		
			if(isset($this->propiedades->SQL_logical_operators)) {
				$logical_operators = (array)$this->propiedades->SQL_logical_operators;
			}

		foreach ($logical_operators as $current) {
			# Get the name of the operator in current lang
			$operator = operator::get_operator($current);
			$search_logical_operators->$current = $operator;
		}
		return (object)$search_logical_operators;
	}//end build_search_logical_operators



	/**
	* GET_SEARCH_QUERY
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
	public static function get_search_query( $json_field, $search_tipo, $tipo_de_dato_search, $current_lang, $search_value, $comparison_operator='ILIKE') {//, $logical_operator = 'AND' 
		
		if (empty($search_value)) return false;

		$json_field = 'a.'.$json_field; // Add 'a.' for mandatory table alias search

		$search_query='';
		switch (true) {
			case ($comparison_operator==='ILIKE' || $comparison_operator==='LIKE'):
				// Allow wildcards like "house*" or "*house"
				// dump($search_value[strlen($search_value) - 1], "$search_value[0] ".to_string());
				$separator 	   = '*';				
				if ( $search_value[0] === $separator ) {
					// Begin with * like
					$search_value = str_replace($separator, '', $search_value);
					$search_query = " unaccent({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator '%$search_value' ";
				
				}else if ( $search_value[strlen($search_value) - 1] === $separator ) {
					// End with *
					$search_value = str_replace($separator, '', $search_value);
					$search_query = " unaccent({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator '$search_value%' ";
					
				}else{
					// Contain
					$search_value = str_replace($separator, '', $search_value);
					$search_query = " unaccent({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator '%$search_value%' ";
				}
				break;

			case ($comparison_operator==='=' || $comparison_operator==='!='):
				$search_query = " unaccent({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator '$search_value' ";
				break;

			case ($comparison_operator==='IS NULL' || $comparison_operator==='IS NOT NULL'):
				if($comparison_operator === 'IS NULL'){
					$comparison_operator2 = '=';
					$union_operator = 'OR';
				}else{
					$comparison_operator2 = '!=';
					$union_operator = 'AND';
				}
				$search_query  = " ({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator $union_operator ";
				$search_query .= " {$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator2 '' )";
				break;

			default:
				$search_query = " unaccent({$json_field}#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') $comparison_operator '%$search_value%' ";
				break;
		}
		
		if(SHOW_DEBUG===true) {
			$search_query = " -- filter_by_search $search_tipo ". get_called_class() ." \n".$search_query;
		}
		return (string)$search_query;
	}//end get_search_query



	/**
	* GET_SEARCH_ORDER
	* Overwrite as needed
	* @return string $order_direction
	*/
	public static function get_search_order($json_field, $search_tipo, $tipo_de_dato_order, $current_lang, $order_direction) {
		$order_by_resolved = "a.$json_field#>>'{components, $search_tipo, $tipo_de_dato_order, $current_lang}' ".$order_direction;
		
		return (string)$order_by_resolved;
	}//end get_search_order



	/**
	* UPDATE_STATE
	* (common because is used by various components and tools, ..)
	* @param object $state_obj ("lang":"toolXXX":1)
	* @return object $this->component_state set and store component_state)
	*/
	protected function update_state_OLD($state_obj) {

		if(!is_object($state_obj)){
			throw new Exception("Error Processing Request this no is a object".to_string($state_obj), 1);
		}

		$section_id 	= $this->component_obj->get_parent();
		$component_tipo = $this->component_obj->get_tipo();
		$section_tipo	= $this->component_obj->get_section_tipo();

		$locator_state 	= new locator();
			$locator->set_section_tipo($section_tipo);
			$locator->set_section_id($section_id);
			$locator->set_component_tipo($component_tipo);
			$locator->set_status($state_obj);

		$component_state = $this->component_obj->get_component_state_obj();

		return $component_state->update_state($locator_state);			
	}//end update_state_OLD



	/**
	* GET_STATE
	* (common because is used by various components and tools, ..)
	* @param object $state_obj ("lang":"toolXXX":1)
	* @return object $this->component_state set and store component_state)
	*/
	protected function get_state_OLD($tool_name=null,$lang=DEDALO_DATA_LANG) {

		$component_state = $this->component_obj->get_component_state_obj();

		$section_id 	= $this->component_obj->get_parent();
		$component_tipo = $this->component_obj->get_tipo();
		$section_tipo	= $this->component_obj->get_section_tipo();

		$locator_state 	= new locator();
			$locator->set_section_tipo($section_tipo);
			$locator->set_section_id($section_id);
			$locator->set_component_tipo($component_tipo);

		$my_state 		= $component_state->get_my_state($locator_state);

		if(!empty($tool_name)){
			$my_state = $my_state->state->$lang->$tool_name;
		}

		return $my_state;
	}//end get_state_OLD



	/**
	* GET_STATE
	* (common because is used by various components and tools, ..)
	* @param object $state_obj ("lang":"toolXXX":1)
	* @return object $this->component_state set and store component_state)
	*/
	public function get_component_state($tool_locator=null,$lang=DEDALO_DATA_LANG) {

		$component_state 	 = $this->get_component_state_obj();

		if (empty($component_state) || !is_object($component_state)) {
			if(SHOW_DEBUG===true) {
				#dump($component_state, ' empty component_state ++ '.to_string($tool_locator));;
			}
			return false;
		}
		$section_id 		 = $component_state->get_parent();
		$component_tipo 	 = $this->get_tipo();
		$section_tipo		 = $component_state->get_section_tipo();
		
		$options 	= new stdClass();
			$options->section_tipo 		= $section_tipo;
			$options->section_id 		= $section_id;
			$options->component_tipo 	= $component_tipo;
			$options->lang 				= $lang;
			$options->tool_locator 		= $tool_locator;
		
		$component_state->set_options($options);

		return $component_state;
	}//end get_component_state



	/**
	* GET_COMPONENT_STATE_OBJ
	* (common because is used by various components and tools, ..)
	* @return object $this->component_state (get and store component_state)
	*/
	protected function get_component_state_obj($modo='edit_tool') {

		if(isset($this->component_state)) return $this->component_state;

		$section_id 			= $this->get_parent();
		$component_tipo 		= $this->get_tipo();
		$section_tipo			= $this->get_section_tipo();
		$component_state_tipo 	= $this->get_component_state_tipo();			

		if (!$component_state_tipo) {
			return null;
		}else{
			$this->component_state  = component_common::get_instance('component_state',
																	  $component_state_tipo,
																	  $section_id,
																	  $modo,
																	  DEDALO_DATA_NOLAN,
																	  $section_tipo);
		}	

		return (object)$this->component_state;
	}//end get_component_state_obj



	/**
	* GET_COMPONENT_STATE_TIPO
	* @return 
	*/
	public function get_component_state_tipo() {

		if(isset($this->component_state_tipo)) return $this->component_state_tipo;

		$section_id 	= $this->get_parent();
		$component_tipo = $this->get_tipo();
		$section_tipo	= $this->get_section_tipo();
		$ar_result 		= section::get_ar_children_tipo_by_modelo_name_in_section($section_tipo, 'component_state', true, true);
			#dump($ar_result,'get_ar_children_tipo_by_modelo_name_in_section '.$section_tipo);			

		if (empty($ar_result[0])) {
			return false;
		}else{
			$this->component_state_tipo = $ar_result[0];
		}	

		return (string)$this->component_state_tipo;
	}//end get_component_state_tipo



	/**
	* GET_STATE_PROCESS_HTML
	* @return string $state_process_html
	*/
	public function get_state_process_html() {
		#dump($this->propiedades, ' $this->propiedades ++ '.to_string());
		if (!isset($this->propiedades->state->edit_component)) {
			return null;
		}

		$ar_children_tipo_by_modelo = section::get_ar_children_tipo_by_modelo_name_in_section($this->section_tipo, 'component_state', true, true);
		if (empty($ar_children_tipo_by_modelo)) {
			if(SHOW_DEBUG===true) {
				debug_log(__METHOD__." Section without component_state defined in structure");
			}
			return null;
		}
		$component_state_tipo = $ar_children_tipo_by_modelo[0];
		$component_state 	  = component_common::get_instance('component_state',
															   $component_state_tipo,
															   $this->parent,
															   'edit_component',
															   DEDALO_DATA_NOLAN,
															   $this->section_tipo);
		/**/
		$state = $this->propiedades->state->edit_component;
			#dump($state, ' state ++ '.to_string());
		$component_state->configure_for_component( $state, $this->tipo, $this->parent, $this->section_tipo, $this->lang );
		
		$state_process_html = $component_state->get_html();

		return $state_process_html;
	}//end get_state_process_html



	/**
	* RENDER_LIST_VALUE
	* (Overwrite for non default behaviour)
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
	public static function render_list_value($value, $tipo, $parent, $modo, $lang, $section_tipo, $section_id, $current_locator=null, $caller_component_tipo=null) {
		
		return $value;
	}//end render_list_value



	/**
	* GET_DIFFUSION_VALUE
	* Calculate current component diffsuion value for target field (usually a mysql field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( $lang ) {
		
		# Default behaviour is get value
		$diffusion_value = $this->get_valor( $lang );


		return (string)$diffusion_value;
	}//end get_diffusion_value



	/**
	* UPDATE_DATO_VERSION
	* @return $response->result =0; // the component don't have the function "update_dato_version"
	* @return $response->result =1; // the component do the update"
	* @return $response->result =2; // the component try the update but the dato don't need change"
	*/
	public static function update_dato_version($update_version, $dato_unchanged, $reference_id) {
		$response = new stdClass();
		$modelo_name = get_called_class();
		$response->result =0;
		$response->msg = "This component $modelo_name don't have update_dato_version, please check the class of the component <br />";
		
		return $response;		
	}//end update_dato_version



	/**
	* GET_SEMANTIC_NODES
	* Used by components using locators like component_portal and component_autocomplete
	* @return array $semantic_nodes
	*	like array('dd1874')
	*/
	public function get_semantic_nodes() {

		if(isset($this->semantic_nodes)) return $this->semantic_nodes;
		
		$this->semantic_nodes = array();

		$childrens = $this->RecordObj_dd->get_ar_childrens_of_this();
			#dump($childrens, ' childrens ++ '.to_string());
		foreach ($childrens as $current_tipo) {
			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
			if ($modelo_name==='semantic_node') {
				$this->semantic_nodes[] = $current_tipo;
			}
		}

		return (array)$this->semantic_nodes;
	}//end get_semantic_nodes



	/**
	* ADD_INDEX_SEMANTIC
	* Used by components using locators like component_portal and component_autocomplete
	* @param string $termino_id
	* @param string $locator_section_tipo
	* @param string $locator_section_id
	* @param string $ds_key
	*	Dédalo semantics key
	* @return object $response
	*/
	public function add_index_semantic($new_ds_locator, $locator_section_tipo, $locator_section_id, $ds_key) {

		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= '';

		$dato = $this->get_dato();
		foreach ((array)$dato as $current_locator) {
			
			if ($current_locator->section_tipo===$locator_section_tipo && $current_locator->section_id==$locator_section_id) {
			#if ( locator::compare_locators($current_locator, $locator, array('section_tipo','section_id')) ) {

				# new ds locator is builded from termino_id temporarily
				#$new_ds_locator = component_autocomplete_ts::convert_dato_to_locator($termino_id);
					#dump($new_ds_locator, ' new_ds_locator ++ '.to_string($termino_id));

				# ds container add if not exits in current locator
				if(!isset($current_locator->ds)) {
					$current_locator->ds = new stdClass();
				}
				if(!isset($current_locator->ds->$ds_key)) {
					$current_locator->ds->$ds_key = array();
				}

				# add ds locator to current portal locator removing duplicates				
				$current_locator->ds->$ds_key = component_common::add_object_to_dato((object)$new_ds_locator, (array)$current_locator->ds->$ds_key);
				
				$this->set_dato($dato);		#dump($dato, ' dato ++ '.to_string());
				$this->Save();

				$response->result 	= true;
				$response->msg 		= "Added $termino_id to locator ds";
				break;
			}

		}//endforeach ((array)$dato as $current_locator) { 
		
		return (object)$response;		
	}//end add_index_semantic



	/**
	* REMOVE_INDEX_SEMANTIC
	* Used by components using locators like component_portal and component_autocomplete
	* @param string $termino_id
	* @param string $locator_section_tipo
	* @param string $locator_section_id
	* @return object $response
	*/
	public function remove_index_semantic($new_ds_locator, $locator_section_tipo, $locator_section_id, $ds_key) {

		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= '';

		$dato = $this->get_dato();
		foreach ((array)$dato as $current_locator) {
			
			if ($current_locator->section_tipo===$locator_section_tipo && $current_locator->section_id==$locator_section_id) {

				# ds container add if not exits in current locator
				if(!isset($current_locator->ds->$ds_key)) {					
					$response->msg = 'Sorry, current index not exists. Nothing is removed';
					return $respose;
				}

				# new ds locator is builded from termino_id temporarily
				#$new_ds_locator = component_autocomplete_ts::convert_dato_to_locator($termino_id);
					#dump($new_ds_locator, ' new_ds_locator ++ '.to_string($termino_id));				

				# add ds locator to current portal locator removing duplicates				
				#$current_locator->ds->$ds_key = component_common::remove_object_in_dato((object)$new_ds_locator, (array)$current_locator->ds->$ds_key);
				$current_locator->ds->$ds_key = component_common::remove_locator_in_dato((object)$new_ds_locator, (array)$current_locator->ds->$ds_key);
				
				$this->set_dato($dato);		#dump($dato, ' dato ++ '.to_string());
				$this->Save();

				$response->result 	= true;
				$response->msg 		= "Removed $termino_id from locator ds";
				break;
			}

		}//endforeach ((array)$dato as $current_locator) { 
		
		return (object)$response;		
	}//end remove_index_semantic



	/**
	* GET_VALOR_LIST_HTML_TO_SAVE
	* Usado por section:save_component_dato
	* Devuelve a section el html a usar para rellenar el 'campo' 'valor_list' al guardar
	* Por defecto será el html generado por el componente en modo 'list', pero en algunos casos
	* es necesario sobre-escribirlo, como en component_portal, que ha de resolverse obigatoriamente en cada row de listado
	* @see class.section.php
	* @return string $html
	*/
	public function get_valor_list_html_to_save() {
		# Store current modo
		$modo_previous = $this->get_modo();

		# Temporal modo
		$this->set_modo('list');

		# Get html from current component
		$html = $this->get_html();
		
		# Return it to anterior mode after get the html
		$this->set_modo($modo_previous);	# Important!
		
		return (string)$html;
	}//end get_valor_list_html_to_save



	/**
	* GET_ORDER_BY_LOCATOR
	* @return bool
	*/
	public static function get_order_by_locator() {
		return false;
	}//end get_order_by_locator



	/**
	* SET_DATO_FROM_CSV
	* Receive a plain text value from csv file and set this value as dato.
	* Override in each component.
	* @param object $data
	* @return bool
	*/
	public function set_dato_from_csv( $data ) {
		debug_log(__METHOD__." Please, overwrite this method in each ".to_string(), logger::DEBUG);
		#$this->set_dato($value);
		return false;
	}//end set_dato_from_csv



	/**
	* GET_SECTION_ID_FROM_VALUE
	* Only first 
	* @return mixed int|null
	*/
	public static function get_section_id_from_value( $value, $target_section_tipo, $target_component_tipo ) {

		# Search if value exists in target section
		$json_field 		 = 'datos';
		$search_tipo 		 = $target_component_tipo;
		$tipo_de_dato_search = 'dato';
		$RecordObj_dd 		 = new RecordObj_dd($target_component_tipo);
		$traducible 		 = $RecordObj_dd->get_traducible();
		$current_lang 		 = ($traducible==='si') ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN;
		$search_value 		 = $value;
		$comparison_operator = '=';

		$target_component_model = RecordObj_dd::get_modelo_name_by_tipo($target_component_tipo, true);

		$search_query = $target_component_model::get_search_query( $json_field, $search_tipo, $tipo_de_dato_search, $current_lang, $search_value, $comparison_operator);
		# dump($search_query, ' $search_query ++ '.to_string());

		$table = common::get_matrix_table_from_tipo($target_section_tipo);
		
		$strQuery = "SELECT section_id FROM \"$table\" a WHERE $search_query ORDER BY section_id ASC LIMIT 1";
		$result	  = JSON_RecordObj_matrix::search_free($strQuery);
		$section_id_from_value = null;
		while ($rows = pg_fetch_assoc($result)) {
			$section_id_from_value = (int)$rows['section_id'];
			break;
		}
		#dump($section_id_from_value, ' $section_id_from_value ++ '.to_string($strQuery));

		return $section_id_from_value;
	}//end get_section_id_from_value



	/**
	* REGENERATE_COMPONENT
	* Force the current component to re-save its data
	* Note that the first action is always load dato to avoid save empty content
	* @see class.tool_update_cache.php
	* @return bool
	*/
	public function regenerate_component() {

		# Force loads dato always !IMPORTANT
		$this->get_dato();

		# Save component data
		$this->Save();
			
		
		return true;
	}//end regenerate_component



	/**
	* GET_SEARCH_INPUT_NAME
	* Search input name (var search_input_name is injected in search -> records_search_list.phtml)
	* and recovered in component_common->get_search_input_name()
	* Normally is section_tipo + component_tipo, but when in portal can be portal_tipo + section_tipo + component_tipo
	* @return string $search_input_name
	*/
	public function get_search_input_name() {
		return $this->search_input_name;
	}//end get_search_input_name



}//end class
?>