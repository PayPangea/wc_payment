<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://webgarh.com
 * @since             1.0.0
 * @package           Woocommerce_Paypangea_Payment_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce PayPangea Payment Gateway
 * Plugin URI:        https://webgarh.com
 * Description:       WooCommerce PayPangea Payment Gateway
 * Version:           1.0.0
 * Author:            Webgarh Plugin Team
 * Author URI:        https://webgarh.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-paypangea-payment-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_PAYPANGEA_PAYMENT_GATEWAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-paypangea-payment-gateway-activator.php
 */
function activate_woocommerce_paypangea_payment_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-paypangea-payment-gateway-activator.php';
	Woocommerce_Paypangea_Payment_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-paypangea-payment-gateway-deactivator.php
 */
function deactivate_woocommerce_paypangea_payment_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-paypangea-payment-gateway-deactivator.php';
	Woocommerce_Paypangea_Payment_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_paypangea_payment_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_paypangea_payment_gateway' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-paypangea-payment-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_paypangea_payment_gateway() {

	$plugin = new Woocommerce_Paypangea_Payment_Gateway();
	$plugin->run();

}
run_woocommerce_paypangea_payment_gateway();

add_action('wp_head','add_paypangea_script');
function add_paypangea_script()
{
	?>
	<script src="https://sdk.paypangea.com/sdk.js?ver=4"></script>
	<script>
		let payPangeaWidget;
	</script>
	<?php
}

//add_action('wp_footer','add_paypangea_script_footer');
function add_paypangea_script_footer()
{
	?>
		<script>
			let payPangeaWidget;
			var tkn = '';
			var wallet = '';
			document.addEventListener('DOMContentLoaded', function () {
				console.log('i am here shashi');
			    payPangeaWidget = new PayPangea({
			        apiKey: '2321349-vnregy88-7yf78dsgf-anytech',
			        environment: 'PRODUCTION',
			    });
			});

			var loginBtn = document.getElementById('loginbtn');

			console.log(loginBtn);

			loginBtn.addEventListener('click', function() {
				console.log('i am here shashi is clicked');
				console.log('token is '+tkn+' and wallet id is '+wallet);
			    payPangeaWidget.showLogin({});

			    const events = ['success', 'error', 'cancel', 'update'];

				events.forEach((event) => {
				    payPangeaWidget.on(event, (data) => {
				        console.log(`Event '${event}' received from PayPangea`, data.outcome);
				        var outcome =  data.outcome;
				        tkn = outcome.token;
				        wallet = outcome.wallet;

				    console.log('token is '+tkn+' and wallet id is '+wallet);

				    });
				});

			});
		</script>
	<?php
}
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'paypangea_add_gateway_class' );
function paypangea_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_PayPangea_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'paypangea_init_gateway_class' );
function paypangea_init_gateway_class() {

	class WC_PayPangea_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {

		$this->id = 'paypangea'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = true; // in case you need a custom credit card form
		$this->method_title = 'PayPangea Gateway';
		$this->method_description = 'Description of paypangea payment gateway'; // will be displayed on the options page

		// gateways can support subscriptions, refunds, saved payment methods,
		// but in this tutorial we begin with simple payments
		$this->supports = array(
			'products'
		);

		// Method with all the options fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled = $this->get_option( 'enabled' );
		$this->stagingmode = 'yes' === $this->get_option( 'stagingmode' );
		$this->api_key = $this->stagingmode ? $this->get_option( 'test_api_key' ) : $this->get_option( 'api_key' );

		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		
		// You can also register a webhook here
		// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

 		}

		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){

			$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable paypangea Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'PayPangea',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Pay with your paypangea via our super-cool payment gateway.',
			),
			'testmode' => array(
				'title'       => 'Staging mode',
				'label'       => 'Enable Staging Mode',
				'type'        => 'checkbox',
				'description' => 'Place the payment gateway in staging mode using test API keys.',
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'test_api_key' => array(
				'title'       => 'Staging Api Key',
				'type'        => 'password',
			),
			'api_key' => array(
				'title'       => 'Production Api Key',
				'type'        => 'password'
			)
		);
	
	 	}

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {

			?>
			<style>
				#place_order{display:none;}
			</style>
			<a href="javascript:void(0);" id="loginbtn" class="button alt buybtn">Login with PayPangea</a>
			<a style="display:none;" href="javascript:void(0);" id="buybtn" class="button alt buybtn">Buy with PayPangea</a>
			<script>
				
				var tkn = '';
				var wallet = '';
				document.addEventListener('DOMContentLoaded', function () {
					console.log('i am here shashi');
				    payPangeaWidget = new PayPangea({
				        apiKey: '2321349-vnregy88-7yf78dsgf-anytech',
				        environment: 'PRODUCTION',
				    });
				});

				var loginBtn = document.getElementById('loginbtn');

				console.log(loginBtn);

				loginBtn.addEventListener('click', function() {
					console.log('i am here shashi is clicked');
					console.log('token is '+tkn+' and wallet id is '+wallet);
				    payPangeaWidget.showLogin({});

				    const events = ['success', 'error', 'cancel', 'update'];

					events.forEach((event) => {
					    payPangeaWidget.on(event, (data) => {
					        console.log(`Event '${event}' received from PayPangea`, data.outcome);
					        var outcome =  data.outcome;
					        tkn = outcome.token;
					        wallet = outcome.wallet;
					        jQuery('#buybtn').css('display','block');
					        jQuery('#loginbtn').css('display','none');
					    console.log('token is '+tkn+' and wallet id is '+wallet);

					    });
					});

				});

				var buyBtn = document.getElementById('buybtn');

				buyBtn.addEventListener('click', function() {
					console.log('token is '+tkn+' and wallet id is '+wallet);
				    var payy = payPangeaWidget.initPayment({
				        amount: '100', // The amount to be paid
				        token: tkn, // The name of the token to be used for payment
				        currency: 'INR', // The fiat currency equivalent if applicable
				        tokenaddress: wallet, // The blockchain address for the token
				        chain: 'BSC', // The blockchain network to use (e.g., Ethereum, BSC)
				        title: 'Test Transaction', // A title for the transaction
				        text: 'Test Transaction Description', // A description or additional text for the transaction
				        successredirectURL: 'https://custompaymentplugin.webgarh.net/', // URL to redirect to on successful payment
				        failredirectURL: 'https://custompaymentplugin.webgarh.net/paypangea-payments/', // URL to redirect to on failed payment
				        webhookURL: '', // Your server endpoint to receive callbacks
				        merchantid: '10000054654023456789' // Your PayPangea merchant ID
				    });

				    console.log(payy);
				});
			</script>
			<?php

		
				 
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts()
	 	{

		   // we need JavaScript to process a token only on cart/checkout pages, right?
			if( ! is_cart() && ! is_checkout() && ! isset( $_GET[ 'pay_for_order' ] ) ) {
				return;
			}

			// if our payment gateway is disabled, we do not have to enqueue JS too
			if( 'no' === $this->enabled ) {
				return;
			}

			// no reason to enqueue JavaScript if API keys are not set
			if( empty( $this->api_key ) || empty( $this->api_key ) ) {
				return;
			}

			// do not work with card detailes without SSL unless your website is in a test mode
			if( ! $this->stagingmode && ! is_ssl() ) {
				return;
			}

			/*// let's suppose it is our payment processor JavaScript that allows to obtain a token
			wp_enqueue_script( 'paypangea_js', 'some payment processor site/api/token.js' );

			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'woocommerce_paypangea', plugins_url( 'paypangea.js', __FILE__ ), array( 'jquery', 'paypangea_js' ) );

			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'woocommerce_paypangea', 'paypangea_params', array(
				'publishableKey' => $this->api_key
			) );

			wp_enqueue_script( 'woocommerce_paypangea' );*/
			
	 	}

		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() 
		{

		

		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) 
		{

		   		$order = wc_get_order( $order_id );
 				return;
 
				/*
			 	 * Array with parameters for API interaction
				 */
				/*	$args = array(
			 
				 
			 
				);
			 
				
				 $response = wp_remote_post( '{payment processor endpoint}', $args );
			 

				 if( 200 === wp_remote_retrieve_response_code( $response ) ) {
			 
					 $body = json_decode( wp_remote_retrieve_body( $response ), true );
			 
					 // it could be different depending on your payment processor
					 if( 'APPROVED' === $body[ 'response' ][ 'responseCode' ] ) {
			 
						// we received the payment
						$order->payment_complete();
						$order->reduce_order_stock();
			 
						// some notes to customer (replace true with false to make it private)
						$order->add_order_note( 'Hey, your order is paid! Thank you!', true );
			 
						// Empty cart
						WC()->cart->empty_cart();
			 
						// Redirect to the thank you page
						return array(
							'result' => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
			 
					 } else {
						wc_add_notice( 'Please try again.', 'error' );
						return;
					}
			 
				} else {
					wc_add_notice( 'Connection error.', 'error' );
					return;
				}*/
					
	 	}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() 
		{

			/*$order = wc_get_order( $_GET[ 'id' ] );
			$order->payment_complete();
			$order->reduce_order_stock();

			update_option( 'webhook_debug', $_GET );*/
					
	 	}
 	}
}