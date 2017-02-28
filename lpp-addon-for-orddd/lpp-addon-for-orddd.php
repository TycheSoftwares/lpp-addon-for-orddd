<?php
/*
Plugin Name: Local Pickup Plus Compatibility Addon
Plugin URI: https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/
Description: This plugin allows customers to add different delivery settings for different pickup addresses from WooCommerce Local Pickup Plus plugin. This plugin is an addon for <a href="https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/" target="_blank">Order Delivery Date Pro for WooCommerce</a> plugin.
Author: Tyche Softwares
Version: 1.0
Author URI: http://www.tychesoftwares.com/about
Contributor: Tyche Softwares, http://www.tychesoftwares.com/
*/

global $lpp_version;
$lpp_version = '1.0';

if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
    // load our custom updater if it doesn't already exist
    include( dirname( __FILE__ ) . '/plugin-updates/EDD_SL_Plugin_Updater.php' );
}

include_once( 'license.php' );

// retrieve our license key from the DB
$license_key = trim( get_option( 'lpp_sample_license_key' ) );
// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
// IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system
define( 'LPP_SL_STORE_URL', 'http://www.tychesoftwares.com/' ); 

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
// IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system
define( 'LPP_SL_ITEM_NAME', 'Local Pickup Plus Compatibility Addon for Order Delivery Date for WooCommerce Plugin' ); 
// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( LPP_SL_STORE_URL, __FILE__, array(
    'version'   => '6.3',       // current version number
    'license'   => $license_key,    // license key (used get_option above to retrieve from DB)
    'item_name' => LPP_SL_ITEM_NAME,  // name of this plugin
    'author'    => 'Ashok Rane'  // author of this plugin
)
);

class lpp_addon_for_orddd {
	public function __construct() {
        // Error notice
        //Check for Order Delivery Date Pro for WooCommerce and WooCommerce Local Pickup Plus plugin
        add_action( 'admin_init', array( &$this, 'lpp_check_if_plugin_active' ) );

        //License
        add_action( 'orddd_add_submenu', array( &$this, 'lpp_addon_for_orddd_menu' ) );
        add_action( 'admin_init', array( 'lpp_license', 'lpp_register_option' ) );
        add_action( 'admin_init', array( 'lpp_license', 'lpp_deactivate_license' ) );
        add_action( 'admin_init', array( 'lpp_license', 'lpp_activate_license' ) );

		add_action( 'orddd_after_custom_product_categories', array( &$this, 'orddd_after_custom_product_categories' ), 10, 1 );
        add_filter( 'orddd_save_custom_settings', array( &$this, 'orddd_save_custom_settings' ), 10, 2 );
        add_filter( 'is_pickup_location_selected', array( &$this, 'is_pickup_location_selected' ), 10, 2 );
        add_filter( 'orddd_shipping_settings_table_data', array( &$this, 'orddd_shipping_settings_table_data' ) );
        add_action( 'orddd_before_checkout_delivery_date', array( &$this, 'orddd_before_checkout_delivery_date' ) );       
        add_action( 'orddd_include_front_scripts', array( &$this, 'orddd_include_front_scripts' ) ); 
        add_filter( 'orddd_get_shipping_method', array( &$this, 'orddd_get_shipping_method' ), 10, 4 );
	}

    public function lpp_check_if_plugin_active() {
        if ( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) || !is_plugin_active( 'woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php' ) ) {
            if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                add_action( 'admin_notices', array( &$this, 'lpp_error_notice' ) );
                if ( isset( $_GET[ 'activate' ] ) ) {
                    unset( $_GET[ 'activate' ] );
                }
            }
        }
    }

    public function lpp_error_notice() {
        $class = 'notice notice-error';
        if( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) && !is_plugin_active( 'woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php' ) ) {
            $message = __( '<b>Local Pickup Plus Compatibility Addon</b> requires <b>Order Delivery Date Pro for WooCommerce</b> and <b>WooCommerce Local Pickup Plus</b> plugin installed and activate.', 'order-delivery-date' );
        } else if( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) ) {
            $message = __( '<b>Local Pickup Plus Compatibility Addon</b> requires <b>Order Delivery Date Pro for WooCommerce</b> plugin installed and activate.', 'order-delivery-date' );
        } else if( !is_plugin_active( 'woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php' ) ) {
            $message = __( '<b>Local Pickup Plus Compatibility Addon</b> requires <b>WooCommerce Local Pickup Plus</b> plugin installed and activate.', 'order-delivery-date' );
        }
        printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    }

    public function lpp_addon_for_orddd_menu() {
        $page = add_submenu_page( 'order_delivery_date', __( 'Activate Local Pickup Plus Compatibility Addon License', 'order-delivery-date' ), __( 'Activate Local Pickup Plus Compatibility Addon License', 'order-delivery-date' ), 'manage_woocommerce', 'lpp_license_page', array( 'lpp_license', 'lpp_sample_license_page' ) );
    }

	public function orddd_after_custom_product_categories( $option_key ) {
		$pickup_location_settings = get_option( 'woocommerce_local_pickup_plus_settings', true );
        if( isset( $pickup_location_settings[ 'enabled' ] ) && 'yes' == $pickup_location_settings[ 'enabled' ] ) {
            $pickup_location_enabled = '';
    		$pickup_locations_stored = array();
    		if ( ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'shipping_based' ) && ( isset( $_GET[ 'mode' ] ) && $_GET[ 'mode' ] == 'edit' ) ) {
                if( isset( $_GET[ 'row_id' ] ) ) {
                    $row_id = $_GET[ 'row_id' ];
                    $shipping_methods_arr = get_option( 'orddd_shipping_based_settings_' . $row_id );
                    if( isset( $shipping_methods_arr[ 'orddd_pickup_locations' ] ) ) {
                        $pickup_locations_stored = $shipping_methods_arr[ 'orddd_pickup_locations' ];
                    } 
                    
                    if( isset( $shipping_methods_arr[ 'delivery_settings_based_on' ][ 0 ] ) && $shipping_methods_arr[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {
                        $pickup_location_enabled = "checked";
                    }
                }
            }
            
            $pickup_locations = get_option( 'woocommerce_pickup_locations', true );
    		?>
    		<p>
                <label>
                    <input type="radio" name="orddd_shipping_based_settings_<?php echo $option_key; ?>[delivery_settings_based_on][]" value="orddd_pickup_locations" id="orddd_delivery_settings_type" class="input-radio" <?php echo $pickup_location_enabled; ?>/><?php _e( 'Pickup Locations', 'order-delivery-date' );?>
                </label>
            </p>
            <div class="delivery_type_options delivery_type_orddd_pickup_locations">          
                <select class="orddd_pickup_locations" id="orddd_pickup_locations" name="orddd_shipping_based_settings_<?php echo $option_key; ?>[orddd_pickup_locations][]" multiple="multiple" placeholder="<?php _e( 'Choose Pickup Locations', 'order-delivery-date' );?>">
                    <?php
                    foreach ( $pickup_locations as $key => $value ) {
                        $address = lpp_addon_for_orddd::orddd_get_formatted_address( $value, true );
                        $location_id = '';
                        if( isset( $value[ 'id' ] ) ) {
                            $location_id = $value[ 'id' ];
                        }
                        if( in_array( 'orddd_pickup_location_' . esc_attr( $location_id ) , $pickup_locations_stored ) ) {
                            echo '<option value="orddd_pickup_location_' . esc_attr( $location_id ) . '" selected>';
                        } else {
                            echo '<option value="orddd_pickup_location_' . esc_attr( $location_id ) . '">';
                        }
                        echo $address;
                        echo '</option>';
                    }
                    ?>
                </select>
            </div>
            <script type='text/javascript'>
                jQuery( document ).ready( function(){
                	if ( jQuery( "input[type=radio][id=\"orddd_delivery_settings_type\"][value=\"orddd_pickup_locations\"]" ).is(":checked") ) {
                		jQuery( '.delivery_type_options' ).slideUp();
                		jQuery( '.delivery_type_orddd_pickup_locations' ).slideDown();
                	} else {
             		    jQuery( '.delivery_type_orddd_pickup_locations' ).slideUp();
                	}
                    jQuery( '.orddd_pickup_locations' ).select2();
                    jQuery( '.orddd_pickup_locations' ).css({'width': '300px' });
                });
            </script>
            <?php
        }
	}

	public static function orddd_get_formatted_address( $address, $one_line = false ) {
		// pass empty first_name/last_name otherwise we get a bunch of notices
		$formatted = WC()->countries->get_formatted_address( array_merge( array( 'first_name' => null, 'last_name' => null, 'state' => null ), $address ) );
		if ( $one_line ) {
			$formatted = str_replace( array( '<br/>', '<br />', "\n" ), array( ', ', ', ', '' ), $formatted );
		} else {
			if ( isset( $address['phone'] ) && $address['phone'] ) {
				$formatted .= "<br/>\n" . $address['phone'];
			}
		}
		return $formatted;
	}

    public function orddd_save_custom_settings( $input, $row_id ) {
        $new_input = $input;
        if( isset ( $input[ 'orddd_pickup_locations' ] ) && count( $input[ 'orddd_pickup_locations' ] ) > 0 ) {
            if( '' != $row_id ) {
                $option_key = orddd_common::get_shipping_setting_option_key( $row_id );
                $shipping_settings = get_option( 'orddd_shipping_based_settings_' . $row_id );
                if( isset( $shipping_settings[ 'orddd_lockout_date' ] ) ) {
                    $new_input[ 'orddd_lockout_date' ] = $shipping_settings[ 'orddd_lockout_date' ];
                }
                if( isset( $shipping_settings[ 'orddd_lockout_time_slot' ] ) ) {
                    $new_input[ 'orddd_lockout_time_slot' ] = $shipping_settings[ 'orddd_lockout_time_slot' ];
                }
            }
            $_REQUEST[ '_wp_http_referer' ] = get_admin_url() . 'admin.php?page=order_delivery_date&action=shipping_based&settings-updated=true';
        } else if( !isset ( $input[ 'shipping_methods' ] ) && !isset( $input[ 'product_categories' ] ) ) {
            $option_key = orddd_common::get_shipping_setting_option_key( $row_id );
            unregister_setting( 'orddd_shipping_based_settings', 'orddd_shipping_based_settings_' . $option_key );
            
            if( get_option( 'orddd_enable_shipping_based_delivery' ) == 'on' ) {
                if( isset( $input[ 'delivery_settings_based_on' ] ) && $input[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {
                    add_settings_error( 'orddd_shipping_based_settings_' . $option_key, 'shipping_methods_error', 'Please select pickup locations', 'error' );
                }
            }
            $new_input = false;
        }   
        return $new_input; 
    }

    public function is_pickup_location_selected( $post, $option_key ) {
        if( isset( $post[ 'orddd_shipping_based_settings_' . $option_key ] ) ) {
            $shipping_settings = $post[ 'orddd_shipping_based_settings_' . $option_key ];
        } else {
            $shipping_settings = array();
        }

        if( ( isset( $shipping_settings[ 'orddd_pickup_locations' ] ) && count( $shipping_settings[ 'orddd_pickup_locations' ] ) > 0 ) ) {
            return 'yes';
        } else {
            return 'no';
        }
    }

    public function orddd_shipping_settings_table_data( $shipping_settings ) {
        $shipping_method_str = '';
        foreach( $shipping_settings as $key => $value ) {
            if( isset( $value->row_id ) ) { 
                $shipping_settings_arr = get_option( 'orddd_shipping_based_settings_' . $value->row_id );
                if ( isset( $shipping_settings_arr[ 'delivery_settings_based_on' ][ 0 ] ) && $shipping_settings_arr[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {
                    $pickup_locations_stored = $shipping_settings_arr[ 'orddd_pickup_locations' ];
                    $shipping_method_str = "<b>Pickup Locations</b></br>";
                    $pickup_locations = get_option( 'woocommerce_pickup_locations', true );
                    foreach ( $pickup_locations as $pkey => $pvalue ) {
                        $location_id = '';
                        if( isset( $pvalue[ 'id' ] ) ) {
                            $location_id = $pvalue[ 'id' ];
                        }
                        if( in_array( 'orddd_pickup_location_' . esc_attr( $location_id ), $pickup_locations_stored ) ) {
                            $address = lpp_addon_for_orddd::orddd_get_formatted_address( $pvalue, true );
                            $shipping_method_str .= $address . ', ';
                        }
                    }
                    $shipping_method_str = substr( $shipping_method_str, 0, -2 );
                    $shipping_settings[ $value->row_id ]->shipping_methods = $shipping_method_str;    
                }
            }        
        }
        return $shipping_settings;
    }

    public function orddd_get_is_pickup_location( $checkout = '' ) {
        global $post, $woocommerce;
        $pickup_location_settings = get_option( 'woocommerce_local_pickup_plus_settings', true );
        $pickup_location_categories = array();
        $is_pickup_location = 'No';
        if( isset( $pickup_location_settings[ 'enabled' ] ) && 'yes' == $pickup_location_settings[ 'enabled' ] ) {

            if( isset( $pickup_location_settings[ 'categories' ] ) && $pickup_location_settings[ 'categories' ] != '' ) {
                $pickup_location_categories = $pickup_location_settings[ 'categories' ];
            } else {
                $pickup_location_categories[] = 0;              
            }
            if( is_account_page() ) {
                if( is_object( $checkout ) ) {
                    $order = new WC_Order( $checkout->id );
                    $items = $order->get_items();
                    //$items = $checkout->get_items();
                    foreach( $items as $key => $value ) {
                        $product_id = $value[ 'product_id' ];
                        $terms = get_the_terms( $product_id  , 'product_cat' );
                        if( $terms != '' ) {
                            foreach ( $terms as $term => $val ) {
                                if( in_array( $val->term_id, $pickup_location_categories ) || in_array( 0, $pickup_location_categories ) ) {
                                    $is_pickup_location = 'Yes';
                                } else {
                                    $is_pickup_location = 'No';
                                    break 2;
                                }
                            }
                        } else if( in_array( 0, $pickup_location_categories ) ) {
                            $is_pickup_location = 'Yes';
                            break;
                        } else {
                            $is_pickup_location = 'No';
                            break;
                        }
                    }    
                }
            } else {
                foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
                    $terms = get_the_terms( $values[ 'data' ]->id  , 'product_cat' );
                    if( $terms != '' ) {
                        foreach ( $terms as $term => $val ) {
                            if( in_array( $val->term_id, $pickup_location_categories ) || in_array( 0, $pickup_location_categories ) ) {
                                $is_pickup_location = 'Yes';
                            } else {
                                $is_pickup_location = 'No';
                                break 2;
                            }
                        }
                    } else if( in_array( 0, $pickup_location_categories ) ) {
                        $is_pickup_location = 'Yes';
                        break;
                    } else {
                        $is_pickup_location = 'No';
                        break;
                    }
                }    
            }
            
        }
        return $is_pickup_location;
    }

    public function orddd_include_front_scripts() {
        global $lpp_version;
        $is_pickup_location = $this->orddd_get_is_pickup_location();
        if( 'Yes' == $is_pickup_location ) {
            wp_enqueue_script( 'lpp-addon-for-orddd', plugins_url( '/js/lpp-addon-for-orddd.js', __FILE__ ), '',  $lpp_version, false );
        }
    }

    public function orddd_before_checkout_delivery_date( $checkout ) {
        $is_pickup_location = $this->orddd_get_is_pickup_location( $checkout );
        if( 'Yes' == $is_pickup_location ) {
            echo '<input type="hidden" id="is_pickup_location_enabled" name="is_pickup_location_enabled" value="yes">';
            echo '<input type="hidden" name="orddd_pickup_location_selected" id="orddd_pickup_location_selected">';
            echo '<input type="hidden" name="lpp_selected_pickup_location" id="lpp_selected_pickup_location">';
            $hidden_vars_str           = '';
            if( get_option( 'orddd_enable_shipping_based_delivery' ) == 'on' ) {
                $i                         = 0;
                $orddd_pickup_locations    = array(); 
                $shipping_settings         = array();
                $results                   = orddd_common::orddd_get_shipping_settings();
                $current_time              = current_time( 'timestamp' );
                foreach ( $results as $key => $value ) {    
                    $shipping_settings          = get_option( $value->option_name );
                    $orddd_pickup_locations_str = lpp_addon_for_orddd::orddd_pickup_location_settings( $shipping_settings, $checkout );
                    $enable_delivery_date       = orddd_common::orddd_get_shipping_enable_delivery_date( $shipping_settings );
                    $date_field_mandatory       = orddd_common::orddd_get_shipping_date_field_mandatory( $shipping_settings );
                    $time_slots_enable          = orddd_common::orddd_is_shipping_timeslot_enable( $shipping_settings );
                    $timeslot_field_mandatory   = orddd_common::orddd_get_shipping_time_field_mandatory( $shipping_settings );
                    $new_array                  = orddd_common::orddd_get_shipping_hidden_variables( $shipping_settings );
                    $var_time                   = orddd_common::orddd_get_shipping_time_settings_variable( $shipping_settings ); 
                    $disabled_days_str          = orddd_common::orddd_get_shipping_disabled_days_str( $shipping_settings );
                    if( isset( $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] ) && $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {
                        $orddd_pickup_locations[ $i ][ 'orddd_pickup_locations' ] = $orddd_pickup_locations_str;
                        $orddd_pickup_locations[ $i ][ 'enable_delivery_date' ] = $enable_delivery_date;
                        $orddd_pickup_locations[ $i ][ 'date_field_mandatory' ] = $date_field_mandatory;
                        $orddd_pickup_locations[ $i ][ 'time_slots' ] = $time_slots_enable;
                        $orddd_pickup_locations[ $i ][ 'timeslot_field_mandatory' ] = $timeslot_field_mandatory;
                        $orddd_pickup_locations[ $i ][ 'hidden_vars' ] = json_encode( $new_array );
                        $orddd_pickup_locations[ $i ][ 'time_settings' ] = $var_time;
                        $orddd_pickup_locations[ $i ][ 'disabled_days' ] = $disabled_days_str;
                    }
                    $i++;
                }

                if( count( $orddd_pickup_locations ) > 0 ) {
                    $hidden_vars_str = esc_attr( json_encode( $orddd_pickup_locations ) );
                }
                echo '<input type="hidden" name="orddd_hidden_location_str" id="orddd_hidden_location_str" value="' . $hidden_vars_str . '">';
            }
        }
    }

    public function orddd_pickup_location_settings( $shipping_settings, $checkout = '' ) {
        $shipping_method_str    = '';
        $is_pickup_location = $this->orddd_get_is_pickup_location( $checkout );
        if( 'Yes' == $is_pickup_location ) {
            if( isset( $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] ) && $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {
                if( isset( $shipping_settings[ 'orddd_pickup_locations' ] ) ) {
                    $shipping_methods = $shipping_settings[ 'orddd_pickup_locations' ];
                    foreach( $shipping_methods as $key => $value ) {
                        $shipping_method_str .= $value . ',';
                    }
                    $shipping_method_str = substr( $shipping_method_str, 0, -1 );
                }
            }
        } 
        return $shipping_method_str;
    }

    public function orddd_get_shipping_method( $shipping_settings, $post, $shipping_methods = array(), $shipping_method = '' ) {
        $shipping_method_selected = '';
        if( isset( $post[ 'shipping_method' ][ 0 ] ) && is_array( $post[ 'shipping_method' ] ) ) {
            $shipping_method_selected = $post[ 'shipping_method' ][ 0 ];
            if( false !== strpos( $shipping_method_selected, 'usps' ) ) {
                $shipping_method_selected = $orddd_zone_id . ":" . $shipping_method_selected;
            }
        } else if( isset( $post[ 'shipping_method' ] ) && $post[ 'shipping_method' ] != '' ) {
            $shipping_method_selected = $post[ 'shipping_method' ];
            if( false !== strpos( $shipping_method_selected, 'usps' ) ) {
                $shipping_method_selected = $orddd_zone_id . ":" . $shipping_method_selected;
            }
        }

        $shipping_method_values = array( 'shipping_methods' => $shipping_methods, 'shipping_method' => $shipping_method );

        if( $shipping_method_selected == 'local_pickup_plus' ) {
            if( isset( $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] ) && $shipping_settings[ 'delivery_settings_based_on' ][ 0 ] == 'orddd_pickup_locations' ) {

                if( isset( $shipping_settings[ 'orddd_pickup_locations' ] ) ) {
                    $shipping_method_values[ 'shipping_methods' ] = $shipping_settings[ 'orddd_pickup_locations' ];
                } 
                if ( isset( $post[ 'post_data' ] ) ) {
                    $shipping_class_to_load_type = preg_match( '/orddd_pickup_location_selected=(.*?)&/', $post['post_data'], $shipping_class_to_load_match );
                    if ( isset( $shipping_class_to_load_match[ 1 ] ) ) {
                        $shipping_method_values[ 'shipping_method' ] = $shipping_class_to_load_match[ 1 ];
                    } 
                } else {
                    if( isset( $post[ 'pickup_location' ][ 0 ] ) && $post[ 'pickup_location' ][ 0 ] != '' && is_array( $post[ 'pickup_location' ] ) ) {
                        $shipping_method_values[ 'shipping_method' ] = "orddd_pickup_location_" . $post[ 'pickup_location' ][ 0 ];
                    } else if( isset( $post[ 'pickup_location' ] ) && $post[ 'pickup_location' ] != '' ) {
                        $shipping_method_values[ 'shipping_method' ] = $post[ 'pickup_location' ];
                    }
                }
            }
        }
        return $shipping_method_values;
    }
}
$lpp_addon_for_orddd = new lpp_addon_for_orddd();