





var component_state = new function() {	

	// URL TRIGGER
	this.url_trigger = DEDALO_LIB_BASE_URL + '/component_state/trigger.component_state.php';
	this.ar_charts   = []
	


	/**
	* SAVE	
	* @param object component_obj
	*/
	this.Save = function(component_obj) {

		var checked		= component_obj.checked,		
			options		= component_obj.dataset.options,
			source_dato	= component_obj.dataset.dato;			

			if(checked===true) {
				var valor = 1;			
			}else{
				var valor = 0;					
			}
			
			//var dato = this.update_dato(options, source_dato, valor);
			//console.log(dato);return;

		// On send 'dato' as argument, overwrite default common_get_dato
		this.save_arguments = {	
								"dato" : JSON.stringify(dato)
							  }
							  //return console.log(dato)

		// Exec general save
		var jsPromise = component_common.Save(component_obj, this.save_arguments);
	};//end Save



	this.get_dato = function() {
		// Working here
	};//end get_dato
	


	/**
	* UPDATE_STATE_LOCATOR
	*/
	this.update_state_locator = function(input_obj){
	
		var id_wrapper 	= input_obj.dataset.id_wrapper,
			target_obj  = document.getElementById( id_wrapper ),
			tipo 		= target_obj.dataset.tipo,
			parent 		= target_obj.dataset.parent,
			modo 		= target_obj.dataset.modo,
			lang 		= target_obj.dataset.lang,
			label 		= target_obj.dataset.label,
			section_tipo= target_obj.dataset.section_tipo,
			top_tipo 	= page_globals.top_tipo,
			options 	= input_obj.dataset.options,
			type 		= input_obj.dataset.type,			
			elements	= input_obj.parentNode.getElementsByTagName('input'),			
			value 		= 0

			//return console.log(target_obj.dataset.options);
			//return 	console.log(target_obj);
			//var target_obj = $('#'+input_obj.dataset.id_wrapper)

			if(input_obj.dataset.type === 'user' && input_obj.checked) {
				value = 100;
			}else if(input_obj.dataset.type === 'admin') {
				value = parseInt(input_obj.value);
			}		

			var mydata	= { 'mode'		  : 'update_state_locator',
							'tipo'		  : tipo,
							'parent'	  : parent,
							'modo'		  : modo,
							'lang'		  : lang,
							'section_tipo': section_tipo,
							'top_tipo'	  : top_tipo,
							'options'	  : options,
							'type' 		  : type,
							'dato' 		  : value
						};
						//if(DEBUG) console.log(mydata); return

			html_page.loading_content( target_obj, 1 );

			// AJAX CALL
			$.ajax({
				url			: this.url_trigger,
				data		: mydata,
				type		: "POST",
			})
			// DONE
			.done(function(data_response) {

				// If data_response contain 'error' show alert error with (data_response) else reload the page
				if(/error/i.test(data_response)) {
					// Alert error
					alert("[update_state_locator] Request failed: \n" + data_response );
				}else{
					
					// Notify to inspector
					//top.inspector.show_log_msg("<span class='ok'>Changed order</span>");					
				}
				//return console.log(id_wrapper);
				
				// Reload component id_wrapper
				// WARNING : NOT USE RELOAD COMPONENT METHODS LIKE 'component_common.load_component_by_wrapper_id' TO UPDATE CURRENT 
				// COMPONENT IN 'edit_component' MODE. Component in 'edit_component' mode is called only by related component (normally text area)	
				if (modo==='edit') {

					var jsPromise = component_common.load_component_by_wrapper_id(id_wrapper);
					// Update graph
					jsPromise.then(function(response) {
					  	//console.log(response);
					  	component_state.toggle_graphs()
					}, function(xhrObj) {
					  	console.log(xhrObj);
					})
										
				}
				//console.log(modo);

				
				// INSPECTOR LOG INFO			
				if (data_response.indexOf("Error")!=-1 || data_response.indexOf("error")!=-1 || data_response.indexOf("Failed")!=-1) {
					var msg = "<span class='error'>Failed Save!<br>" +data_response+ " for " + label + "</span>";
				}else{
					var msg = "<span class='ok'>" + label + ' ' + get_label.guardado +"</span>";
				}
				inspector.show_log_msg(msg);




				if (DEBUG) console.log("->update_state_locator: " + data_response)
			})
			.fail( function(jqXHR, textStatus) {
				// log
				top.inspector.show_log_msg( "<span class='error'>Error on " + getFunctionName() + " update_state_locator</span>" + textStatus );
			})
			.always(function() {
				html_page.loading_content( target_obj, 0 );
			})			
	};//update_state_locator



	this.update_dato_DEPRECATED = function(options, source_dato, valor){

		var options 	= JSON.parse(options),
			source_dato = JSON.parse(source_dato).length != undefined ? JSON.parse(source_dato) : []
			/*
			source_dato = [{
				"section_tipo": "oh1",
		        "section_id": 1,
		        "component_tipo": "oh15",
		        "state": {
		            "lg-spa": {
		                "tool_transcription": 1,
		                "tool_indexation": 0,
		            },
		            "lg-cat": {
		                "tool_transcription": 1,
		                "tool_indexation": 0,
		                "tool_lang": 1
		            }
		        }
			}]
			*/	
			/*
			source_dato = [{
					"section_tipo": "oh1",
			        "section_id": 1,
			        "component_tipo": "oh15",
			        "state": {
			            "lg-spa": {
			                "tool_transcription": 1,
			                "tool_indexation": 0,
			            },
			            "lg-cat": {
			                "tool_transcription": 1,
			                "tool_indexation": 0,
			                "tool_lang": 1
			            }
			        }
				},
				{
					"section_tipo": "rsc167",
			        "section_id": 15,
			        "component_tipo": "rsc19",
			        "state": {
			            "lg-spa": {
			                "tool_transcription": 1,
			                "tool_indexation": 0,
			            },
			            "lg-cat": {
			                "tool_transcription": 1,
			                "tool_indexation": 0,
			                "tool_lang": 1
			            },
			            "lg-nolan": { "tool_indexation": 0, "tool_transcription": 0}
			        }
				}]			
			*/
		var tool_locator = {}
			tool_locator[options.tool_locator] = valor
		var lang = {}
			lang[options.lang] = tool_locator		

		var s_len = source_dato.length
		for (var i = s_len - 1; i >= 0; i--) {
			
			if(source_dato[i].section_tipo == options.section_tipo &&
				source_dato[i].section_id == options.section_id &&
				source_dato[i].component_tipo == options.component_tipo) 
				{
					if ( !source_dato[i].state.hasOwnProperty([options.lang]) ) {						
						source_dato[i].state[options.lang] = tool_locator						
						return source_dato
					}
					//console.log( !source_dato[i].state[options.lang].hasOwnProperty([options.tool_locator]) );
					if ( !source_dato[i].state[options.lang].hasOwnProperty([options.tool_locator]) ) {
						source_dato[i].state[options.lang][options.tool_locator] = valor						
						return source_dato
					}
					
					source_dato[i].state[options.lang][options.tool_locator] = valor;									
					return source_dato
			}
		};
		
		// If not match, add curent dato
		source_dato.push({
			"section_tipo": options.section_tipo,
			"section_id": options.section_id,
			"component_tipo": options.component_tipo,
			"state" : lang	
			})
		

		return source_dato;
	};//end update_dato_DEPRECATED



	/**
	* BUILD_CHARTS
	* Build every stats charts from json object received
	* Example schema (JSON)
	[ 
	  {
	    key: 'Series1',
	    values: [
	      { 
	        "label" : "Group A" ,
	        "value" : -1.8746444827653
	      } , 
	      { 
	        "label" : "Group B" ,
	        "value" : -8.0961543492239
	      }	     
	    ]
	  }
	]
	*/
	this.build_charts = function (ar_value_stats_json, key) {
		//console.log(ar_value_stats_json);//return;		
		if(ar_value_stats_json===null || typeof ar_value_stats_json !== 'object') {
			if (DEBUG) { console.log("build_charts: Empty 'ar_value_stats_json' received"); };
			return false;
		}

		var chart_id			= 'char_'+ key,
			title_chart_text 	= ar_value_stats_json.title,
			graph_type 			= ar_value_stats_json.graph_type,
			current_data 		= ar_value_stats_json.data
						
		var new_div 			= $("<div class='wrap_stats_graphic' id='wrap_stats_"+key+"'/>"),
			title_div 			= '<div class="titulo_chart">'+title_chart_text+'</div>',
			svg_element 		= '<svg class="chart '+graph_type+'" id="'+ chart_id +'"></svg>',
			info_button 		= '<div class="icon_bs info_button" onclick="component_state.toggle_info(this)"></div>'
			 
			//$( "#stats_container" ).append( new_div );
			$( "#current_stats_item_"+key ).html('').prepend( new_div );
			//$( new_div ).append( title_div, svg_element, info_button );
			$( new_div ).append( title_div, svg_element );

		// Build specific chart	    	    
    	if (typeof component_state[graph_type] !== 'function') {
    		console.log("build_charts graph_type is invalid:" + graph_type);
    		if(DEBUG) {
    			alert("build_charts graph_type is invalid:" + graph_type)
    		}
    		return;
    	};
    	
    	component_state[graph_type]( current_data, chart_id );		
		
	    return true;
	};//end build_charts



	/*
	//Regular pie chart example
	nv.addGraph(function() {
	  var chart = nv.models.pieChart()
	      .x(function(d) { return d.label })
	      .y(function(d) { return d.value })
	      .showLabels(true);

	    d3.select("#chart svg")
	        .datum(exampleData())
	        .transition().duration(350)
	        .call(chart);

	  return chart;
	});
	*/

	/**
	* STATS_PIE
	* Schema sample : 
	[
	    {
	      key: "One",
	      y: 5
	    },
	    {
	      key: "Two",
	      y: 2
	    }
	]
	*/
	this.stats_pie = function(current_dato, chart_id) {
		//console.log(current_dato); return
		if (!current_dato) return;
		
	    var dato = current_dato[0].values;
		var chartOptions = {
			delay: 10
		}

		var chart_object = document.getElementById(chart_id);
		if (!chart_object) {
			return null;
		}		
		
		// Adjust height to current number of items/values
		var n = dato.length,
			//h = chart_object.offsetHeight
			h = chart_object.getBoundingClientRect()
			//console.log(h);
		chart_object.height = n*6

		// NV GRAPH
		var chart,
			width  = "100%",
		    height = "100%"

	    nv.addGraph(function() {		    

		    chart = nv.models.pieChart()
		        .x(function(d) { return d.x })
		        .y(function(d) { return d.y })	      
		        .color(d3.scale.category20().range())
		        .color(function(d){ if(d.data) return d.data.color })
		        //.color(function(d) { console.log(d); return [(d.x=='si' || d.data[x]=='si') ? 'green' : 'red']})
		        .width(width)
		        .height(height)
		        .showLegend(true)
		        .donut(false)
		        .pieLabelsOutside(true)
		        //.labelsOutside(true)
		        .labelThreshold(.025)
		        //.tooltips(true)
		        .options(chartOptions);		        
		        //.tooltipContent(function(a){ 
		        //			//console.log(d);
		        //			return a 
		        //		});		    	

		      d3.select("#"+chart_id)
		          .datum(dato)
		          .transition().duration(1200)
		          //.attr('width', width)
		          //.attr('height', height)
		          .call(chart);

		    nv.utils.windowResize(chart.update);
			//chart.dispatch.on('stateChange', function(e) { nv.log('New State:', JSON.stringify(e)); });

			return chart;
		});		
	};//end stats_pie



	this.toggle_graphs = function( button_object, show ) {

		if (typeof button_object=='undefined' || !button_object) {
			var button_object = document.querySelectorAll('[data-state_graphs_button]')[0];			
		}		

		var content_div = button_object.nextElementSibling;
		if (content_div.style.display==='none') {
			for (var i = 0; i < this.ar_charts.length; i++) {
				var key 	= this.ar_charts[i].key,
					graph 	= this.ar_charts[i].graph

				this.build_charts(graph, key)
			}
			button_object.nextElementSibling.style.display = 'block';
		}else{
			button_object.nextElementSibling.style.display = 'none';
		}

		if (show) {
			button_object.nextElementSibling.style.display = 'block';
		}		
	};//end toggle_graphs



	/*
	this.toggle_info = function(button) {
		var $current_stats_item_info = $(button).parents('.current_stats_item').first().children('.current_stats_item_info').first();
			//console.log(current_stats_item_info);
		$current_stats_item_info.fadeToggle(300);
	}
	*/



}//end component_state