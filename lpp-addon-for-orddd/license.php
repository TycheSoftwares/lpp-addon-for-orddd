<?php
/**
 * orddd_license Class
 *
 * @class orddd_license
 */

class lpp_license {
	
	/** 
    * Activate License if License key is valid  
    */
	public static function lpp_activate_license() {		
		// listen for our activate button to be clicked
		if ( isset( $_POST[ 'lpp_license_activate' ] ) ) {
			// run a quick security check
			if ( ! check_admin_referer( 'lpp_sample_nonce', 'lpp_sample_nonce' ) )
				return; // get out if we didn't click the Activate button
			// retrieve the license from the database
			$license = trim( get_option( 'lpp_sample_license_key' ) );
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license' 	 => $license,
				'item_name'  => urlencode( LPP_SL_ITEM_NAME ) // the name of our product in EDD
			);

			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, LPP_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "active" or "inactive"
			update_option( 'lpp_sample_license_status', $license_data->license );
		}
	}

	/** 
    * Deactivate the License  
    */
	
	public static function lpp_deactivate_license() {
		// listen for our activate button to be clicked
		if ( isset( $_POST[ 'lpp_license_deactivate' ] ) ) {
			// run a quick security check
			if ( ! check_admin_referer( 'lpp_sample_nonce', 'lpp_sample_nonce' ) )
				return; // get out if we didn't click the Activate button
	
			// retrieve the license from the database
			$license = trim( get_option( 'lpp_sample_license_key' ) );
			
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license' 	 => $license,
				'item_name'  => urlencode( LPP_SL_ITEM_NAME ) // the name of our product in EDD
			);
	
			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, LPP_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' )
				delete_option( 'lpp_sample_license_status' );
		}
	}
	
	/**
	* Checks if License key is valid or not
	*/

	public static function lpp_sample_check_license() {
		global $wp_version;
		$license = trim( get_option( 'lpp_sample_license_key' ) );

		$api_params = array(
			'edd_action' => 'check_license',
			'license'	 => $license,
			'item_name'	 => urlencode( LPP_SL_ITEM_NAME )
		);
		// Call the custom API.
		$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, LPP_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->license == 'valid' ) {
			echo 'valid'; exit;
			// this license is still valid
		} else {
			echo 'invalid'; exit;
			// this license is no longer valid
		}
	}

	/**
    * Stores the license key in database of the site once the plugin is installed and the license key saved.
    */
	public static function lpp_register_option() {
		// creates our settings in the options table
		register_setting( 'lpp_sample_license', 'lpp_sample_license_key',  array( 'lpp_license', 'lpp_get_sanitize_license' ) );
	}

    /**
    * Checks if a new license has been entered, if yes plugin must be reactivated.
    */	
	public static function lpp_get_sanitize_license( $new ) {
		$old = get_option( 'lpp_sample_license_key' );
		if( $old && $old != $new ) {
			delete_option( 'lpp_sample_license_status' ); // new license has been entered, so must reactivate
		}
		return $new;
	}

	/**
    * Add the license page in the Order delivery date menu.
    */
	public static function lpp_sample_license_page() {
		$license 	= get_option( 'lpp_sample_license_key' );
		$status 	= get_option( 'lpp_sample_license_status' );
	
		?>
		<div class="wrap">
			<h2><?php _e( 'Plugin License Options', 'lpp-addon-for-orddd' ); ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'lpp_sample_license' ); ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">	
									<th scope="row" valign="top">
										<?php _e( 'License Key', 'lpp-addon-for-orddd' ); ?>
									</th>
									<td>
										<input id="lpp_sample_license_key" name="lpp_sample_license_key" type="text" class="regular-text"	value="<?php esc_attr_e( $license ); ?>" />
											<label class="description" for="lpp_sample_license_key"><?php _e( 'Enter your license key', 'lpp-addon-for-orddd' ); ?></label>
									</td>
								</tr>
								<?php if ( false !== $license ) { ?>
								<tr valign="top">	
									<th scope="row" valign="top">
										<?php _e( 'Activate License', 'lpp-addon-for-orddd' ); ?>
									</th>
									<td>
									<?php if ( $status !== false && $status == 'valid' ) { ?>
										<span style="color:green;"><?php _e( 'active', 'lpp-addon-for-orddd' ); ?></span>
										<?php wp_nonce_field( 'lpp_sample_nonce', 'lpp_sample_nonce' ); ?>
										<input type="submit" class="button-secondary" name="lpp_license_deactivate" value="<?php _e( 'Deactivate License', 'lpp-addon-for-orddd' ); ?>"/>
									<?php } else {
											wp_nonce_field( 'lpp_sample_nonce', 'lpp_sample_nonce' ); ?>
											<input type="submit" class="button-secondary" name="lpp_license_activate" value="<?php _e( 'Activate License', 'lpp-addon-for-orddd' ); ?>"/>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>	
					<?php submit_button(); ?>
				</form>
		<?php
	}
}