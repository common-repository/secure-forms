<?php
/**
 * Templage for selecting the forms.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
	global $user_email, $wpsf_id;
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

	$request  = wp_remote_post( $url, $args );
	$response = json_decode( wp_remote_retrieve_body( $request ) );
	if ( 404 === $response->status ) {
		?>
		<div class="wpsf_no_form_data">
			<strong>
			<?php
				echo esc_html( 'No Submissions Yet' );
			?>
			</strong>
			<p>
			<?php
				echo esc_html( 'It looks like there are no submissions at the moment. Check back later or be the first to submit!' );
			?>
			</p>
		</div>
		<?php
		return;
	}
	$site_forms_data = $response->form_data;
	if ( ! function_exists( 'wpsf_get_group_fields' ) ) {
		/**
		 * Get fields assigned to the group
		 *
		 * @param object $form current form.
		 * @param string $slug field slug.
		 * @param array  $form_data current form data.
		 */
		function wpsf_get_group_fields( $form, $slug, $form_data ) {
			$values = array();
			foreach ( $form->fields as $field ) {
				if ( $field->parent_group == $slug ) {
					$values[] = $form_data[ $field->slug ]->value;
				}
			}
			return $values;
		}
	}

	if ( ! function_exists( 'wpsf_get_user_ip' ) ) {
		/**
		 *
		 * Get user's IP Address.
		 */
		function wpsf_get_user_ip() {
			foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
				if ( array_key_exists( $key, $_SERVER ) === true ) {
					foreach ( explode( ',', sanitize_text_field( wp_unslash( ( $_SERVER[ $key ] ) ) ) ) as $ip ) {
						$ip = trim( $ip );
						if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
							return $ip;
						}
					}
				}
			}
		}
	}
	?>
	
	<div class="wpsf_form_data_container">	
		<div class="header">
			<h1><?php echo esc_html( 'View Submissions' ); ?></h1> 
		</div>
		<div class="table">
			<div class="row thead">
				<div class="date_title title">
					<strong><?php echo esc_html( 'Date' ); ?></strong>
				</div>
				<div class="title">
					<strong><?php echo esc_html( 'Form' ); ?></strong>
				</div>
			</div>
			<?php
			$key = wpsf_get_key();
			foreach ( $site_forms_data as $single_form ) {
					$form_id = $single_form->form_id;
					$form    = Forminator_API::get_form( $form_id );
					$encrypted_form_data  = $single_form->entry_data;
					$decryptted_form_data = wpsf_decrypt_aes256( $encrypted_form_data, $key );
					$form_data            = json_decode( $decryptted_form_data );
					$field_data           = (array) $form_data->meta_data;
					$logged_user          = wp_get_current_user();
				?>
					<div class="row">
						<div class="summary">
							<div class="date">
								<label>
									<?php echo esc_html( $single_form->date_created ); ?>
								</label>
							</div>
							<div class="form_name">
								<label><?php echo esc_html( 'Form : ' ); ?>
								<?php
								if ( $form->settings['formName'] ) {
									echo esc_html( $form->settings['formName'] );
								}
								?>
								</label>
							</div>
							<div class="action">
								<?php
									$user_name  = $logged_user->display_name;
									$user_id    = $logged_user->ID;
									$ip_address = wpsf_get_user_ip();
									$event      = 'view';
									$wpsfid = get_option( 'wpsf_siteid' );
								?>
								<a href="" class="wpsf_view_details" 
									data-user_name="<?php echo esc_html( $user_name ); ?>" 
									data-form_id="<?php echo esc_html( $form_id ); ?>" 
									data-user_id="<?php echo esc_html( $user_id ); ?>"
									data-ip_address="<?php echo esc_html( $ip_address ); ?>"
									data-event="<?php echo esc_html( $event ); ?>"
									data-wpsfid="<?php echo esc_html( $wpsf_id ); ?>">	
									<?php echo esc_html( 'View Details' ); ?>
								</a>
							</div>
						</div>
						<div class="wpsf_details">
							<?php
							foreach ( $form->fields as $field ) {
								$field_type        = $field->type;
								$slug              = $field->slug;
								$single_field_data = $field_data[ $slug ];
								switch ( $field_type ) {
									case 'name':
										if ( '' !== $field->parent_group ) {
											break;
										}
										if ( gettype( $single_field_data->value ) === 'string' ) {
											?>
											<div class="field_row">
												<div class="lable">
													<strong>
														<?php
															echo esc_html( $field->field_label );
														?>
													</strong>
												</div>													
												<div class="value">
													<?php
														echo esc_html( $single_field_data->value );
													?>
												</div>
											</div>												
											<?php
										} else {
											$field_value = (array) $single_field_data->value;
											?>
											<div class="field_row">
												<div class="lable">
													<strong>
														<?php
															echo esc_html( $field->field_label );
														?>
													</strong>
												</div>													
												<div class="value">
													<?php
														echo esc_html( $field_value['prefix'] . ' ' . $field_value['first-name'] . ' ' . $field_value['last-name'] );
													?>
												</div>
											</div>	
											<?php
										}
										break;
									case 'email':
									case 'phone':
									case 'text':
									case 'url':
									case 'textarea':
									case 'number':
									case 'select':
									case 'date':
									case 'currency':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
												<div class="lable">
													<strong>
														<?php
															echo esc_html( $field->field_label );
														?>
													</strong>
												</div>													
												<div class="value">
													<?php
														echo esc_html( $single_field_data->value );
													?>
												</div>
											</div>	
										<?php
										break;
									case 'address':
										if ( '' !== $field->parent_group ) {
											break;
										}
										$address = (array) $single_field_data->value;
										?>
										<div class="field_row">
												<div class="lable">
													<strong><?php echo esc_html( 'Address:' ); ?></strong>
												</div>													
												<div class="value">
													<?php
													echo esc_html( implode( ', ', $address ) );
													?>
												</div>
											</div>	
										<?php
										break;
									case 'radio':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
														echo esc_html( $field->field_label );
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
													echo esc_html( $single_field_data->value );
												?>
											</div>
										</div>
										<?php
										break;
									case 'checkbox':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
														echo esc_html( $field->field_label );
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
												echo esc_html( $single_field_data->value );
												?>
											</div>
										</div>
										<?php
										break;
									case 'upload':
										if ( '' !== $field->parent_group ) {
											break;
										}
										$file_data = $single_field_data->value;
										$file_name = basename( $file_data->file->file_path );
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
														echo esc_html( $field->field_label );
													?>
												</strong>
											</div>													
											<div class="value">
												<a target='_new' href='<?php echo esc_attr( $file_data->file->file_url ); ?>'>
													<?php echo esc_html( $file_name ); ?>
												</a>
											</div>
										</div>
										<?php
										break;
									case 'calculation':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php echo esc_html( 'Calculation' ); ?>
												</strong>
											</div>		
											<div class="value">
												<?php
													echo esc_html( $single_field_data->value->formatting_result );
												?>
											</div>
										</div>
										<?php
										break;
									case 'time':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
														echo esc_html( $field->field_label );
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
												$hours             = $single_field_data->value->hours;
												$minutes           = $single_field_data->value->minutes;
												$ampm              = $single_field_data->value->ampm;
												$formatted_hours   = sprintf( '%02d', $hours );
												$formatted_minutes = sprintf( '%02d', $minutes );
												$time_string       = $formatted_hours . ':' . $formatted_minutes . ' ' . $ampm;
												echo esc_html( $time_string );
												?>
											</div>
										</div>
										<?php
										break;
									case 'postdata':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
														echo esc_html( 'Post Data' );
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
												foreach ( $single_field_data->value->value as $key => $value ) {
													$label = str_replace( '-', ' ', $key );
													$label = ucwords( $label );
													echo esc_html( $label . ': ' . $value ) . '<br/>';
												}
												?>
											</div>
										</div>
										<?php
										break;
									case 'hidden':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
													if ( '' !== $field->field_label ) {
														echo esc_html( $field->field_label );
													} else {
														echo 'Hidden';
													}
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
													echo esc_html( $single_field_data->value );
												?>
											</div>
										</div>
										<?php
										break;
									case 'stripe':
										if ( '' !== $field->parent_group ) {
											break;
										}
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
													if ( '' !== $field->field_label ) {
														echo esc_html( $field->field_label );
													} else {
														echo 'Stripe';
													}
													?>
												</strong>
											</div>													
											<div class="value">
											</div>
										</div>
										<?php
										break;
									case 'signature':
										if ( '' !== $field->parent_group ) {
											break;
										}
										$url = $single_field_data->value->file->file_url;
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
													if ( '' !== $field->field_label ) {
														echo esc_html( $field->field_label );
													} else {
														echo 'Signature';
													}
													?>
												</strong>
											</div>													
											<div class="value">
												<img src="<?php echo esc_url( $url ); ?>" width='100'/>
											</div>
										</div>
										<?php
										break;
									case 'slider':
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
													if ( '' !== $field->field_label ) {
														echo esc_html( $field->field_label );
													} else {
														echo 'Group';
													}
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
													echo esc_html( $single_field_data->value );
												?>
											</div>
										</div>
										<?php
										break;
									case 'group':
										?>
										<div class="field_row">
											<div class="lable">
												<strong>
													<?php
													if ( '' !== $field->field_label ) {
														echo esc_html( $field->field_label );
													} else {
														echo 'Group';
													}
													?>
												</strong>
											</div>													
											<div class="value">
												<?php
													$values = wpsf_get_group_fields( $form, $field->slug, $field_data );
												?>
											</div>
										</div>
										<?php
										break;
									default:
								}
							}
							?>
						</div>
					</div>
					<?php
			}
			?>
		</div>
	</div>

	<?php
