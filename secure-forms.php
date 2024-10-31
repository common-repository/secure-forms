<?php
/**
 *
 * Plugin Name: Secure Forms
 * Version: 1.0.3
 * Plugin URI: https://wpsecureforms.com/
 * Description: Plugin that encrypts Formdata before submission.
 * Author: ClikIT
 * Author URI:https://clikitnow.com/
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Text Domain: secure-forms
 * License: GPLv2 or later
 *
 * @package secure-forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'WPSF_PLUGIN_FILE', __FILE__ );
if ( function_exists( 'wpsf_fs' ) ) {
	wpsf_fs()->set_basename( true, __FILE__ );
} else {
	if ( ! function_exists( 'wpsf_fs' ) ) {
		/**
		 * Create a helper function for easy SDK access.
		 */
		function wpsf_fs() {
			global $wpsf_fs;
			if ( ! isset( $wpsf_fs ) ) {
				// Include Freemius SDK.
				require_once __DIR__ . '/freemius/start.php';
				$wpsf_fs = fs_dynamic_init(
					array(
						'id'                  => '15764',
						'slug'                => 'secure-forms',
						'premium_slug'        => 'secure-forms-pro',
						'type'                => 'plugin',
						'public_key'          => 'pk_ca451966bac9629bbe5ecd1a2d3f9',
						'is_premium'          => false,
						'premium_suffix'      => 'Pro',
						// If your plugin is a serviceware, set this option to false.
						'has_premium_version' => true,
						'has_addons'          => false,
						'has_paid_plans'      => true,
						'menu'                => array(
							'slug'       => 'wpsf-dashboard',
							'first-path' => 'admin.php?page=wpsf-dashboard',
							'support'    => false,
							'account'    => true,
							'parent'     => array(
								'slug' => 'wpsf-dashboard',
							),
						),
					)
				);
			}
			return $wpsf_fs;
		}
		// Init Freemius.
		wpsf_fs();
		// Signal that SDK was initiated.
		do_action( 'wpsf_fs_loaded' );
	}
	require_once __DIR__ . '/includes/wpsf-functions.php';
}
