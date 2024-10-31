<?php
/**
 * Template for API Wizard.
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_enqueue_style( 'wpsf-wizard-pricing' );
wp_enqueue_style( 'wpsf-wizard-pricing-common' );
wp_enqueue_script( 'wpsf-freemius-checkout-script' );
wp_enqueue_script( 'wpsf-jotform-embed-script' );
wp_enqueue_script( 'wpsf-wizard-page-script' );
?>
<?php
	global $wpsf_is_api_validated, $wpsf_fs;
	$step1_flag = get_option( 'wpsf_step1_finished', false );
?>
<div id="wpsf_wizard">
		<ul class="nav">
				<?php if ( ! $step1_flag ) { ?>
				<li class="nav-item">
					<a class="nav-link" href="#step-1">
						Select Plan
					</a>
				</li>
				<?php } ?>
				<?php
				if ( ! $wpsf_is_api_validated ) {
					?>
				<li class="nav-item">
					<a class="nav-link" href="#step-2">
						Request API
					</a>
				</li>				
				<li class="nav-item">
					<a class="nav-link" href="#step-3">
						Validate API
					</a>
				</li>
				<?php } ?>
				<li class="nav-item">
					<a class="nav-link" href="#step-4">
						Finished
					</a>
				</li>
		</ul>
		<hr/>
		<div class="tab-content">
			<?php if ( ! $step1_flag ) { ?>
				<div id="step-1" class="tab-pane" role="tabpanel" aria-labelledby="step-1">
					<div class="wpsf_packages_wrapper">
						<div class="wpsf_plan free">
							<header>
								Free
							</header>
							<div class="wpsf_price">
								<h2>Free</h2>
								<p>Single Site</p>
							</div>
							<div class="wpsf_feautures">
								<p></p>
								<ul>
									<li data-id="17347" class="tooltip-wrapper info-icon">
										<span title="Select 1 form to be routed through our service">1 Form</span>
									</li>
									<li data-id="17347" class="tooltip-wrapper info-icon">
										<span title="Only 25 submissions are saved.">25 Submissions</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="No email notifications on the form submission.">No Email Notifications</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Basic Support.">Basic Support</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Can Sign BAA.">Can Sign BAA</span>
									</li>
									<li data-id="17367">
										<span title="">1 Live Domain</span>
									</li>
								</ul>
								<button>Continue with free</button>
							</div>
						</div>
						<div class="wpsf_plan pro">
							<header>
								Secure Forms Pro
							</header>
							<div class="wpsf_price">
								<h2>
									$25.99 / Month
								</h2>
								<p>Billing Monthly Single Site</p>
							</div>
							<div class="wpsf_feautures">
								<p>Priority Email Support</p>
								<button id="pro-purchase" class="btn-primary"><span>Upgrade Now</span><img src="https://wimg.freemius.com/website/pages/pricing/cards.png" alt=""></button>
								<ul>
									<li data-id="17347" class="tooltip-wrapper info-icon">
										<span title="Select unlimited form to be routed through our service.">Unlimited Forms</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Unlimited Form Submissions.">Unlimited Submissions</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Unlimited Email Notifications.">Unlimited Email Notifications</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Export unlimited form submissions and logs.">Unlimited Logs</span>
									</li>
									<li data-id="17348" class="tooltip-wrapper info-icon">
										<span title="Can Sign BAA.">Can Sign BAA</span>
									</li>
									<li data-id="17366" class="tooltip-wrapper info-icon">
										<span title="Premium Support/">Premium Support</span>
									</li>
									<li data-id="17367">
										<span title="1 Live Domain.">1 Live Domain</span>
									</li>
									<li data-id="17367">
										<span title="Export your logs & form.">Export your logs & form</span>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
			<?php
			if ( ! $wpsf_is_api_validated ) {
				?>
			<div id="step-2" class="tab-pane" role="tabpanel" aria-labelledby="step-2">
					<?php
						include_once 'form-api-key.php';
					?>
			</div>			
			<div id="step-3" class="tab-pane" role="tabpanel" aria-labelledby="step-3">
					<?php include_once 'form-validate-api.php'; ?>
			</div>
			<?php } ?>
			<div id="step-4" class="tab-pane" role="tabpanel" aria-labelledby="step-4">
				<?php
				$baa_status   = get_transient( 'baa_status' );
				if ( ! $baa_status ) {
					$checked_no   = 'checked';
				} else {
					$checked_yes  = ( '1' === $baa_status ) ? 'checked' : '';
					$checked_no   = ( '0' === $baa_status ) ? 'checked' : '';
				}
				?>
				<div class="wpsf_baa_form_wrapper">
					<div class="wpsf_baa_label">
						<label>
							<input type="radio" name="wpsf_baa_checked" <?php echo esc_attr( $checked_yes ); ?> value="1" id="wpsf_baa_yes" class="wpsf_baa_checkbox"/>							
							<?php echo esc_html( "Yes, I need to sign a BAA (Any organization that handles Personal Healthcare Information (PHI). If you're not sure, ask your legal counsel)" ); ?>
						</label>
						<label>
							<input type="radio" name="wpsf_baa_checked" <?php echo esc_attr( $checked_no ); ?> value="0" id="wpsf_baa_no" class="wpsf_baa_checkbox"/>							
							<?php echo esc_html( 'No, I do not need to sign a BAA because I do not handle Personal Healthcare Information (PHI)' ); ?>
						</label>
					</div>
					<div class="baa_form">
						<iframe
							id="JotFormIFrame-241731125683050"
							title="Form"
							onload="window.parent.scrollTo(0,0)"
							allowtransparency="true"
							allow="geolocation; microphone; camera; fullscreen"
							src="https://form.jotform.com/241731125683050"
							frameborder="0"
							style="min-width:100%;max-width:100%;height:539px;border:none;"
							scrolling="no"
							>
						</iframe>
					</div>
					<button class="sw-btn-finish">Finish</button>	 
				</div>
			</div>
		</div>
 
		<!-- Include optional progressbar HTML -->
		<div class="progress">
			<div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
</div>