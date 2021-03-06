


/**
* COMPONENT PORTAL CLASS
*/
var component_portal = new function() {

	this.portal_objects 		 = []
	this.url_trigger 			 = DEDALO_LIB_BASE_URL + '/component_portal/trigger.component_portal.php'
	this.save_arguments 		 = {} // End save_arguments
	// Fixed when user click on delete icon (open_delete_dialog)
	this.delete_obj 			 = null;
	this.delete_dialog_portal_id = "delete_dialog_portal"

	// Active button obj
	this.active_delete_button_obj = null


	// Window load
	window.addEventListener("load", function (event) {

		// Add delete confirmation dialog text once
		component_portal.create_dialog_div()
	})

	

	// DOM ready
	$(function() {
		switch(page_globals.modo) {			
			case 'tool_time_machine' :
					component_portal.hide_buttons_and_tr_edit_content();
					break;
			case 'tool_lang' :
					break;
			case 'edit' :
					break;
		}		
	});//end $(function()



	/**
	* CREATE_DIALOG_DIV
	* Add delete confirmation dialog text
	*/
	this.create_dialog_div = function() {
		
		var delete_dialog = this.build_delete_dialog({
			delete_dialog_portal_id : this.delete_dialog_portal_id
		})
		document.body.appendChild(delete_dialog);
	};//end create_dialog_div
	


	/**
	* BUILD_DELETE_DIALOG
	* @return DOM object modal_dialog
	*/
	this.build_delete_dialog = function(options) {

		var header = document.createElement("div")
			// h4 <h4 class="modal-title">Modal title</h4>
			var h4 = document.createElement("h4")
				h4.classList.add('modal-title')
				t = document.createTextNode(get_label.esta_seguro_de_borrar_este_registro)
				// Add
				h4.appendChild(t)
				header.appendChild(h4)

		var body = document.createElement("div")
			var t = document.createTextNode("Loading..")
				// add
				body.appendChild(t)

		var footer = document.createElement("div")

			// <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			var button_cancel = document.createElement("button")
				button_cancel.classList.add("btn","btn-default")
				button_cancel.dataset.dismiss = "modal"
				var t = document.createTextNode(get_label.cancelar)
				// add
				button_cancel.appendChild(t)
				// add
				footer.appendChild(button_cancel)

			// <button type="button" class="btn btn-primary">Save changes</button>
			var button_delete_data = document.createElement("button")
				button_delete_data.classList.add("btn","btn-primary")
				button_delete_data.dataset.dismiss = "modal"
				var t = document.createTextNode(get_label.borrar_solo_el_vinculo)
				// add
				button_delete_data.appendChild(t)
				button_delete_data.addEventListener("click", function (e) {
					//div_note_wrapper.parentNode.removeChild(div_note_wrapper)					
					component_portal.remove_locator_from_portal(component_portal.delete_obj,'delete_link')
				})
				// add
				footer.appendChild(button_delete_data)

			// <button type="button" class="btn btn-primary">Save changes</button>
			var button_delete_record = document.createElement("button")
				button_delete_record.classList.add("btn","btn-primary")
				button_delete_record.dataset.dismiss = "modal"
				var t = document.createTextNode(get_label.borrar_el_recurso)
				// add
				button_delete_record.appendChild(t)
				button_delete_record.addEventListener("click", function (e) {
					//div_note_wrapper.parentNode.removeChild(div_note_wrapper)
					component_portal.remove_resource_from_portal(component_portal.delete_obj,'delete_all')
				})
				// add
				footer.appendChild(button_delete_record)		


		// modal dialog
		var modal_dialog = common.build_modal_dialog({
			id 		: options.delete_dialog_portal_id,
			header 	: header,
			body 	: body,
			footer 	: footer
		})
		

		return modal_dialog
	};//end build_delete_dialog



	/**
	* OPEN_DELETE_DIALOG
	* @param object button_obj
	*/
	this.open_delete_dialog = function(button_obj) {

		// Fix selected button as selected
		component_portal.delete_obj = button_obj

		// Get portal name
		// From component wrapper
		var wrap_div = find_ancestor(button_obj, 'wrap_component')
			if (wrap_div === null ) {
				if(DEBUG) console.log(button_obj);
				return alert("component_portal:open_delete_dialog: Sorry: portal wrap_div dom element not found")
			}
		var label = wrap_div.querySelector("label.css_label").textContent || null


		// Add dialog id info to dialog body
		var rel_locator = JSON.parse(button_obj.dataset.rel_locator)
		var ref_id = rel_locator.section_id
		if (typeof rel_locator.tag_id!=="undefined") {
			ref_id += "-"+rel_locator.tag_id
		}
		var delete_dialog = document.getElementById(this.delete_dialog_portal_id)
		var modal_body = delete_dialog.querySelector(".modal-body")
			modal_body.innerHTML = label + ". " + get_label.registro + " ID: " + ref_id		
		
		// Open dialog
		$('#'+this.delete_dialog_portal_id).modal({
			show 	 : true,
			keyboard : true
		})

		return true		
	};//end open_delete_dialog



	/**
	* ACTIVE_PORTAL_TABLE_SORTABLE : SORT TR RECORDS 
	*/
	this.active_portal_table_sortable = function(table_id, dragable_connectWith) {

		// console.log("dragable_connectWith: "+dragable_connectWith);
		// Helper functions
		var fixHelperModified = function(e, tr) {
			var $originals = tr.children()
			var $helper = tr.clone()
			$helper.children().each(function(index) {
			        $(this).width($originals.eq(index).width())
			    });
		    return $helper
			}
		var updateIndex = function(e, ui) {
			    $('td.index', ui.item.parent()).each(function (i) {
			        $(this).html(i + 1)
			    });
			}
		var draggin_start = function(event, ui) {
				var current_item = ui.item
				// console.log(ui.item[0])
			}

		// Table sortable
		$('#'+table_id + ' .tbody').sortable({
			//axis: 'y',
			//containment: '#'+table_id, // Constrin moviment inside table body (parent)
			//start: 	draggin_start,
		    helper 		: fixHelperModified,
		    stop 		: updateIndex,		    
			//connectWith : '#'+dragable_connectWith + ' .tbody',		    		    
		    update 		: function(event, ui) {
				component_portal.save_order(this);
			},
			activate: function( event, ui ) {

			}
		}).disableSelection();

		// Add dragable_connectWith like '"portal_table_".$propiedades->dragable_connectWith'
		if (dragable_connectWith.length>13) {
			$('#'+table_id + ' .tbody').sortable( "option", "connectWith", '#'+dragable_connectWith + ' .tbody' )
		}
	};//end active_portal_table_sortable


	
	/**
	* SAVE_ORDER
	*/
	this.save_order = function( table_object ) {
		
		var ar_final		= []
		var ar_elements_dato	= table_object.querySelectorAll('.portal_element_sortable')
				//console.log(ar_elements_dato);

			// Component wrap
			var component_wrap = component_common.get_wrapper_from_element(table_object)
				if (!component_wrap) return alert("Error on select component wrap")
				var id_wrapper = component_wrap.id
				//console.log(component_wrap);			
		
		var d_len = ar_elements_dato.length 
		for (var i = 0; i < d_len; i++) {
			var dato = JSON.parse(ar_elements_dato[i].dataset.dato)			
			if (typeof dato==='undefined' || typeof dato.section_id==='undefined' || dato.section_id.length<1) {
				console.log("Error: tr bad format in list !!!! Please fix this ASAP.")
				return alert("Error save_order. Sort rows unavailable")
			}else{
				ar_final.push(dato)
			}			
		}
		var dato = JSON.stringify(ar_final)
			//console.log(dato)

		var mydata	= { 'mode' 		 	: 'save_order',
						'portal_tipo' 	: component_wrap.dataset.tipo,
						'portal_parent' : component_wrap.dataset.parent,
						'section_tipo' 	: page_globals.section_tipo,
						'dato' 			: dato,
						'top_tipo' 		: page_globals.top_tipo,
					}
					//return console.log(mydata);

		html_page.loading_content( component_wrap, 1 );

		// AJAX CALL
		$.ajax({
			url	 : this.url_trigger,
			data : mydata,
			type : "POST",
		})
		// DONE
		.done(function(data_response) {

			// If data_response contain 'error' show alert error with (data_response) else reload the page
			if(/error/i.test(data_response)) {
				// Alert error
				alert("[save_order] Request failed: \n" + data_response );
			}else{
				// Espected value string ok
				if(data_response=='ok') {			
					
					// Reload component by ajax (Recommended)
					component_common.load_component_by_wrapper_id(id_wrapper); // ,null,component_portal.active_portal_drag component_portal.active_portal_drag();wrapper_id, arguments, callback

					// Notify to inspector
					top.inspector.show_log_msg("<span class='ok'>Changed order</span>");	
				
				}else{
					alert("[save_order] Warning: " + data_response, 'Warning');
				}
			}
			if (DEBUG) console.log("->save_order: " + data_response)
		})
		.fail( function(jqXHR, textStatus) {
			// log
			top.inspector.show_log_msg( "<span class='error'>Error on " + getFunctionName() + " save_order</span>" + textStatus );
		})
		.always(function() {
			html_page.loading_content( component_wrap, 0 );
		})
	};//end save_order



	/**
	* NEW PORTAL RECORD
	* Create new section record and add to current portal
	*/
	this.new_portal_record = function (button_obj) {
		
		//var portal_id 		= $(button_obj).data('portal_id')
		var	portal_tipo 		= button_obj.dataset.portal_tipo
		var portal_parent		= button_obj.dataset.portal_parent
		var portal_section_tipo = button_obj.dataset.portal_section_tipo
		var	target_section_tipo	= button_obj.dataset.target_section_tipo

		// Test mandatory vars
		if (typeof target_section_tipo=='undefined' || target_section_tipo.length<3)
			return alert("new_portal_record Error: target_section_tipo is empty! \n Nothing is done.")

		// Component wrap
		var component_wrap = component_common.get_wrapper_from_element(button_obj)
			if (!component_wrap) return alert("Error on select component wrap")
			var id_wrapper = component_wrap.id
			//console.log(component_wrap)

		var component_info = JSON.parse(component_wrap.dataset.component_info);
		var propiedades    = component_info.propiedades 

			//console.log(propiedades.portal_link_open);			return;

		var mydata	= {
					'mode'				  	: 'new_portal_record',
					'portal_tipo'		  	: portal_tipo,
					'portal_parent' 	  	: portal_parent,
					'portal_section_tipo' 	: portal_section_tipo,
					'target_section_tipo' 	: target_section_tipo,
					'top_tipo' 				: page_globals.top_tipo,
					'top_id' 				: page_globals.top_id,
				}
				//return console.log(mydata);

		html_page.loading_content( component_wrap, 1 )

		// AJAX REQUEST
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type 	: "POST"
		})
		// ALWAYS
		.always(function() {
			html_page.loading_content( component_wrap, 0 )
		})
		// DONE
		.done(function(received_data) {

			// Response new 'id_matrix' expected
			if ($.isNumeric(received_data) && received_data>0) {

				// Notification msg ok
				var msg = "<span class='ok'>New portal record: " + received_data + "</span>";
					inspector.show_log_msg(msg);

				var created_record_id = parseInt(received_data);

			}else{
				// Warning msg
				var msg = "<span class='warning'>Warning on create new_portal_record: \n" + received_data +"</span>. Please, reload page manually for update list" ;
					inspector.show_log_msg(msg);
					alert( msg.innerText || msg.textContent )
			}

			// Reload component and open new created record from tool
			if (created_record_id) {

				// Updating component portal way
				var jsPromise = component_common.load_component_by_wrapper_id( id_wrapper );
					jsPromise.then(function(response) {
					  	//console.log(response);
					  	// Open window to edit new record created
					  	var element_id 		 = 'portal_link_open_'+portal_section_tipo+'_'+created_record_id

					  	if (typeof propiedades.portal_link_open != "undefined" && propiedades.portal_link_open===false) {
					  		// Not open new window
					  	}else{
					  		// Open new window clicking new record edit record
						  	var portal_link_open = document.getElementById(element_id);
							if (portal_link_open) {
								portal_link_open.click()
							}else{
								alert("[new_portal_record] Error on locate element after reload component. element_id: "+element_id)
							}
					  	}
						
					}, function(xhrObj) {
					  	console.log(xhrObj);
					});//end jsPromise				
				
			}//if (created_record_id) {			
			
		})
		// FAIL ERROR 
		.fail(function(error_data) {
			// Notify to log messages in top of page
			var msg = "<span class='error'>ERROR: on new_portal_record data portal_parent:" + portal_parent + "<br>Data is NOT saved!</span>";				
			inspector.show_log_msg(msg);
			if (DEBUG) console.log(error_data);
		})		
	};//end new_portal_record



	/**
	* REMOVE RESOURCE FROM PORTAL
	*/
	this.remove_locator_from_portal = function (button_obj) {
		
		// Component wrap
		var component_wrap = component_common.get_wrapper_from_element(button_obj);
			if (!component_wrap) return alert("Error on select component wrap");
			var id_wrapper = component_wrap.id
			//console.log(component_wrap);

		var mydata	= { 'mode' 		   	: 'remove_locator_from_portal',
						'portal_tipo'  	: component_wrap.dataset.tipo,
						'portal_parent' : component_wrap.dataset.parent,
						'section_tipo'	: component_wrap.dataset.section_tipo,
						'rel_locator' 	: button_obj.dataset.rel_locator,
						'top_tipo'		: page_globals.top_tipo
					  }
					  //return console.log(mydata);	

		var locator_obj = JSON.parse(button_obj.dataset.rel_locator) || null;
		var locator_section_id = (typeof locator_obj.section_id!='undefined' ) ? locator_obj.section_id : null;		

		html_page.loading_content( component_wrap, 1 );
	
		// AJAX CALL
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type	: "POST"
		})
		// DONE
		.done(function(data_response) {

			// If data_response contain 'error' show alert error with (data_response) else reload the page
			if(/error/i.test(data_response)) {
				// Alert error
				alert("[remove_locator_from_portal] Request failed: \n" + data_response );
			}else{
				// Espected value string ok
				if(data_response=='ok') {				
					
					// Reload component by ajax
					//component_common.load_component_by_wrapper_id(id_wrapper);

					// Notify to inspector
					top.inspector.show_log_msg("<span class='ok'>Removed portal locator "+locator_section_id+"</span>");	
				
				}else{
					alert("[remove_locator_from_portal] Warning: " + data_response, 'Warning');
				}
			}
			//if (DEBUG) console.log("->remove_locator_from_portal: " + data_response)

			// Reload component by ajax
			component_common.load_component_by_wrapper_id(id_wrapper);	
		})
		.fail( function(jqXHR, textStatus) {
			// log
			top.inspector.show_log_msg( "<span class='error'>Error on " + getFunctionName() + " [rel_locator] " + vars.rel_locator + "</span>" + textStatus );
		})
		.always(function() {			
			html_page.loading_content( component_wrap, 0 );
		})
	};// /this.remove_locator_from_portal



	/**
	* REMOVE RESOURCE FROM PORTAL
	*/
	this.remove_resource_from_portal = function (button_obj, action) {
		
		// Component wrap
		var $button_obj 	= $(button_obj),
			$component_wrap = $button_obj.parents('.css_wrap_portal').first()
			if ($component_wrap.length!==1) return alert("Error on select component wrap")

		// Component wrap
		var component_wrap = component_common.get_wrapper_from_element(button_obj)
			if (!component_wrap) return alert("Error on select component wrap")
			var id_wrapper = component_wrap.id
			//console.log(component_wrap);

		// Confirm action
		if( !confirm(get_label.seguro) )  return false;	

		var mydata = { 'mode'			: 'remove_resource_from_portal',
						'portal_tipo'	: component_wrap.dataset.tipo,
						'portal_parent' : component_wrap.dataset.parent,
						'section_tipo'	: component_wrap.dataset.section_tipo,
						'rel_locator'	: button_obj.dataset.rel_locator,
						'top_tipo'		: page_globals.top_tipo
					}
					//return console.log(mydata);

		var locator_obj = JSON.parse(button_obj.dataset.rel_locator) || null;
		var locator_section_id = (typeof locator_obj.section_id!='undefined' ) ? locator_obj.section_id : null;
			

		html_page.loading_content( component_wrap, 1 );
	
		// AJAX CALL
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type	: "POST"
		})
		// DONE
		.done(function(data_response) {

			// If data_response contain 'error' show alert error with (data_response) else reload the page
			if(/error/i.test(data_response)) {
				// Alert error
				alert("[remove_resource_from_portal] Request failed: \n" + data_response );
			}else{
				// Espected value string ok							
						
				// Reload component by ajax
				//component_common.load_component_by_wrapper_id(id_wrapper);

				// Notify to inspector
				top.inspector.show_log_msg("<span class='ok'>Removed completely resource "+locator_section_id+"</span>");				
				
			}
			//if (DEBUG) console.log("->remove_resource_from_portal: " + data_response)	

			// Reload component by ajax
			component_common.load_component_by_wrapper_id(id_wrapper);	
				
		})
		.fail( function(jqXHR, textStatus) {					
			// log
			top.inspector.show_log_msg( "<span class='error'>Error on " + getFunctionName() + " [locator_section_id] " + locator_section_id + "</span>" + textStatus );
		})
		.always(function() {				
			html_page.loading_content( component_wrap, 0 );																							
		})
	};// /this.remove_resource_from_portal



	/**
	* HIDE BUTTONS AND CONTENT
	* usado en los listados de time machine
	*/
	this.hide_buttons_and_tr_edit_content = function() {
		$(function(){	
			//$('.section_edit_in_portal_content, .btn_new_ep, .css_button_delete').hide(0);
			$('.delete_portal_link, .th_large_icon, TR[class*="portal_tr_edit_"]').remove(0);
		});
	};



	/**
	* PORTAL TAP STATE UPDATE
	* Update only specific style of row portal tabs. NOT affect if show or hide content (controled by: html_page.taps_state_update)
	*/
	this.portal_tab_style_update__DEPRECATED = function() {
		
		$(function() {
			/**/
			//alert("called portal_tab_style_update")

			// TAP OBJ SELECTOR
			var portal_tab_title_obj = $('.section_edit_in_portal_td').children('.tab_title');

			// INITIAL ITERATION TO SHOW / HIDE TAPS	
			portal_tab_title_obj.each(function() {
				
				var tab_id = $(this).data('tab_id');					//alert( tab_id );	
				if(tab_id != null) {
					var tab_value	= get_localStorage(tab_id);			//alert("tab_value:"+tab_value)

					// TOOGLE DIV IF EXISTS COOKIE
					if(tab_value != 1) {
						component_portal.open_row_edit( $(this) );		//alert("tab_value is 1. open row "+tab_id)
					}
				}
				//$(this).next('.tab_content').toggle(0);
			});
			
		});
	};
	


	/**
	* OPEN_TR_EDIT
	*/
	this.open_tr_edit__DEPRECATED = function (button_obj) {	
		
		// vars
		var vars = new Object();
			vars.portal_tr_edit = $(button_obj).data('portal_tr_edit'),
			vars.portal_tr_list = $(button_obj).data('portal_tr_list'),
			vars.id_wrapper		= $(button_obj).data('id_wrapper'),
			vars.common_row_id	= $(button_obj).data('common_row_id');
		// Verify vars values
		if(!test_object_vars(vars, 'open_tr_edit')) return false;

		// ROW EDIT : current html lengh
		var current_html_lenght = $('.portal_tr_edit_'+vars.common_row_id).find('.css_section_content').html().length ;

		// Close and clean all common rows edit
		// ROW EDIT : Hide
		$('.portal_tr_edit_'+vars.common_row_id).css('display','none');
		// ROW EDIT : Empty content html
		$('.portal_tr_edit_'+vars.common_row_id).find('.css_section_content').html('<div class=\"portal_loading_msg\">Loading..</div>');
		/*
		$('.portal_tr_edit_'+vars.common_row_id).find('.css_section_content').each(function() {					  
		  $(this).css('display','none');
		  $(this).html('Loading..');		
		});
		*/

		// ROW LIST : Show all common rows list
		$('.portal_tr_list_'+vars.common_row_id).css('display','table-row');
			
		$(function() {
			// Ajax load section
			if($('#'+vars.id_wrapper).length != 1) return alert("Error: wrong target_row_id:"+vars.id_wrapper+" n:"+$('#'+vars.id_wrapper).length);

			//if (current_html_lenght<100) {}
			component_common.load_section_by_ajax(vars.id_wrapper);	//, arguments, callback
						
			//console.log("called function")
		});//$(function()
		
		// ROW LIST : Hide current tr list
		$('#'+vars.portal_tr_list).css('display','none');

		// ROW EDIT : Show current tr edit		
		$('#'+vars.portal_tr_edit).css('display','table-row');			
	};//end open_tr_edit__DEPRECATED



	/**
	* CLOSE_TR_EDIT
	*/
	this.close_tr_edit__DEPRECATED = function (button_obj) {
		
		// vars
		var vars = new Object();
			vars.portal_tr_edit = $(button_obj).data('portal_tr_edit'),
			vars.portal_tr_list = $(button_obj).data('portal_tr_list');
		// Verify vars values
		if(!test_object_vars(vars, 'close_tr_edit')) return false;

		// ROW EDIT : Close curent tr edit
		$('#'+vars.portal_tr_edit).css('display','none');//.find('.css_section_content').html('')

		// ROW LIST : Open current tr list
		$('#'+vars.portal_tr_list).css('display','table-row');
	};//end close_tr_edit__DEPRECATED

	

	/**
	* TOGGLE_MORE_ELEMENTS_CONTENT
	* Despliega los elementos plegados en los listados
	*/
	this.toggle_more_elements_content = function (button_obj) {

		if ( $(button_obj).hasClass('portal_toggle_more_elements_minus') ) {
			// Is opened. Closing
			$(button_obj).parents('.row_portal_inside').first().find('.portal_more_elements_content').fadeOut(300)
			$(button_obj).removeClass('portal_toggle_more_elements_minus')

		}else{
			// Is close. Opening
			$(button_obj).parents('.row_portal_inside').first().find('.portal_more_elements_content').fadeIn(300)
			$(button_obj).addClass('portal_toggle_more_elements_minus')
		}
	};//end toggle_more_elements_content



	/**
	* SHOW_MORE
	* Used in list to view more than first element of portal
	*/
	this.show_more_open = {}
	this.show_more = function( button_obj ) {

		var target 			= document.getElementById(button_obj.dataset.target),
			portal_tipo 	= button_obj.dataset.tipo,
			portal_parent 	= button_obj.dataset.parent,
			open_key 		= portal_tipo+'_'+portal_parent

		// component_portal.show_more_open[open_key] = false
		// console.log(component_portal.show_more_open);

		if (component_portal.show_more_open[open_key]) {

			if(target.style.display!='none') {
				target.style.display = 'none'	// Hide target
				button_obj.innerHTML = get_label.mostrar + ' + '
			}else{
				target.style.display = 'block'	// Show already loaded target
				button_obj.innerHTML = get_label.mostrar + ' - '
			}	
			//return false;
			//return component_portal.show_more_open[open_key]
		}

		var mydata = {
				"mode" 		   : 'show_more',
				"portal_tipo"  : portal_tipo,
				"portal_parent": portal_parent,
				"section_tipo" : button_obj.dataset.section_tipo,
				}
				//return console.log(mydata);

		target.innerHTML = " Loading.. "

		// AJAX REQUEST
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type 	: "POST"
		})
		// DONE
		.done(function(received_data) {

			target.innerHTML = received_data
			component_portal.show_more_open[open_key] = received_data;	//true
			button_obj.innerHTML = get_label.mostrar + ' - '			
		})
		// FAIL ERROR 
		.fail(function(error_data) {
			
		})
		// ALWAYS
		.always(function() {
					
		})
	};//end show_more	



	/**
	* OPEN_RECORD
	*/
	this.open_record = function( button_obj, url ) {
	
		var wrap_div = find_ancestor(button_obj, 'wrap_component')
			if (wrap_div === null ) {
				if(DEBUG) console.log(button_obj);
				return alert("component_portal:open_record: Sorry: wrap_div dom element not found")
			}			
			// Set portal as component to refresh
			html_page.add_component_to_refresh( wrap_div.id )

		var window_url	= url,
			window_name	= "Edit "+ encodeURIComponent(url),
			w_width		= screen.width,	
			w_height	= screen.height

		//window.location = window_url; return

		var edit_window = window.open(window_url,window_name,'status=yes,scrollbars=yes,resizable=yes,width='+w_width+',height='+w_height)
			if(edit_window) edit_window.focus()			
			
		/*
		if ( /context/.test(url) ) {
			// Open and focus window
			var edit_window = window.open(window_url,window_name,'status=yes,scrollbars=yes,resizable=yes,width='+w_width+',height='+w_height);
				edit_window.focus()
			
		}else{
			//window.location = window_url;
			var edit_window = window.location.href = url;			
		}
		*/

		// Onclose edit record window, main window will be reloaded to update contents
		/* NOT NECESSRY ANYMORE
		edit_window.onbeforeunload = function () {
		   edit_window.location.reload();
		}
		*/

		return false;		
	};//end open_record	


	
	/**
	* SELECT_COMPONENT
	* Overwrite common method
	* @param object obj_wrap
	*/
	this.select_component = function(obj_wrap) {
		//e.stopPropagation();
		obj_wrap.classList.add("selected_wrap");
		//$(obj_wrap).find('a').first().focus();
	};



	this.toggle_views = function(event){
		
		console.log(event);
	};



	







}//end class