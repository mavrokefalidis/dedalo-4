




// INSPECTOR CLASS
var inspector = new function() {

	// SIDEBAR STATE
	this.sidebar_state = 'isopen'
	this.css_section_content
	this.sidebar


	this.max_screen_width = 960
	this.url_trigger 	  = DEDALO_LIB_BASE_URL + '/component_common/trigger.component_common.php'
	

	switch(page_globals.modo) {
		
		case 'edit' :
			$(function() {
				// Set selectors
				inspector.css_section_content = $('.css_section_content').first()
				inspector.sidebar 			  = $('#sidebar')

				// ARRANGE POSITION IN DIALOG WINDOW
				if (html_page.on_dialog_window === 1) {
					$('#button_toggle_inspector').addClass('button_toggle_inspector_in_dialog')
				};

				/* INSPECTOR SCROLL FIXED */
				inspector.maintain_inspector_position()

				// AVOID RESET COMPONENT SELECT ON CLICK
				$('#inspector_div').find('.tab_content').click(function(e) {
					e.stopPropagation();
				});
			});			
			break;
	}



	/**
	* MAINTAIN_INSPECTOR_POSITION
	*/
	this.maintain_inspector_position = function(){
	
		var sidebar = document.getElementById('sidebar')
		if (sidebar) {
			var fix_top = function()	{
				var window_height 	 = window.innerHeight,
					window_width 	 = window.innerWidth,
					inspector_height = sidebar.clientHeight
					//console.log(window_height); console.log(inspector_height);
				if (window_width > inspector.max_screen_width) {
					if (inspector_height < window_height) {
						
						// On change scroll position, maintain sidebar position
						//inspector.sidebar.css({'top': parseInt(window.scrollY)+'px'});
						sidebar.style.top = window.scrollY +'px'
						
					}else{
						//inspector.sidebar.css({'top': 0+'px'});
						sidebar.style.top = 0 +'px'
					}
				}else{
					//inspector.sidebar.css({'top': 0+'px','position':'relative'});
				}				
			}
				
			var isiPad = navigator.userAgent.match(/iPad/i) != null
			if(!isiPad || window.innerWidth > inspector.max_screen_width) {
				window.addEventListener("scroll", fix_top)
			}
		}				
	};



	/**
	* TOGGLE SIDEBAR (INSPECTOR)
	*/
	this.toggle_sidebar = function() {	
	
		if(this.sidebar_state!='isopen') {
			this.close_content()
			this.open_inspector()
			// fix state
			this.sidebar_state = 'isopen'
		}else{		
			this.open_content()
			this.close_inspector()
			// fix state
			this.sidebar_state = 'isclose'
		}
		//alert(this.sidebar_state)
		return false
	};



	/**
	* OPEN MAIN INSPECTOR
	*/
	this.open_inspector = function() {
		if (this.sidebar) {
			this.sidebar.show(0)
		}			
	};
	/**
	* CLOSE MAIN INSPECTOR
	*/
	this.close_inspector = function() {
		if (this.sidebar) {
			this.sidebar.hide(0)
		}		
	};


	/**
	* OPEN INSPECTOR CONTENT
	*/
	this.open_content = function(content_div_id) {	
		var section_content_selector = this.css_section_content  //$('.css_section_content').first();
		if (typeof content_div_id !== 'undefined') {
			section_content_selector = $('#'+content_div_id)
		}
		$(section_content_selector).animate(0).css('width', '99.9%')
	};
	/**
	* CLOSE INSPECTOR CONTENT 
	*/
	this.close_content = function(content_div_id) {
		var section_content_selector = this.css_section_content;	//$('.css_section_content').first();
		if (typeof content_div_id !== 'undefined') {
			section_content_selector = $('#'+content_div_id)
		}
		if (window.innerWidth > inspector.max_screen_width) {
			$(section_content_selector).animate(0).css('width', '80%')
		}
	};


	/**
	* SHOW LOG MSG (In inspector log with delayed hide)
	*
	*/
	this.show_log_msg = function (msg, delay_secs) {
		
		if( typeof delay === 'undefined') delay_secs = 10000 * 3

		// Span save label
		var span_save = document.createElement("span")
			span_save.classList.add("span_save")
			span_save.innerHTML = msg

			span_save.addEventListener("click", function(event){
				this.remove()
			})

			setTimeout(function(){
				span_save.remove()
			},7000)
	
		// Place span save label in inspector if exists
		try {
			var inspector_log = document.getElementById('inspector_log')
				if (inspector_log) {
					inspector_log.appendChild(span_save)
				}else{
					console.log( 'Inspector msg: ' + msg.toString() )
				}
		}catch(error) {
			console.log( 'Inspector msg catched: ' + msg.toString() )
		}					
	}



	/**
	* UPDATE INSPECTOR INFO
	* Update inspector info when wrap is selected
	*/
	this.previous_update_inspector_info_caller = null
	this.update_inspector_info = function (obj_warp) {

		if (obj_warp instanceof jQuery ) {
			obj_warp = obj_warp[0]	// object jquery case
		}
		if (!obj_warp) {
			if (DEBUG) console.log('Error on get obj_warp. Stopped update_inspector_info')
			return false
		}

		// Click body or no wrap component elements case
	 	if ( typeof obj_warp.dataset.tipo ==='undefined' ) {
	 		return false
	 	}

		var mode			= 'update_inspector_info',
			tipo			= obj_warp.dataset.tipo,
			current_parent	= obj_warp.dataset.parent,
			lang			= obj_warp.dataset.lang,
			dato			= obj_warp.dataset.dato || '',
			component_name 	= obj_warp.dataset.component_name,
			target_obj		= document.getElementById('inspector_info'),			//$('#inspector_info'),
			debug_div		= document.getElementById('inspector_debug'),			//$('#inspector_debug'),
			mod_date 		= '',
			mod_by_user_name= '',		
			label 			= typeof obj_warp.querySelectorAll('label')[0] ==='object' ? obj_warp.querySelectorAll('label')[0].innerHTML : ''


		// Page var globals verify
		if( typeof current_parent ==='undefined' || !current_parent ) {
			console.log('Error: current_parent not defined! (update_inspector_info) ' + current_parent)
			return false
		}

		// Reset some content
		//$('#inspector_indexations').html('');
		//$('#inspector_relation_list_sections').html('');
		var ii  = document.getElementById('inspector_indexations')
			if(ii) ii.innerHTML=''
		var irl = document.getElementById('inspector_relation_list_sections')
			if (irl) irl.innerHTML=''

		// COMPONENT_INFO : MOD_DATE
		var mod_date = mod_by_user_name = ''
		if (typeof obj_warp.dataset.component_info !== 'undefined' && obj_warp.dataset.component_info.length>0) {
			try {
			   var component_info = JSON.parse(obj_warp.dataset.component_info)
			   if ( typeof component_info.mod_date !== 'undefined' ) {
					mod_date = component_info.mod_date
				}
				if (typeof component_info.mod_by_user_name !== 'undefined' ) {
					mod_by_user_name = component_info.mod_by_user_name
				}	
			}
			catch (e) {
			   console.log(e)
			}			
		}
			
		
		// HTML TO INSPECTOR
		if (target_obj) {
			target_obj.innerHTML  =''
			target_obj.innerHTML +='<div class="key capitalize">'+get_label.seleccionado+'</div><div class="value"><b style="color:#333">'+label+'</b> <span class="debug_info">('+lang+')</span></div><br>'
			target_obj.innerHTML +='<div class="key capitalize">'+get_label.modificado+'</div><div class="value">'+mod_date+'</div><br>'
			target_obj.innerHTML +='<div class="key">'+get_label.por_usuario+'</div><div class="value">'+mod_by_user_name+'</div><br>'

			if (DEBUG) {
			debug_div.innerHTML  =''
			debug_div.innerHTML +='<div class="key">parent</div><div class="value"> '+ current_parent+ '</div><br>'
			debug_div.innerHTML +='<div class="key">tipo</div><div class="value"> '  + tipo 			+ '</div><br>'
			debug_div.innerHTML +='<div class="key">lang</div><div class="value"> '  + lang 			+ '</div><br>'
			debug_div.innerHTML +='<div class="key">label</div><div class="value"> ' + label 		+ '</div><br>'
			debug_div.innerHTML +='<div class="key">model</div><div class="value"> ' + component_name+ '</div><br>'
			var dato_trim = dato.substring(0, 255) || dato;
			debug_div.innerHTML +='<div class="key">dato</div><div class="value"> '   + dato_trim 	+ '</div><br>'
			}
		}
		return false

	}//end update_inspector_info



	/**
	* LOAD INSPECTOR TOOLS BUTTONS
	* Load inspector tools buttons whe wrap is selected
	*/
	this.previous_load_inspector_tools_caller = null
	this.load_inspector_tools = function (obj_warp) {

		if (obj_warp instanceof jQuery ) {
			obj_warp = obj_warp[0]	// object jquery case
		}
		if (!obj_warp) {
			if (DEBUG) console.log("Error on get obj_warp. Stopped load_inspector_tools")
			return false
		}	 	 

	 	// Click body or no wrap component elements case
	 	if ( typeof obj_warp.dataset.tipo === 'undefined' ) {
	 		return false
	 	}

	 	if ( typeof obj_warp.dataset.parent === 'undefined' ) {
	 		return false
	 	}

		var tipo				 = obj_warp.dataset.tipo
		var	current_parent		 = obj_warp.dataset.parent
		var	lang				 = obj_warp.dataset.lang
		var	context_name		 = obj_warp.dataset.component_name
		var	current_tipo_section = obj_warp.dataset.current_tipo_section	//return alert('load_inspector_tools \nid:'+id_matrix + " \ntipo:"+tipo + " \ncurrent_tipo_section:"+current_tipo_section)
		//var	section_tipo 		 = page_globals.section_tipo
		var	section_tipo 		 = obj_warp.dataset.section_tipo

		// target result div
		var target_obj = document.getElementById('inspector_tools')
		if(!target_obj) {
			if (DEBUG) 	console.log("->load_inspector_tools : Error: 'inspector_tools' DOM element not found")
			return false; //alert("Error: load_inspector_tools (target_obj not found!)");
		}

		// test if parent is global var
		// Updated: get parent of current wrap obj
		if (typeof current_parent === 'undefined' || !current_parent ) {
			 console.log( obj_warp.dataset )
			return alert("load_inspector_tools Error: wrap dataset parent is not set " + current_parent)
		}


		/*
			$html_tool   .= "<div class=\"tool_inspector_div\">";

			$btn_title	  = label::get_label('abrir').' '.label::get_label('herramienta');
			$html_tool   .= "\n<div class=\"icon_bs tool_lang_icon link\"
								data-tipo=\"{$tipo}\" data-parent=\"{$parent}\" data-lang=\"{$lang}\" data-tab_id=\"img_lang_{$tipo}_{$parent}\" 
								title=\"$btn_title\"
								onclick=\"tool_common.open_tool_lang(this)\"
								></div>";
			$html_tool   .= label::get_label('tool_lang');	#" Lang";
			$html_tool   .= "</div>";
		*/
		var ar_tools_name    = []
		target_obj.innerHTML = ''
		try {
			var component_info = JSON.parse(obj_warp.dataset.component_info)
				//console.log(component_info);

			if (component_info && typeof component_info.ar_tools_name !== 'undefined') {
				ar_tools_name = component_info.ar_tools_name
			}
			//console.log(ar_tools_name);
			if (ar_tools_name.length > 0) {

				var html_string = ''
				var len = ar_tools_name.length
				for (var i = 0; i < len; i++) {

					var tool_name 	= ar_tools_name[i]
					var	label 	  	= get_label[tool_name]
					var	title 		= get_label.abrir + ' ' + get_label.herramienta

					// Skip add tool_indexation and tool_transcription when in resource section
					if( /rsc/i.test(page_globals.top_tipo) && (/tool_indexation/i.test(tool_name) ) ) { // || /tool_transcription/i.test(tool_name)
						continue;
					}
					// HTML TO INSPECTOR
					// Container tool div
					html_string += inspector.build_tool_button({'tool_name'		: tool_name,
																'label' 		: label,
																'title' 		: title,
																'tipo' 			: tipo,
																'parent' 		: current_parent,
																'section_tipo'  : section_tipo,
																'lang'  		: lang,
																'context_name'	: context_name
																})					
					/*
					html_string +='<div class="tool_inspector_div">';
						// Icon div				
						html_string += '<div class="icon_bs '+tool_name+'_icon link"\
							data-tipo="'+tipo+'" data-parent="'+parent+'" data-section_tipo="'+section_tipo+'" data-lang="'+lang+'" data-tab_id="img_lang_'+tipo+'_'+parent+'" data-context_name="'+context_name+'"\
							title="'+title+'"\
							onclick="tool_common.open_'+tool_name+'(this)"></div>';
						// Label
						html_string += label;
					html_string += '</div>';
					*/

				}//end for
				target_obj.innerHTML = html_string
			}//end if (ar_tools_name.length>0) {

		}catch(err) {
			if (DEBUG) {
				console.log(err)
			}
		}
		return

	}//end load_inspector_tools


	this.build_tool_button = function( vars ) {

		var html_string = ''

		html_string +='<div class="tool_inspector_div">'

			//<div id="button_difussion_export_record" class="icon_bs tool_diffusion tool_diffusion_inspector link" data-options="{&quot;section_tipo&quot;:&quot;rsc170&quot;,&quot;mode&quot;:&quot;export_record&quot;}" onclick="tool_diffusion.export_record(this,'')">Publicar </div>
	
			// div				
			html_string += '<div class="link"\
				data-tipo="'+vars.tipo+'"\
				data-parent="'+vars.parent+'"\
				data-section_tipo="'+vars.section_tipo+'"\
				data-lang="'+vars.lang+'"\
				data-tab_id="img_lang_'+vars.tipo+'_'+vars.parent+'"\
				data-context_name="'+vars.context_name+'"\
				title="'+vars.title+'"\
				onclick="tool_common.open_'+vars.tool_name+'(this)">'

					// Icon div
					html_string += '<div class="icon_bs '+vars.tool_name+'_icon"></div>'

					// Label
					html_string += vars.label

			html_string += '</div>'

		html_string += '</div>'

		return html_string
	}
	


	/**
	* TRIGGER_URL
	*/
	this.trigger_url = function(url, button_obj) {
		
		html_page.loading_content( button_obj, 1 )

		// AJAX
		$.ajax({
			url			: url,
			data		: {},
			type		: 'GET'
		})
		.done(function(received_data) {
			//console.log(received_data);
			var msg = "Updated \n";
			if (DEBUG) {
				msg += "<hr>DEBUG INFO [received_data]: \n"+received_data
			};
			alert(msg);
		})
		.fail(function(error_data) {
			alert("Error \n\n" + error_data);
		})
		.always(function() {
			html_page.loading_content( button_obj, 0 )																			
		});
	}


}// end inspector






