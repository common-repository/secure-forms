<?php
/**
 * Core file of the plugin.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'WPSF_API_URL', 'https://wpsecureforms.com' );

if ( ! function_exists( 'wpsf_is_plugin_admin_page' ) ) {
	/**
	 * Initilize the variables.
	 */
	function wpsf_is_plugin_admin_page() {
		$wpsf_is_api_validated = get_option( 'wpsf_validated', false );
		$wpsf_page_slugs = array( 'wpsf-dashboard', 'wpsf-form-data-page', 'wpsf-forms-page', 'wpsf-log-page', 'wpsf-dashboard-account', 'wpsf-dashboard-contact' );
		$request_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'], '?' ) ) : '';
		$query_string = wp_parse_url( $request_url, PHP_URL_QUERY );
		parse_str( $query_string, $query_params );
		$admin_page_slug = isset( $query_params['page'] ) ? $query_params['page'] : null;
		if ( in_array( $admin_page_slug, $wpsf_page_slugs ) ) {
			return true;
		} else {
			return false;
		}
	}
}
if ( ! function_exists( 'wpsf_init_values' ) ) {
	/**
	 * Initilize the variables.
	 */
	function wpsf_init_values() {
		global $wpsf_fs;
		global $is_validated, $wpsf_id, $site_baa_status, $wpsf_is_api_validated;
		$wpsf_is_api_validated = get_option( 'wpsf_validated', false );
		if ( wpsf_is_plugin_admin_page() && $wpsf_is_api_validated ) {
			$site_details    = wpsf_get_site_details();
			if ( count( $site_details ) > 0 ) {
				$is_baa_required = $site_details[0]->is_baa_required;
				$is_baa_signed = $site_details[0]->is_baa_signed;
				if ( '1' === $is_baa_required ) {
					if ( '1' === $is_baa_signed ) {
						$site_baa_status = true;
					} else {
						$site_baa_status = false;
					}
				} else {
					$site_baa_status = true;
				}
				$wpsf_id         = $site_details[0]->wpsf_siteid;
			}
		}
	}
}
add_action( 'init', 'wpsf_init_values' );

if ( ! function_exists( 'wpsf_activation_hook' ) ) {
	/**
	 * Add a custom user role for the plugin users.
	 */
	function wpsf_activation_hook() {
		$role = add_role(
			'secure_forms_user',
			'Secure Forms User',
			array(
				'read' => true,
			)
		);
		if ( $role ) {
			$role->add_cap( 'wpsf_capability' );
		}
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'wpsf_capability' );
		}
	}
}
register_activation_hook( WPSF_PLUGIN_FILE, 'wpsf_activation_hook' );

if ( ! function_exists( 'wpsf_synch_site_to_rds' ) ) {
	/**
	 * Update Site Info in RDS.
	 */
	function wpsf_synch_site_to_rds() {
		global $wpsf_is_api_validated;
		if ( wpsf_is_plugin_admin_page() && $wpsf_is_api_validated ) {
			global $wpsf_fs;
			$url  = WPSF_API_URL . '/wp-json/wpsf/v1/synch_site';
			$body = array(
				'site_url' => get_site_url(),
			);
			$args = array(
				'method'    => 'POST',
				'timeout'   => 45,
				'sslverify' => true,
				'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
				'body'      => $body,
			);
			$request  = wp_remote_post( $url, $args );
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			$secure_forms_role = get_role( 'secure_forms_user' );
			if ( $secure_forms_role ) {
				$secure_forms_role->add_cap( 'wpsf_capability' );
			}
		}
	}
}
add_action( 'admin_init', 'wpsf_synch_site_to_rds' );

if ( ! function_exists( 'wpsf_check_valid_license' ) ) {
	/**
	 * Check if site has valid licence.
	 */
	function wpsf_check_valid_license() {
		global $wpsf_fs;
		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/wpsf_check_pro';
		$body = array(
			'site_url' => get_site_url(),
		);
		$args = array(
			'method'    => 'POST',
			'timeout'   => 45,
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);
		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		if ( 200 === $response->code ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_site_baa_status' ) ) {
	/**
	 * Get Site BAA Status.
	 */
	function wpsf_site_baa_status() {
		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/baa_status';
		$body = array(
			'site_url' => get_site_url(),
		);

		$args = array(
			'method'    => 'POST',
			'timeout'   => 45,
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request     = wp_remote_post( $url, $args );
		$response    = json_decode( wp_remote_retrieve_body( $request ) );
		if ( $response ) {
			return $response->baa_status;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_generate_api_key' ) ) {
	/**
	 * Generate a API Key.
	 *
	 * @param int $length Length of the key.
	 */
	function wpsf_generate_api_key( $length = 32 ) {
		$bytes   = random_bytes( $length );
		$api_key = rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
		$api_key = wp_unslash( $api_key );
		return $api_key;
	}
}

if ( ! function_exists( 'wpsf_scripts' ) ) {
	/**
	 * Plugin admin scripts and styles.
	 */
	function wpsf_scripts() {
		global $wpsf_fs;
		wp_enqueue_style( 'wpsf-admin-styles', plugin_dir_url( __DIR__ ) . 'assets/admin/css/wpsf-admin-css.css', array(), 1.0 );
		wp_enqueue_script( 'wpsf-admin-script', plugin_dir_url( __DIR__ ) . 'assets/admin/js/wpsf_admin_script.js', array(), 1.0, true );
		wp_register_script( 'wpsf-select2-script', plugin_dir_url( __DIR__ ) . 'assets/admin/js/select2.min.js', array(), 1.0, true );
		wp_register_style( 'wpsf-select2-style', plugin_dir_url( __DIR__ ) . 'assets/admin/css/select2.min.css', array(), true );
		wp_enqueue_style( 'wpsf-wizard-style', plugin_dir_url( __DIR__ ) . 'assets/admin/css/smart_wizard_all.min.css', array(), 1.0 );
		wp_enqueue_script( 'wpsf-wizard-script', plugin_dir_url( __DIR__ ) . 'assets/admin/js/jquery.smartWizard.min.js', array(), 1.0, true );
		wp_localize_script(
			'wpsf-admin-script',
			'ajax',
			array(
				'wpsf_nonce' => wp_create_nonce( 'ajax-nonce' ),
				'url'        => admin_url( 'admin-ajax.php' ),
				'wpsf_url'   => WPSF_API_URL,
			)
		);
		wp_localize_script(
			'wpsf-wizard-script',
			'wpsf_fs_data',
			array(
				'ajax_url' => get_site_url() . str_replace( "'", '', Freemius::ajax_url() ),
				'action' => $wpsf_fs->get_ajax_action( 'activate_license' ),
				'security'        => $wpsf_fs->get_ajax_security( 'activate_license' ),
				'module_id'   => $wpsf_fs->get_id(),
				'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_register_style( 'wpsf-wizard-pricing', plugin_dir_url( __DIR__ ) . 'assets/admin/css/pricing.css?v=180', '', time() );
		wp_register_style( 'wpsf-wizard-pricing-common', plugin_dir_url( __DIR__ ) . 'assets/admin/css/common.css?v=180', '', time() );
		wp_register_script( 'wpsf-freemius-checkout-script', 'https://checkout.freemius.com/checkout.min.js', '', 1.0, false );
		wp_register_script( 'wpsf-jotform-embed-script', 'https://cdn.jotfor.ms/s/umd/latest/for-form-embed-handler.js', '', 1.0, false );
		wp_register_script( 'wpsf-wizard-page-script', plugin_dir_url( __DIR__ ) . 'assets/admin/js/wpsf_wizard_page.js', '', time(), false );
	}
}

add_action( 'admin_enqueue_scripts', 'wpsf_scripts' );

if ( ! function_exists( 'wpsf_save_form_data_to_rds' ) ) {
	/**
	 * Intercept the data to encrypt before save it.
	 *
	 * @param string $mail ID of the form submitted.
	 * @param object $custom_form array of field values.
	 * @param array  $data data of the form submitted.
	 * @param object $entry tne entry object.
	 * */
	function wpsf_save_form_data_to_rds( $mail, $custom_form, $data, $entry ) {
		global $wpdb;
		$limit_reached = wpsf_is_limit_reached();
		if ( ! $limit_reached ) {
			$wpsf_id            = wpsf_get_wpsf_id();
			$encrypted_value    = wpsf_encrypt_aes256( wp_json_encode( $entry ) );
			$encryption_enabled = get_option( 'wpsf_selected_forms' );
			$wpsf_siteid        = get_option( 'wpsf_siteid' );
			if ( $encryption_enabled && in_array( $custom_form->id, $encryption_enabled ) ) {
				$entry_table_name        = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
				$entry_table_meta_name   = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
				$result1 = $wpdb->query( $wpdb->prepare( 'DELETE from %1s WHERE entry_id=%d', $entry_table_name, $entry->entry_id ) );
				$result2 = $wpdb->query( $wpdb->prepare( 'DELETE from %1s WHERE entry_id=%d', $entry_table_meta_name, $entry->entry_id ) );
				$url  = WPSF_API_URL . '/wp-json/wpsf/v1/insert_form_data';
				$body = array(
					'site_url'    => get_site_url(),
					'form_data'   => $encrypted_value,
					'form_id'     => $custom_form->id,
					'wpsf_siteid' => $wpsf_id,
				);

				$args = array(
					'method'    => 'POST',
					'timeout'   => 45,
					'sslverify' => true,
					'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
					'body'      => $body,
				);

				$request  = wp_remote_post( $url, $args );
				$response = json_decode( wp_remote_retrieve_body( $request ) );
				if ( 200 !== $response->code ) {
					echo esc_html( 'Error while inserting the form data: ' . $response->response );
				}
			}
		}
	}
}
add_action( 'forminator_custom_form_mail_before_send_mail', 'wpsf_save_form_data_to_rds', 99, 4 );

/**
 * Disable sending email to admin after the submissions.
 *
 * @param string                $message mail contents.
 * @param Forminator_Form_Model $custom_form the current form.
 * @param array                 $data data of the form submitted.
 * @param object                $entry tne entry object.
 * @param object                $mail the email class object.
 *
 * @return string $message
 */
function wpsf_disable_admin_emails( $message, $custom_form, $data, $entry, $mail ) {
	$limit_reached = wpsf_is_limit_reached();
	$encryption_enabled = get_option( 'wpsf_selected_forms' );
	$wpsf_siteid        = get_option( 'wpsf_siteid' );
	if ( $encryption_enabled && in_array( $custom_form->id, $encryption_enabled ) ) {
		if ( ! $limit_reached ) {
			return '';
		} else {
			return $message;
		}
	} else {
		return $message;
	}
}
add_filter( 'forminator_custom_form_mail_admin_message', 'wpsf_disable_admin_emails', 99, 5 );

if ( ! function_exists( 'wpsf_get_key' ) ) {
	/**
	 * Get API Key.
	 */
	function wpsf_get_key() {
		$key  = '';
		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/get_key';
		$body = array(
			'site_url' => get_site_url(),
		);

		$args = array(
			'method'    => 'GET',
			'timeout'   => 45,
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		if ( count( $response ) > 0 ) {
			$key = $response[0]->api_key;
		}
		return $key;
	}
}

if ( ! function_exists( 'wpsf_encrypt_aes256' ) ) {
	/**
	 * Encrypt the values.
	 *
	 * @param string $plaintext values to encrypt.
	 * @param string $key key string for the encryption.
	 */
	function wpsf_encrypt_aes256( $plaintext, $key = '' ) {
		$cipher         = 'AES-256-CBC';
		$key            = wpsf_get_key();
		$ivlen          = openssl_cipher_iv_length( $cipher );
		$iv             = openssl_random_pseudo_bytes( $ivlen );
		$ciphertext_raw = openssl_encrypt( $plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
		$hmac           = hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary = true );
		return base64_encode( $iv . $hmac . $ciphertext_raw );
	}
}

if ( ! function_exists( 'wpsf_decrypt_aes256' ) ) {
	/**
	 * Decrypt the values.
	 *
	 * @param string $ciphertext values to decrypt.
	 * @param string $key key string for the decryption.
	 */
	function wpsf_decrypt_aes256( $ciphertext, $key = '' ) {
		$cipher = 'AES-256-CBC';
		$ivlen  = openssl_cipher_iv_length( $cipher );
		$c      = base64_decode( $ciphertext );
		$iv     = substr( $c, 0, $ivlen );
		$hmac   = substr( $c, $ivlen, $sha2len = 32 );

		$ciphertext_raw     = substr( $c, $ivlen + $sha2len );
		$original_plaintext = openssl_decrypt( $ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
		$calcmac            = hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary = true );
		if ( hash_equals( $hmac, $calcmac ) ) {
			return $original_plaintext;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_dashboard_callback' ) ) {
	/**
	 * Callback for the plugin dashboard page.
	 */
	function wpsf_dashboard_callback() {
		global $wpsf_is_api_validated, $site_baa_status;
		$wizard_status = get_option( 'wpsf_wizard_finished', false );
		if ( ! $wpsf_is_api_validated || ! $site_baa_status || ! $wizard_status ) {
			include_once plugin_dir_path( __DIR__ ) . 'views/wpsf-wizard.php';
			return;
		}
		include_once plugin_dir_path( __DIR__ ) . 'views/wpsf-dashboard.php';
	}
}

if ( ! function_exists( 'wpsf_is_authorized_user' ) ) {
	/**
	 * Callback for validating the user to access the form data.
	 */
	function wpsf_is_authorized_user() {
		global $user_email;
		$user = wp_get_current_user();
		$url     = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites_data';
		$body    = array(
			'site_url'   => get_site_url(),
			'user_email' => $user_email,
		);
		$args = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);
		$request         = wp_remote_post( $url, $args );
		$response        = json_decode( wp_remote_retrieve_body( $request ) );
		if ( 'rest_forbidden' === $response->code ) {
			return false;
		}
		if ( in_array( 'secure_forms_user', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_license_content_callback' ) ) {
	/**
	 * Callback for license page
	 */
	function wpsf_license_content_callback() {
		include_once plugin_dir_path( __DIR__ ) . 'views/wpsf-license.php';
	}
}

if ( ! function_exists( 'wpsf_forms_content_callback' ) ) {
	/**
	 * Callback for the form admin page.
	 */
	function wpsf_forms_content_callback() {
		wp_enqueue_script( 'wpsf-select2-script' );
		wp_enqueue_style( 'wpsf-select2-style' );
		if ( isset( $_POST['wpsf_forms'] ) ) {
			check_admin_referer( 'wpsf-form-field', '_wpnonce_select_form' );
			$selected_forms = array_map( 'sanitize_text_field', wp_unslash( $_POST['wpsf_forms'] ) );
			if ( '' !== $selected_forms ) {
				foreach ( $selected_forms as $form_id ) {
					update_post_meta( $form_id, 'wpsf_enabled', true );
				}
			}
			update_option( 'wpsf_selected_forms', $selected_forms );
		}
		?>
		<div class="wrap wpsf_select_forms">
			<div class="header">
				<h1> Select Form </h1>			
			</div>
			<div class="wpsf_select_form_wrapper">
				<p class="desc"> <?php echo esc_html( 'Choose the from from the list for which you want to apply the encryption and make it secure. ' ); ?></p>
				<form action="" method="post" name="wpsf_select_form" id="wpsf_select_form">
					<?php
						$selected_forms = get_option( 'wpsf_selected_forms' );
						$multiple       = '';
						wp_nonce_field( 'wpsf-form-field', '_wpnonce_select_form' );
					?>
					<div class="select_form_fields">
						<label for="wpsf_form">Select Forms </label>
						<div class="select_container">
								<select name="wpsf_forms[]" id="wpsf_forms" <?php echo esc_html( $multiple ); ?>  style="width:100%">
								<option></option>
								<?php
									$args = array(
										'post_type'      => 'forminator_forms',
										'posts_per_page' => -1,
									);

									$query = new WP_Query( $args );
									if ( $query->have_posts() ) {
										while ( $query->have_posts() ) :
											$query->the_post();
											$form_id = get_the_ID();
											if ( $selected_forms ) {
												$selected = in_array( $form_id, $selected_forms ) ? 'selected' : '';
											}
											?>
											<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( get_the_ID() ); ?>">
												<?php echo esc_html( get_the_title() ); ?>
											</option>
											<?php
										endwhile;
									}
									?>
							</select>
						</div>
						<button class="button button-primary"><?php echo esc_html( 'Submit' ); ?></button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wpsf_get_form_entries_count' ) ) {
	/**
	 * Callback for the form entries count.
	 */
	function wpsf_get_form_entries_count() {
		$body = array(
			'site_url' => get_site_url(),
		);

		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites_data';
		$args = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request         = wp_remote_post( $url, $args );
		$response        = json_decode( wp_remote_retrieve_body( $request ) );
		if ( 200 === $response->status ) {
			return count( $response->form_data );
		} else {
			return 0;
		}
	}
}

if ( ! function_exists( 'wpsf_get_site_details' ) ) {
	/**
	 * Callback for the form entries count.
	 */
	function wpsf_get_site_details() {
		$body = array(
			'site_url' => get_site_url(),
		);

		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites';
		$args = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request         = wp_remote_post( $url, $args );
		$response        = json_decode( wp_remote_retrieve_body( $request ) );
		return $response;
	}
}

if ( ! function_exists( 'wpsf_form_data_content_callback' ) ) {
	/**
	 * Callback for the form data admin page.
	 */
	function wpsf_form_data_content_callback() {
		global $wpsf_is_api_validated;
		global $current_user;
		$is_ssl_valid = wpsf_is_secure( get_site_url() );
		if ( ! $is_ssl_valid ) {
			?>
			<h1><?php echo esc_html( 'Invalid SSL certificate. please install a valid SSL certificate to start using the features.' ); ?></h2>
			<?php
			return;
		}
		if ( ! $wpsf_is_api_validated ) {
			?>
			<h1> <?php echo esc_html( 'Please' ); ?> 
				<a href="<?php echo esc_url( get_site_url() ); ?>/wp-admin/admin.php?page=wpsf-forms-api-page" >
					<?php echo esc_html( 'Validate The API' ); ?>
				</a> <?php echo esc_html( 'first.' ); ?>
			</h1>
			<?php
			return;
		}
		include_once plugin_dir_path( __DIR__ ) . 'views/form-data.php';
	}
}

if ( ! function_exists( 'wpsf_validate_api' ) ) {
	/**
	 * Callback for the form data admin page.
	 *
	 * @param string $api_key key to validate.
	 */
	function wpsf_validate_api( $api_key ) {
		$is_valid = false;
		$body     = array(
			'site_url' => get_site_url(),
			'api_key'  => $api_key,
		);

		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/validate_api';
		$args = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		if ( 200 === $response->status ) {
			$site_data = $response->site_data;
			$site_url  = $site_data->url;
			if ( get_site_url() === $site_url ) {
				$is_valid = true;
			} else {
				$is_valid = false;
			}
		}
		return $is_valid;
	}
}

if ( ! function_exists( 'wpsf_update_user_token' ) ) {
	/**
	 * Callback updating the user token.
	 */
	function wpsf_update_user_token() {
		global $wpsf_is_api_validated;
		$user_token = wp_get_session_token();
		$body = array(
			'site_url'   => get_site_url(),
			'user_token' => $user_token,
			'action' => 'login',
		);

		$url  = WPSF_API_URL . '/wp-json/wpsf/v1/wpsf_user_token';
		$args = array(
			'method'    => 'POST',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		update_option( 'wpsf_last_run_time', time() );
	}
}

if ( ! function_exists( 'wpsf_create_user_token' ) ) {
	/**
	 * Callback updating the user token.
	 *
	 * @param string $logged_in_cookie The logged-in cookie value.
	 * @param int    $expire The time the login grace period expires as a UNIX timestamp.
	 * @param int    $expiration The time when the logged-in authentication cookie expires as a UNIX timestamp.
	 * @param int    $user_id User ID.
	 * @param string $logged_in Authentication scheme.
	 * @param string $token User’s session token to use for this cookie.
	 */
	function wpsf_create_user_token( $logged_in_cookie, $expire, $expiration, $user_id, $logged_in, $token ) {
		global $wpsf_is_api_validated;
		$user_token = $token;
		if ( $wpsf_is_api_validated ) {
			$body = array(
				'site_url'   => get_site_url(),
				'user_token' => $user_token,
				'action' => 'login',
			);
			$url  = WPSF_API_URL . '/wp-json/wpsf/v1/wpsf_user_token';
			$args = array(
				'method'    => 'POST',
				'sslverify' => true,
				'headers'   => array( 'wpsf-token' => $user_token ),
				'body'      => $body,
			);

			$request  = wp_remote_post( $url, $args );
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			update_option( 'wpsf_last_run_time', time() );
		}
	}
}
add_action( 'set_logged_in_cookie', 'wpsf_create_user_token', 99, 6 );

if ( ! function_exists( 'wpsf_logout_user' ) ) {
	/**
	 * User logout action.
	 */
	function wpsf_logout_user() {
		global $wpsf_is_api_validated;
		if ( $wpsf_is_api_validated ) {
			$body = array(
				'site_url'   => get_site_url(),
				'action' => 'logout',
			);
			$url  = WPSF_API_URL . '/wp-json/wpsf/v1/wpsf_user_token';
			$args = array(
				'method'    => 'POST',
				'sslverify' => true,
				'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
				'body'      => $body,
			);
			$request  = wp_remote_post( $url, $args );
			$response = json_decode( wp_remote_retrieve_body( $request ) );
		}
	}
}

add_action( 'wp_logout', 'wpsf_logout_user' );

if ( ! function_exists( 'wpsf_generate_secure_token' ) ) {
	/**
	 * Generate a user token.
	 */
	function wpsf_generate_secure_token() {
		return bin2hex( random_bytes( 32 ) );
	}
}

if ( ! function_exists( 'wpsf_month_record_count' ) ) {
	/**
	 * Get month record count.
	 */
	function wpsf_month_record_count() {
		$wpsfid        = wpsf_get_wpsf_id();
		$total_records = 0;
		$body          = array(
			'site_url' => get_site_url(),
			'wpsf_id' => $wpsfid,
		);

		$url      = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites_current_month_data';
		$args     = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);
		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		if ( 200 === $response->status ) {
			$total_records = $response->total_records;
		}
		return $total_records;
	}
}

if ( ! function_exists( 'wpsf_is_limit_reached' ) ) {
	/**
	 * Callback to check if the monthly record limit reached.
	 */
	function wpsf_is_limit_reached() {
		$current_month_records = wpsf_month_record_count();
		$is_reached            = false;
		if ( $current_month_records >= 25 ) {
			$is_reached = true;
		}
		return $is_reached;
	}
}

if ( ! function_exists( 'wpsf_get_wpsf_id' ) ) {
	/**
	 * Get site id.
	 */
	function wpsf_get_wpsf_id() {
		$wpsf_id  = '';
		$body     = array(
			'site_url' => get_site_url(),
		);
		$url      = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites';
		$args     = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);
		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
		if ( is_array( $response ) && count( $response ) > 0 ) {
			$wpsf_id = $response[0]->wpsf_siteid;
		}
		return $wpsf_id;
	}
}


if ( ! function_exists( 'wpsf_forms_api_callback' ) ) {
	/**
	 * Callback for the form log admin page.
	 */
	function wpsf_forms_api_callback() {
		include_once plugin_dir_path( __DIR__ ) . 'views/form-api-key.php';
	}
}

if ( ! function_exists( 'wpsf_log_content_callback' ) ) {
	/**
	 * Callback for the form log admin page.
	 */
	function wpsf_log_content_callback() {
		include_once plugin_dir_path( __DIR__ ) . 'views/form-log.php';
	}
}

if ( ! function_exists( 'wpsf_update_baa_status' ) ) {
	/**
	 * Ajax function for update baa status.
	 */
	function wpsf_update_baa_status() {
		$response = array();
		$wpsfid   = wpsf_get_wpsf_id();
		if ( isset( $_POST['is_baa_required'] ) ) {
			$nonce = isset( $_POST['api_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['api_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				wp_die( 'Nonce Varification Failed!' );
			}
			$is_baa_required = sanitize_text_field( wp_unslash( $_POST['is_baa_required'] ) );
			set_transient( 'baa_status', $is_baa_required );
			$site_url        = get_site_url();
			$body     = array(
				'site_url'        => $site_url,
				'is_baa_required' => $is_baa_required,
			);
			$url      = WPSF_API_URL . '/wp-json/wpsf/v1/baa_required';
			$args     = array(
				'method'    => 'POST',
				'sslverify' => true,
				'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
				'body'      => $body,
			);
			$request  = wp_remote_post( $url, $args );
			$response = json_decode( wp_remote_retrieve_body( $request ) );
		}
		die();
	}
}
add_action( 'wp_ajax_wpsf_update_baa_status', 'wpsf_update_baa_status' );

if ( ! function_exists( 'wpsf_validate_api_key' ) ) {
	/**
	 * Validate API Key.
	 */
	function wpsf_validate_api_key() {
		$response = array();
		if ( isset( $_POST['wpsf_api_key'] ) ) {
			$nonce = isset( $_POST['api_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['api_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				wp_die( 'Nonce Varification Failed!' );
			}
			$api_key = sanitize_text_field( wp_unslash( $_POST['wpsf_api_key'] ) );
			$result  = wpsf_validate_api( $api_key );
			if ( $result ) {
				update_option( 'wpsf_validated', true );
				$url = get_site_url() . '/wp-admin/admin.php?page=wpsf-form-data-page';
				$response['status'] = 200;
				$response['message'] = '<h4>Api Validated Succesfully</h4>';
			} else {
				$response['status'] = 400;
				$response['message'] = '<h4 class="error">Api data not found.</h4>';
			}
		}
		echo wp_json_encode( $response );
		die();
	}
}
add_action( 'wp_ajax_wpsf_validate_api_key', 'wpsf_validate_api_key' );

if ( ! function_exists( 'wpsf_request_api_key' ) ) {
	/**
	 * Request API Key.
	 */
	function wpsf_request_api_key() {
		$response_ajax = array();
		$nonce = isset( $_POST['api_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['api_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
			wp_die( 'Nonce Varification Failed!' );
		}
		if ( isset( $_POST['wpsf_email_addr'] ) ) {
			$email_addr = sanitize_email( wp_unslash( $_POST['wpsf_email_addr'] ) );
		}
		if ( isset( $_POST['wpsf_terms'] ) ) {
			$wpsf_terms = sanitize_text_field( wp_unslash( $_POST['wpsf_terms'] ) );
		}
		if ( '' !== $email_addr && 'true' === $wpsf_terms ) {
			wpsf_update_user_token();
			$wpsf_site_inserted = wpsf_insert_site_details();
			if ( ! $wpsf_site_inserted ) {
				$response_ajax['status'] = 400;
				$response_ajax['message'] = 'Error Inserting the site details';
			}
			$body = array(
				'site_url'      => get_site_url(),
				'email_address' => $email_addr,
			);

			$url  = WPSF_API_URL . '/wp-json/wpsf/v1/get_api';
			$args = array(
				'method'    => 'GET',
				'sslverify' => true,
				'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
				'body'      => $body,
			);

			$request  = wp_remote_post( $url, $args );
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( 200 === $response->status ) {
				$response_ajax['status'] = 200;
				$response_ajax['message'] = 'Please check your inbox';
			}
		} else {
			$response_ajax['status'] = 400;
			$response_ajax['message'] = '<h4 class="error">Please check the values you have entered, either one of them is missing on invalid</h4>';
		}

		echo wp_json_encode( $response_ajax );
		die();
	}
}
add_action( 'wp_ajax_wpsf_request_api_key', 'wpsf_request_api_key' );

if ( ! function_exists( 'wpsf_insert_site_details' ) ) {
	/**
	 * Insert site details.
	 */
	function wpsf_insert_site_details() {
		$url          = WPSF_API_URL . '/wp-json/wpsf/v1/create_site';
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		$body = array(
			'site_url'   => get_site_url(),
			'api_key'    => wpsf_generate_api_key(),
			'user_email' => $user_email,
		);
		$args = array(
			'method'    => 'POST',
			'timeout'   => 45,
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => wp_json_encode( $body ),
		);

		$request     = wp_remote_post( $url, $args );
		$response    = json_decode( wp_remote_retrieve_body( $request ) );
		$wpsf_siteid = $response->wpsf_siteid;
		if ( 200 === $response->code ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_finish_step1' ) ) {
	/**
	 * Set flag of step1.
	 */
	function wpsf_finish_step1() {
		update_option( 'wpsf_step1_finished', 1 );
		die();
	}
}
add_action( 'wp_ajax_wpsf_finish_step1', 'wpsf_finish_step1' );

if ( ! function_exists( 'wpsf_finish_wizard' ) ) {
	/**
	 * Set flag of wizard.
	 */
	function wpsf_finish_wizard() {
		update_option( 'wpsf_wizard_finished', 1 );
		die();
	}
}
add_action( 'wp_ajax_wpsf_finish_wizard', 'wpsf_finish_wizard' );

if ( ! function_exists( 'wpsf_is_secure' ) ) {
	/**
	 * Callback to check HTTPS.
	 *
	 * @param string $url Site url.
	 */
	function wpsf_is_secure( $url ) {
		$options = array( 'ssl' => array( 'capture_peer_cert' => true ) );
		$context = stream_context_create( $options );
		$stream  = fopen( $url, 'rb', false, $context );
		if ( $stream ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsf_send_submission_mail' ) ) {
	/**
	 * Send new submission email
	 */
	function wpsf_send_submission_mail() {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		$url          = WPSF_API_URL . '/wp-json/wpsf/v1/send_mail';
		$plan         = 'free';
		if ( wpsf_fs()->is_plan( 'pro', true ) ) {
			$plan = 'pro';
		}
		$body = array(
			'user_email' => $user_email,
			'site_url'   => get_site_url(),
			'plan'       => $plan,
		);
		$args = array(
			'method'    => 'POST',
			'timeout'   => 45,
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => wp_json_encode( $body ),
		);

		$request  = wp_remote_post( $url, $args );
		$response = json_decode( wp_remote_retrieve_body( $request ) );
	}
}

if ( ! function_exists( 'wpsf_api_routes' ) ) {
	/**
	 * Callback for the API endpoints.
	 */
	function wpsf_api_routes() {
		register_rest_route(
			'wpsf/v1',
			'/handle_jotform',
			array(
				'methods'  => 'POST',
				'callback' => 'wpsf_jotforms_submit_callback',
			)
		);
	}
	add_action( 'rest_api_init', 'wpsf_api_routes' );
}
if ( ! function_exists( 'wpsf_form_data_list_page' ) ) {
	/**
	 * Create Admin page for listing the form data.
	 */
	function wpsf_form_data_list_page() {
		global $wpsf_is_api_validated, $wpsf_fs, $site_baa_status;
		$wizard_status = get_option( 'wpsf_wizard_finished', false );
		$is_paying = $wpsf_fs->is_paying();
		add_menu_page(
			'Secure Forms',
			'Secure Forms',
			'wpsf_capability',
			'wpsf-dashboard',
			'wpsf_dashboard_callback',
			'dashicons-lock',
		);
		$is_ssl_valid = wpsf_is_secure( get_site_url() );
		if ( ! $is_ssl_valid ) {
			return;
		}
		if ( $wpsf_is_api_validated && $site_baa_status && $wizard_status ) {
			add_submenu_page(
				'wpsf-dashboard',
				'View Submissions',
				'View Submissions',
				'wpsf_capability',
				'wpsf-form-data-page',
				'wpsf_form_data_content_callback',
				1,
			);
		}

		if ( $wpsf_is_api_validated && $site_baa_status && $wizard_status ) {
			add_submenu_page(
				'wpsf-dashboard',
				'Select Forms',
				'Select Forms',
				'wpsf_capability',
				'wpsf-forms-page',
				'wpsf_forms_content_callback',
				2,
			);
			add_submenu_page(
				'wpsf-dashboard',
				'Log',
				'Log',
				'wpsf_capability',
				'wpsf-log-page',
				'wpsf_log_content_callback',
				3,
			);
		}
	}
}
add_action( 'admin_menu', 'wpsf_form_data_list_page' );
