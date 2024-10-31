<?php
/**
 * Template for Validate API Form.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpsf_is_api_validated;
if ( isset( $_POST['wpsf_api_key'] ) ) {
	check_admin_referer( 'wpsf-api-key-field', '_wpnonce_api_key' );
	$api_key = sanitize_text_field( wp_unslash( $_POST['wpsf_api_key'] ) );
	$result  = wpsf_validate_api( $api_key );
	if ( $result ) {
		update_option( 'wpsf_validated', true );
		$url = get_site_url() . '/wp-admin/admin.php?page=wpsf-form-data-page';
		?>
		<h4>
			<?php echo esc_html( 'API Validated Successfully, start selecting the forms to encrypt from ' ); ?> <a href="<?php echo esc_url( $url ); ?>" >
				<?php echo esc_html( 'here' ); ?>
			</a>.
		</h4>
		<?php
	} else {
		echo esc_html( 'No API Data Found' );
	}
}
?>
<div class="wpsf_success_validate">
	<span class="dashicons dashicons-yes-alt"></span>
	<p><?php echo esc_html( 'API Validated Successfully' ); ?></p>
</div>
<div class="wrap">
	<div class="wpsf_enter_api">
		<h1> <?php echo esc_html( 'Validate API Key ' ); ?></h1>
		<form action="#" method="post" name="wpsf_select_form" id="wpsf_select_form">
			<?php
				wp_nonce_field( 'wpsf-api-key-field', '_wpnonce_api_key' );
			?>
			<div class="form-table">				
				<label for="wpsf_form"><?php echo esc_html( 'Enter API Key' ); ?> </label>
				<input type="text" name='wpsf_api_key' id="wpsf_api_key"/>
				<button class="button button-primary wpsf_validate_api"><?php echo esc_html( 'Submit' ); ?></button>
			</div>
			
		</form>
	</div>
</div>
