<?php
/**
 * Template for API Key Form.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpsf_is_api_validated;
if ( isset( $_POST['wpsf_email_addr'] ) ) {
	check_admin_referer( 'wpsf-request-api-key-field', '_wpnonce_request_api_key' );
	$email_addr = sanitize_email( wp_unslash( $_POST['wpsf_email_addr'] ) );
	if ( ! is_email( $email_addr ) ) {
		return;
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
}

?>
<div class="wpsf_success">
	<p>Please Check Your Inbox</p>
</div>
<div class="wrap">
	<div class="wpsf_request_api">
		<h1> <?php echo esc_html( 'Request API Key' ); ?></h1>
		<form action="#" method="post" name="wpsf_select_form" id="wpsf_select_form">
			<?php
				wp_nonce_field( 'wpsf-request-api-key-field', '_wpnonce_request_api_key' );
			?>
			<div class="wpsf_api_field_wrapper">
				<label class="wpsf_email"> <?php echo esc_html( 'Enter Email Address' ); ?> </label>
				<input type="text" name='wpsf_email_addr' id="wpsf_email_addr"/>
			</div>			
			<div class="wpsf_aggree">
				<label>
					<input type="checkbox" name="wpsf_terms" id="wpsf_terms" value="yes"/>
					<?php echo esc_html( 'I agree to' ); ?> <a href="https://clikitnow.com/wp-secure-forms-terms-of-service/" target="_new"> <?php echo esc_html( 'terms of service' ); ?></a>
				</label>
			</div>			
			<button class="button button-primary wpsf_request_api"><?php echo esc_html( 'Submit' ); ?></button>
		</form>
	</div>
</div>
