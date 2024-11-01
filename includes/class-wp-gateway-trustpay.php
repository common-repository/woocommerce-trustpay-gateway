<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* WC_Gateway_Trustpay class.
*
* @extends WC_Gateway_Trustpay
*/
class WC_Gateway_Trustpay extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->id                 = 'trustpay';
		$this->method_title       = __( 'Trustpay', WC_TRUSTPAY_TRANS );
		$this->method_description = __( 'Trustpay Gateway works by sending the user to Trustpay to enter their payment information.', WC_TRUSTPAY_TRANS );
		$this->has_fields         = false;
		$this->api_endpoint       = 'https://my.trustpay.biz/TrustPayWebClient/TransactPost';
		$this->supports           = array( 'products' );

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title              = $this->settings['title'];
		$this->description        = $this->settings['description'];
		$this->enabled            = $this->settings['enabled'];
		$this->app_key            = $this->settings['app_key'];
		$this->debug              = $this->settings['debug'];
		$this->successpostbackurl = $this->settings['successpostbackurl'];
		$this->failurepostbackurl = $this->settings['failurepostbackurl'];

		if ( 'yes' == $this->settings['istest'] ) {
			$this->description .= ' ' .__( 'TEST MODE ENABLED.', WC_TRUSTPAY_TRANS );
			$this->description  = trim( $this->description );
		}

		// Logo
		if ( isset( $this->settings['gateway_logo'] ) ) {
			$this->icon = apply_filters( 'wc_trustpay_icon', plugins_url( '/assets/images/'. $this->settings['gateway_logo'], dirname( __FILE__ ) ) );
		} else {
			$this->icon = apply_filters( 'wc_trustpay_icon', plugins_url( '/assets/images/trustpay.png', dirname( __FILE__ ) ) );
		}

		// Valid for use.
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Hooks
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_receipt_trustpay', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_gateway_trustpay', array( $this, 'check_tpn_response' ) );
		add_action( 'valid_trustpay_tpn_request', array( $this, 'successful_request' ) );

		// JS hacks
		add_action( 'admin_print_scripts', array( $this, 'admin_inline_js' ) );
	}

	/**
	 * JS Hacks
	 */
	function admin_inline_js(){
		?>
		<script type="text/javascript">
			window.onload = function () {
				var tmp = document.getElementById('woocommerce_trustpay_notificationurl');
				if ( tmp !== null ) {
					tmp.style.display = "none";
				}
			};
		</script>
		<?php
	}

	/**
	 * Admin nofifications
	 */
	function admin_notices() {
		if ( $this->enabled == 'no' ) {
			return;
		}

		// Application ID is missing
		if ( $this->settings['app_key'] == '' ) {
			add_action( 'admin_notices', array( $this, 'application_id_missing_message' ) );
		}
	}

	/**
	 * Adds error message when app_key is not configured.
	 *
	 * @return string Error Mensage.
	 */
	function application_id_missing_message() {
		return
			'<div class="error">' .
				'<p>' . sprintf( __( '<strong>Gateway Disabled</strong> TrustPay Application ID was not found. <a href="%s">Click here to configure!</a>', WC_TRUSTPAY_TRANS ), esc_url( get_admin_url() . 'admin.php?page=woocommerce_settings&tab=payment_gateways' )  ) . '</p>';
			'</div>';
	}

	/**
	 * Checking if this gateway is enabled and available in the user's country.
	 *
	 * @return bool
	 */
	function is_valid_for_use() {
		$is_available = false;

		if ( $this->enabled == 'yes' && $this->settings['app_key'] != '' ) {
			$is_available = true;
		}

		return $is_available;
	}

	/**
	 * Start Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', WC_TRUSTPAY_TRANS ),
				'label'       => __( 'Enable Trustpay Gateway', WC_TRUSTPAY_TRANS ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'This controls whether the plugin is active on the checkout page.', WC_TRUSTPAY_TRANS )
			),
			'title' => array(
				'title'       => __( 'Title', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', WC_TRUSTPAY_TRANS ),
				'default'     => __( 'Trustpay', WC_TRUSTPAY_TRANS ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', WC_TRUSTPAY_TRANS ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', WC_TRUSTPAY_TRANS ),
				'default'     => __( 'Pay with TrustPay Methods', WC_TRUSTPAY_TRANS ),
				'desc_tip'    => true
			),
			'app_key' => array(
				'title'       => __( 'Application ID', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => __( 'Please enter your Trustpay Application ID.', WC_TRUSTPAY_TRANS ) . ' ' . sprintf( __( 'You can to get this information from your %sTrustPay Account%s.', WC_TRUSTPAY_TRANS ), '<a href="https://my.trustpay.biz" target="_blank">', '</a>' ),
				'default'     => ''
			),
			'notificationurl' => array(
				'title'       => __( 'Notification URL', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => site_url( '/wc-api/WC_Gateway_TrustPay/' ),
			),
			'sharedsecret' => array(
				'title'       => __( 'Shared Secret', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => __( 'Please enter the Shared Secret.', WC_TRUSTPAY_TRANS ). ' ' . sprintf( __( 'You can to get this information from your %sTrustPay Account%s.', WC_TRUSTPAY_TRANS ), '<a href="https://my.trustpay.biz" target="_blank">', '</a>' ),
				'default'     => ''
			),
			'customeridentification' => array (
				'title'       => __( 'Customer Identification', WC_TRUSTPAY_TRANS ),
				'type'        => 'select',
				'description' => __( 'Eeach customer must be identified. Pick any of identification options listed above, when username/email is not available plugin will default to firstname lastname option.', WC_TRUSTPAY_TRANS ),
				'options'     => array(
					'firstnamelastname' => __( 'Firstname Lastname', WC_TRUSTPAY_TRANS ),
					'username'           => __( 'Username', WC_TRUSTPAY_TRANS ),
					'emailaddress'      => __( 'Email Address', WC_TRUSTPAY_TRANS ),
				),
				'std'     => 'firstnamelastname', // WooCommerce < 2.0
				'default' => 'firstnamelastname', // WooCommerce >= 2.0
			),
			'successpostbackurl' => array(
				'title'       => __( 'Success Postback URL', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => __( 'Optional. Leave blank for default WooCommerce success URL.', WC_TRUSTPAY_TRANS ),
			),
			'failurepostbackurl' => array(
				'title'       => __( 'Failure Postback URL', WC_TRUSTPAY_TRANS ),
				'type'        => 'text',
				'description' => __( 'Optional. Leave blank for default WooCommerce fail URL.', WC_TRUSTPAY_TRANS ),
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', WC_TRUSTPAY_TRANS ),
				'type'        => 'title',
				'description' => '',
				'desc_tip'    => true
			),
			'gateway_logo' => array(
				'title'       => __( 'Payment Gateway Logo', WC_TRUSTPAY_TRANS ),
				'type'        => 'select',
				'options'     => array(
					'trustpay.png'   => __( 'Default', WC_TRUSTPAY_TRANS ),
					'trustpay-2.png' => __( 'Blue&White', WC_TRUSTPAY_TRANS ),
					'trustpay-3.png' => __( 'Black&White', WC_TRUSTPAY_TRANS ),
				),
				'std'     => 'trustpay.png', // WooCommerce < 2.0
				'default' => 'trustpay.png', // WooCommerce >= 2.0
			),
			'istest' => array(
				'title'       => __( 'Test Mode', WC_TRUSTPAY_TRANS ),
				'label'       => __( 'Enable Development/Test Mode', WC_TRUSTPAY_TRANS ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'This sets the payment gateway in development mode.', WC_TRUSTPAY_TRANS ),
				'desc_tip'    => true
			),
			'debug' => array(
				'title'       => __( 'Debug Log', WC_TRUSTPAY_TRANS ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', WC_TRUSTPAY_TRANS ),
				'default'     => 'no',
				'description' => __( 'Log Trustpay events, such as API requests, inside <code>woocommerce/logs/trustpay.txt</code>', WC_TRUSTPAY_TRANS  )
			)
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		$woocommerce->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

	/**
	 * Output for the order received page.
	 *
	 * @return void
	 */
	function receipt_page( $order ) {
		echo '<p>'. __( 'Thank you for your order, please click the button below to pay with TrustPay.', WC_TRUSTPAY_TRANS ) . '</p>';

		echo $this->generate_truspay_form( $order );
	}

	/**
	 * Generate TrustPay button link.
	 */
	function generate_truspay_form( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Prepare the success order fallback url
		if ( empty( $this->settings['successpostbackurl'] ) ) {
			$successUrl = $this->get_return_url( $order );
		} else {
			$successUrl = $this->settings['successpostbackurl'];
		}

		// Prepare the fail/cancel order fallback url
		if ( empty( $this->settings['failurepostbackurl'] ) ) {
			$cancelUrl = $order->get_cancel_order_url();
		} else {
			$cancelUrl = $this->settings['failurepostbackurl'];
		}

		// Prep appuser
		$appuser = $order->billing_first_name . ' ' . $order->billing_last_name;
		$user    = wp_get_current_user();
		if ( $user->exists() && isset( $this->settings['customeridentification'] ) ) {
			if ( $this->settings['customeridentification'] == 'username' ) {
				$appuser = $user->user_login;
			} elseif( $this->settings['customeridentification'] == 'emailaddress' ) {
				$appuser = $user->user_email;
			}
		}

		// TrustPay Account related details
		$this->data_to_send = array(
			'vendor_id'     => $this->settings['app_key'],
			'appuser'       => $appuser,
			'currency'      => $order->order_currency,
			'amount'        => $order->order_total,
			'txid'          => sprintf( '%d-%s', $order->id, uniqid( time() ) ), // Makes ID unique for each checkout
			'fail'          => $cancelUrl,
			'success'       => $successUrl,
			'message'       => sprintf( __( 'New order from %s', WC_TRUSTPAY_TRANS ), get_bloginfo( 'name' ) ),
			'istest'        => ( $this->settings['istest'] == 'no' ) ? 'false' : 'true',
			'firstname'     => $order->billing_first_name,
			'lastname'      => $order->billing_last_name,
			'email_address' => $order->billing_email,
			'msisdn'        => $order->billing_phone,
			'street'        => $order->billing_address_1,
			'city'          => $order->billing_city,
			'state'         => $order->billing_state,
			'country'       => $order->billing_country,
			'postcode'      => $order->billing_postcode
		);

		$trustpay_args_array = array();
		foreach ( $this->data_to_send as $key => $value ) {
			$trustpay_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}

		return
			'<form action="' . esc_url( $this->api_endpoint ) . '" method="post" id="trustpay_payment_form">' .
				implode( '', $trustpay_args_array ) .
				'<input type="submit" class="button-alt button-trustpay" id="submit_trustpay_payment_form" value="' . esc_attr( __( 'Pay via TrustPay', WC_TRUSTPAY_TRANS ) ) . '" /> '.
				'<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order', WC_TRUSTPAY_TRANS ) . '</a>' .
			'</form>';
	}

	/**
	 * Check if the TrustPay TPN is valid
	 *
	 * @param array $data
	 */
	function check_tpn_request_is_valid( $data ){
		global $woocommerce;

		// Transation ID is missing
		if ( empty( $data['tp_transaction_id'] ) ) {
			$this->log->add( 'trustpay', 'TPN Request is empty.' );
			return false;
		}

		// JWT parameter is missing
		if ( ! isset( $data['jwt'] ) || empty( $data['jwt'] ) ) {
			$this->log->add( 'trustpay', 'JWT parameter is missing.' );
			return false;
		}

		// If the payment method specifies full IPN logging, do it now.
		if ( empty( $this->settings['sharedsecret'] ) ) {
			$this->log->add( 'trustpay', 'Notification URL and Secret Key Configuration not found' . print_r( $data, true ) );
			return false;
		} else {
			$shared_secret = htmlspecialchars_decode( trim ( $this->settings['sharedsecret'] ) );
		}

		// JWT signature check
		$lib_jwt = dirname( WC_TRUSTPAY_MAIN_FILE ) . '/libs/php-jwt/src/JWT.php';
		if ( file_exists( $lib_jwt ) ) {
			include_once $lib_jwt;

			$decoded = Firebase\JWT\JWT::decode( $data['jwt'], $shared_secret, array('HS256'));
			if ( isset( $decoded->jti ) && isset( $decoded->sub ) && isset( $decoded->amount ) &&
				isset( $data['transaction_id'] ) && $decoded->jti == $data['transaction_id'] &&
				isset( $data['application_id'] ) && $decoded->sub == $data['application_id'] &&
				isset( $data['amount'] ) && $decoded->amount == $data['amount'] ) {

				return true;
			}
		}

		return false;
	}

	/**
	 * Check TrustPay TPN response.
	 *
	 */
	function check_tpn_response() {
		@ob_clean();

		$tpn_response = ! empty( $_GET ) ? $_GET : false;
		if ( $tpn_response && $this->check_tpn_request_is_valid( $tpn_response ) ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( "valid_trustpay_tpn_request", $tpn_response );
		} else {
			wp_die( "TrustPay TPN Request Failure", "TrustPay TPN", array( 'response' => 200 ) );
		}
	}

	/**
	 * Successful Payment!
	 *
	 * @access public
	 * @param array $posted
	 * @return void
	 */
	function successful_request( $posted ) {
		$posted = stripslashes_deep( $posted );

		if ( ! empty( $posted['transaction_id'] ) ) {
			list( $order_id, $tmp ) = explode( '-', $posted['transaction_id'] );
			$order = $this->get_trustpay_order( $order_id );

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'trustpay', 'Found order #' . $order->id );
			}

			// Lowercase returned variables
			$posted['status'] = strtolower( $posted['status'] );

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'trustpay', 'Payment status: ' . $posted['status'] );
			}

			switch ( $posted['status'] ) {
				case 'success':
					// Check order not already completed
					if ( $order->status == 'completed' ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'trustpay', 'Aborting, Order #' . $order->id . ' is already complete.' );
						}
						exit;
					}

					// Validate currency
					if ( isset( $posted['requestcurrency'] ) ) {
						if ( $order->get_order_currency() != $posted['requestcurrency'] ) {
							if ( 'yes' == $this->debug ) {
								$this->log->add( 'trustpay', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['requestcurrency'] . '") #2' );
							}

							// Put this order on-hold for manual checking
							$order->update_status( 'on-hold', sprintf( __( 'Validation error: TrustPay currencies do not match (code %s).', WC_TRUSTPAY_TRANS ), $posted['currency'] ) );
							exit;
						}
					} else {
						if ( $order->get_order_currency() != $posted['currency'] ) {
							if ( 'yes' == $this->debug ) {
								$this->log->add( 'trustpay', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['currency'] . '")' );
							}

							// Put this order on-hold for manual checking
							$order->update_status( 'on-hold', sprintf( __( 'Validation error: TrustPay currencies do not match (code %s).', WC_TRUSTPAY_TRANS ), $posted['currency'] ) );
							exit;
						}
					}

					// Validate amount
					if ( isset( $posted['requestamount'] ) ) {
						if ( $order->get_total() != $posted['requestamount'] ) {
							if ( 'yes' == $this->debug ) {
								$this->log->add( 'trustpay', 'Payment error: Amounts do not match (gross ' . $posted['requestamount'] . ') #2' );
							}

							// Put this order on-hold for manual checking
							$order->update_status( 'on-hold', sprintf( __( 'Validation error: TrustPay amounts do not match (gross %s).', WC_TRUSTPAY_TRANS ), $posted['amount'] ) );
							exit;
						}
					} else {
						if ( $order->get_total() != $posted['amount'] ) {
							if ( 'yes' == $this->debug ) {
								$this->log->add( 'trustpay', 'Payment error: Amounts do not match (gross ' . $posted['amount'] . ')' );
							}

							// Put this order on-hold for manual checking
							$order->update_status( 'on-hold', sprintf( __( 'Validation error: TrustPay amounts do not match (gross %s).', WC_TRUSTPAY_TRANS ), $posted['amount'] ) );
							exit;
						}
					}

					if ( $posted['status'] == 'success' ) {
						// Update order status
						$order->add_order_note( __( 'TPN payment completed', WC_TRUSTPAY_TRANS ) );
						$order->payment_complete();
					}

					if ( 'yes' == $this->debug ) {
						$this->log->add( 'trustpay', 'Payment complete.' );
					}
					break;

				case 'denied' :
				case 'expired' :
				case 'failed' :
				case 'voided' :
					// Order failed
					$order->update_status( 'failed', sprintf( __( 'Payment %s via TrustPay TPN.', WC_TRUSTPAY_TRANS ), strtolower( $posted['status'] ) ) );
					break;

				default :
					// No action
					break;
			}

			exit;
		}
	}

	/**
	 * Get the trustpay order processed by transaction_id
	 *
	 * @param type $transaction_id
	 * @return \WC_Order
	 */
	function get_trustpay_order( $transaction_id) {
		$order = new WC_Order( $transaction_id );
		return $order;
	}

}
