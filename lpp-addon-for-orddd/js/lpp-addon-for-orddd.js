jQuery( document ).ready(function() { 
	if( 'undefined' != typeof( jQuery( "#is_pickup_location_enabled" ).val() ) ) {
        jQuery(document).on( "change", "input[name=\"pickup_location[0]\"]", function() {
            load_delivery_date();
            if ( jQuery( "#orddd_enable_autofill_of_delivery_date" ).val() == 'on' ) {
            	orddd_autofil_date_time();
            }
        });

        jQuery(document).on( "change", "select[name=\"pickup_location[0]\"]", function() {
            load_delivery_date();
            if ( jQuery( "#orddd_enable_autofill_of_delivery_date" ).val() == 'on' ) {
            	orddd_autofil_date_time();
            }
        });

        if( "yes" != jQuery( "#orddd_shipping_method_based_settings" ).val() ) {
            jQuery(document).on( "change", "input[name=\"shipping_method[0]\"]", function() {
                load_delivery_date();
            });
        
            jQuery(document).on( "change", "select[name=\"shipping_method[0]\"]", function() {
                load_delivery_date();
            });
            
        }

        //var old_pickup_location = jQuery( "#lpp_selected_pickup_location" ).val();
        jQuery(document).ajaxComplete( function( event, xhr, options ) {
	        if( options.url.indexOf( "wc-ajax=update_order_review" ) !== -1 ) {
	            if( xhr.statusText != "abort" ) {
                    if( jQuery( "#orddd_enable_shipping_based_delivery" ).val() == "on" ) {
    	                jQuery( "#e_deliverydate" ).datepicker( "option", "disabled", true );
    	                jQuery( "#time_slot" ).attr( "disabled", "disabled" );

    	                var shipping_method = jQuery( "input[name=\"shipping_method[0]\"]:checked" ).val();
    			        if( typeof shipping_method === "undefined" ) {
    			            var shipping_method = jQuery( "select[name=\"shipping_method[0]\"] option:selected" ).val();
    			        }
    			        if( typeof shipping_method === "undefined" ) {
    			            var shipping_method = jQuery( "input[name=\"shipping_method[0]\"]" ).val();                    
    			        }

            			if( shipping_method == "local_pickup_plus" ) {
            				if( typeof jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val() != "undefined" ) {
                        		var pickup_location = jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val();    
                    		} else if( typeof jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val() === "undefined" ) {
                        		if( typeof jQuery( "select[name=\"pickup_location[0]\"] option:selected" ).val() != "undefined" ) {
                            		var pickup_location = jQuery( "select[name=\"pickup_location[0]\"] option:selected" ).val();
                        		} else if( typeof jQuery( "input[name=\"pickup_location[0]\"]" ).val() != "undefined" ) {
                        			var pickup_location = jQuery( "input[name=\"pickup_location[0]\"]" ).val();
                    			}
                    		} 

    		                if( typeof pickup_location === "undefined" ) {
            		            var pickup_location = "";
                    		}

                    		if( jQuery( "#lpp_selected_pickup_location" ).val() != pickup_location ) {
            					jQuery( "#e_deliverydate" ).datepicker( "option", "disabled", false );    
    							jQuery( "#time_slot" ).removeAttr( "disabled", "disabled" );
            					load_delivery_date();
    	    					if ( jQuery( "#orddd_enable_autofill_of_delivery_date" ).val() == "on" ) {
                                	orddd_autofil_date_time();
                            	}
                            } else {
                                jQuery( "#e_deliverydate" ).datepicker( "option", "disabled", false );    
                                jQuery( "#time_slot" ).removeAttr( "disabled", "disabled" );
                            }
                            jQuery( "#lpp_selected_pickup_location" ).val( pickup_location );
            			} else {
            				jQuery( "#lpp_selected_pickup_location" ).val( "" );
            			}
                    }
	            }
	        }
	    });
	}
});	

function orddd_lpp_method_func( shipping_method ) {
	if( 'undefined' != typeof( jQuery( "#is_pickup_location_enabled" ).val() ) ) {
        if ( "1" == jQuery( "#orddd_is_account_page" ).val() ) {
            var shipping_method = jQuery( "#shipping_method" ).val();
            var str = "orddd_pickup_location";            
            if( shipping_method.indexOf( str ) !== -1 ) {
                var pickup_location = jQuery( "#orddd_hidden_location_str" ).val();
                jQuery( "#orddd_hidden_vars_str" ).val( pickup_location );
            } else {
                var pickup_location = jQuery( "#orddd_hidden_vars_custom_str" ).val();
                jQuery( "#orddd_hidden_vars_str" ).val( pickup_location );
            } 
        } else {
            if( shipping_method == 'local_pickup_plus' ) {
                if( jQuery( "#orddd_hidden_location_str" ).val() != '' ) {
                    if( typeof jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val() != "undefined" ) {
                        var shipping_method = "orddd_pickup_location_" + jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val();    
                    } else if( typeof jQuery( "input[name=\"pickup_location[0]\"]:checked" ).val() === "undefined" ) {
                        if( typeof jQuery( "select[name=\"pickup_location[0]\"] option:selected" ).val() != "undefined" ) {
                            var shipping_method = "orddd_pickup_location_" + jQuery( "select[name=\"pickup_location[0]\"] option:selected" ).val();
                        } else if( typeof jQuery( "input[name=\"pickup_location[0]\"]" ).val() != "undefined" ) {
                            var shipping_method = "orddd_pickup_location_" + jQuery( "input[name=\"pickup_location[0]\"]" ).val();
                        }
                    } 

                    if( typeof shipping_method === "undefined" ) {
                        var shipping_method = "";
                    }

                    if( typeof shipping_method != "undefined" && '' != shipping_method ) {
                        var pickup_location = jQuery( "#orddd_hidden_location_str" ).val();
                        jQuery( "#orddd_hidden_vars_str" ).val( pickup_location );
                    }
                    jQuery( "#orddd_pickup_location_selected" ).val( shipping_method );    
                }
            } else {
                var pickup_location = jQuery( "#orddd_hidden_vars_custom_str" ).val();
                jQuery( "#orddd_hidden_vars_str" ).val( pickup_location );
            }
        }        
    } else {
        var pickup_location = jQuery( "#orddd_hidden_vars_custom_str" ).val();
        jQuery( "#orddd_hidden_vars_str" ).val( pickup_location );
    }
    return shipping_method;
}

function lpp_shipping_methods( value, shipping_methods ) {
	if ( typeof value.orddd_pickup_locations !== "undefined" ) {
        var shipping_methods = value.orddd_pickup_locations.split(",");
    }
    return shipping_methods;
}