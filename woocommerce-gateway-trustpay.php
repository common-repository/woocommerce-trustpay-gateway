<?php
/**
 * Plugin Name: WooCommerce Trustpay Gateway
 * Description: Trustpay payment gateway for WooCommerce.
 * Version: 1.4.0
 * Author: trustpay
 * Author URI: http://trustpay.biz
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_TRUSTPAY_VERSION', '1.4.0' );
define( 'WC_TRUSTPAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_TRUSTPAY_MAIN_FILE', __FILE__ );
define( 'WC_TRUSTPAY_TRANS', 'wctrustpay' );

/**
 * Main Trustpay class which sets the gateway up for us
 */
class WC_Trustpay {

	/**
	 * Constructor
	 */
	function __construct() {
		// Actions
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
	}

	/**
	 * Initialize localisations and plugin files
	 */
	function init() {
		// Check if WooCommerce requirements are met
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_fallback_notice' ) );
			return;
		}

		// Includes
		include_once( 'includes/class-wp-gateway-trustpay.php' );

		// Localisation
		load_plugin_textdomain( 'wctrustpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Error message reminding user to upgrade WooCommerce.
	 */
	function woocommerce_fallback_notice() {
		return
			'<div class="error">' .
				'<p>' . sprintf( __( 'WooCommerce TrustPay Gateway depends on the last version of <a href="%s" target="_blank">WooCommerce</a> to work!', WC_TRUSTPAY_TRANS ), 'https://wordpress.org/plugins/woocommerce/' ) . '</p>' .
			'</div>';
	}

	/**
	 * Register the gateway for use
	 */
	function register_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Trustpay';
		return $methods;
	}

	/**
	 * Adds action link to local documentation.
	 *
	 * @since 1.3.2
	 */
	function action_links( $links )
	{
		$links[] = '<a href="'. esc_url( WC_TRUSTPAY_PLUGIN_URL . '/getting-started.pdf' ) .'" target="_blank">Documentation</a>';

		return $links;
	}

}

$wc_trustpay = new WC_Trustpay();
