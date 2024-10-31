<?php
/**
 * Template for log.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wpsf_form_data_container">	
	<div class="table">
		<?php
		global $user_email, $wpsf_id;
		$records_per_page = 20;
		$current_page     = 1;
		$nonce            = isset( $_GET['wpsf_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['wpsf_wpnonce'] ) ) : '';
		if ( isset( $_GET['wpsf_wpnonce'] ) && wp_verify_nonce( $nonce, 'wpsf_pagination_nonce' ) ) {
			if ( isset( $_GET['wpsf_page'] ) && is_numeric( $_GET['wpsf_page'] ) ) {
				$current_page = (int) $_GET['wpsf_page'];
			}
		}
		$url    = WPSF_API_URL . '/wp-json/wpsf/v1/get_sites_log';
		$body   = array(
			'site_url'    => get_site_url(),
			'page'        => $current_page,
			'wpsf_siteid' => $wpsf_id,
			'user_email'  => $user_email,
		);

		$args = array(
			'method'    => 'GET',
			'sslverify' => true,
			'headers'   => array( 'wpsf-token' => wp_get_session_token() ),
			'body'      => $body,
		);

		$request       = wp_remote_post( $url, $args );
		$response      = json_decode( wp_remote_retrieve_body( $request ) );
		$site_log_data = $response->form_log_data;
		$total_records = $response->total_records;
		if ( 404 === $response->status ) {
			?>
			<div class="wpsf_no_form_data">
				<strong>
				<?php
					echo esc_html( 'No Logs generated Yet' );
				?>
				</strong>
				<p>
				<?php
					echo esc_html( 'It looks like there are no logs available at the moment. Check back later!' );
				?>
				</p>
		</div>
			<?php
			return;
		}
		$site_forms_data = $response->form_data;
		?>
		<div class="row thead">
					<div class="summary">
						<div class="date">
							<strong>
								<?php echo esc_html( 'Form ID' ); ?>
							</strong>
						</div>
						<div class="date">
							<strong>
								<?php echo esc_html( 'User Name' ); ?>
							</strong>
						</div>
						<div class="form_name">
							<strong>
							<?php
								echo esc_html( 'Date' );
							?>
							</strong>
						</div>
						<div class="form_name">
							<strong>
							<?php
								echo esc_html( 'Time' );
							?>
							</strong>
						</div>
						<div class="form_name">
							<strong>
							<?php
								echo esc_html( 'IP Address' );
							?>
							</strong>
						</div>
					</div>
				</div>
		<?php
		foreach ( $site_log_data as $single_form ) {
				$username = $single_form->user_name;
			?>
				<div class="row">
					<div class="summary">
						<div class="date">
							<label>
								<?php
									$form = Forminator_API::get_form( $single_form->form_id );
									echo esc_html( $single_form->form_id );
								?>
							</label>
						</div>
						<div class="date">
							<label>
								<?php echo esc_html( $username ); ?>
							</label>
						</div>
						<div class="form_name">
							<?php
								echo esc_html( gmdate( 'm-d-Y', strtotime( $single_form->date_visited ) ) );
							?>
						</div>
						<div class="form_name">
							<?php
								echo esc_html( gmdate( 'g:i A', strtotime( $single_form->date_visited ) ) );
							?>
						</div>
						<div class="form_name">
							<?php
								echo esc_html( $single_form->ip_address );
							?>
						</div>
					</div>
				</div>
				<?php
		}
		?>
	</div>
</div>
<?php
$nonce            = wp_create_nonce( 'wpsf_pagination_nonce' );
$total_pages      = ceil( $total_records / $records_per_page );
$request_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'], '?' ) ) : '';
$base_url    = get_site_url() . '/wp-admin/admin.php?page=wpsf-log-page&wpsf_wpnonce=' . $nonce;
?>
<ul class='wpsf_pagination'>
<?php

if ( $current_page > 1 ) {
	$url = $base_url . '&wpsf_page=' . ( $current_page - 1 );
	?>
	<li><a href='<?php echo esc_url( $url ); ?>'>Previous</a></li>
	<?php
}

for ( $i = 1; $i <= $total_pages; $i++ ) {
	if ( $i == $current_page ) {
		?>
		<li class='active'>
			<a href="<?php echo esc_url( $base_url ); ?>&wpsf_page=<?php echo esc_attr( $i ); ?>">
				<?php echo esc_attr( $i ); ?>
			</a>
		</li>
		<?php
	} else {
		?>
		<li>
			<a href="<?php echo esc_url( $base_url ); ?>&wpsf_page=<?php echo esc_attr( $i ); ?>">
				<?php echo esc_attr( $i ); ?>
			</a>
		</li>
		<?php
	}
}

if ( $current_page < $total_pages ) {
	?>
	<li>
		<a href="<?php echo esc_url( $base_url ); ?>&wpsf_page=<?php echo esc_attr( $current_page + 1 ); ?>">
			Next
		</a>
	</li>
	<?php
}
?>
</ul>
<?php