<?php
/**
 * Template for Dashboard.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpsf_is_api_validated;

$is_ssl_valid = wpsf_is_secure( get_site_url() );
if ( ! $is_ssl_valid ) {
	?>
	<h1>Invalid SSL certificate. please install a valid SSL certificate to start using the features.</h2>
	<?php
	return;
}
if ( ! $wpsf_is_api_validated ) {
	?>
	<h1> Please <a href="<?php echo esc_url( get_site_url() ); ?>/wp-admin/admin.php?page=wpsf-forms-api-page" >Validate The API</a> first. </h1>
	<?php
	return;
} else {
	?>
	<h2>Dashboard</h2>
	<div class="wpsf_dashboard_wrapper">
		<ul>
			<li><a href="<?php site_url(); ?>/wp-admin/admin.php?page=wpsf-forms-page">Select Form</a></li>
			<li><a href="<?php site_url(); ?>/wp-admin/admin.php?page=wpsf-form-data-page">View Submissions</a></li>
		</ul>
	</div>
	<?php
}
