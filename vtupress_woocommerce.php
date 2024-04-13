<?php

/**
*Plugin Name: VTUpress 4 Woocommerce
*Plugin URI: http://vtupress.com
*Description: Allow users to use their vtupress balance to purchase woocommerce products
*Version: 1.0.5
*Author: Akor Victor
*Author URI: https://facebook.com/akor.victor.39
Requires PHP: 7.4
License:      GPL3
License URI:  https://www.gnu.org/licenses/gpl.html
Domain Path:  /languages
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Gateway_Offline
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      SkyVerge
 */


$path = WP_PLUGIN_DIR.'/vtupress/functions.php';

require __DIR__.'/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/bikendi-tech-solutions/vtupress_woocommerce',
	__FILE__,
	'vtupress_woocommerce'
);
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

$myUpdateChecker->setAuthentication('your-token-here');

$myUpdateChecker->getVcsApi()->enableReleaseAssets();


if(file_exists($path) && in_array('vtupress/vtupress.php', apply_filters('active_plugins', get_option('active_plugins')))){


if(is_plugin_active("woocommerce/woocommerce.php")){

add_action( 'plugins_loaded', 'wc_vtupress_gateway_init', 11 );
function wc_vtupress_gateway_init() {

    class WC_Gateway_Vtuwallet extends WC_Payment_Gateway {
 public $domain;
        // The meat and potatoes of our gateway will go here
		/*
		*
		*		 'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
				***/
               
		
		        public function __construct() {

            $this->domain = 'Vtu Wallet';

            $this->id                 = 'vtuwallet';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'Vtupress Wallet', $this->domain );
            $this->method_description = __( 'Allows your users to purchase products using their vtu wallet.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
			$this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			}
			
			
			
		public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Vtupress Wallet System As Payment Method', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'Vtupress Wallet', $this->domain ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information For Customers on Checkout', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => 'Payment Information For Customers After Checkout',
                    'desc_tip'    => true,
                ),
            );
        }
		
		
		
		 public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

	
        public function payment_fields(){
			
include_once(ABSPATH .'wp-content/plugins/vtupress/functions.php');

			 global $woocommerce, $current_balance,  $order_price_cents;  
    $order_price_cents = $woocommerce->cart->total;

$id = get_current_user_id();
			if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }
$current_balance = vp_getuser($id, "vp_bal",true);

if($current_balance >= $order_price_cents){
            ?>
            <div id="custom_input">
                <p class="form-row form-row-wide">
                    <label for="pin" class=""><?php _e('Your Transaction PIN', $this->domain); ?></label>
                    <input type="number" class="" name="pin" id="pin" placeholder="" value="">
                </p>
            </div>
            <?php
}
else{
?>
<label class="font-bold"><?php _e("Your Balance Is Too Low. Balance: ₦$current_balance", $this->domain); ?></label>
       <?php         
}
        }
		
		
		
public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );
			$order_data = $order->get_data();
			//$status = $order_data['status'];
			$user_id = $order->get_user_id();
			//$item = new WC_Order_Item_Product($order_id);
			//$product_name = $item->get_name();

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

			global $wpdb;

				
		$id = get_current_user_id();
		$name = get_userdata($id)->user_login;
		$current_balance = vp_getuser($id, "vp_bal",true);

	
			$order_total = $order->get_total(); 
			$tot = $current_balance - $order_total;
			vp_updateuser($id, 'vp_bal', $tot);
			
		if (  $status  == 'completed' ) {
    // Do something
			//$order_data = $order->get_data(); 
		

			
			
			
			//$order_total_tax = $order_data['total_tax'];
$table_name = $wpdb->prefix.'vp_wallet';
$wpdb->insert($table_name, array(
'name'=> $name,
'type'=> "Woocommerce",
'description'=> "Payment for a product Worth $order_total",
'fund_amount' => $order_total,
'before_amount' => $current_balance,
'now_amount' => $tot,
'user_id' => $id,
'status' => "Approved",
'the_time' => current_time('mysql', 1)
));
		}
		else{
$table_name = $wpdb->prefix.'vp_wallet';
$wpdb->insert($table_name, array(
'name'=> $name,
'type'=> "Woocommerce",
'description'=> "Payment For A product Worth $order_total",
'fund_amount' => $order_total,
'before_amount' => $current_balance,
'now_amount' => $tot,
'user_id' => $id,
'status' => "Processing",
'the_time' => current_time('mysql', 1)
));
		}

            // Set order status
            $order->update_status( $status, __( 'Checkout with Vtu Wallet. ', $this->domain ) );

            // or call the Payment complete
            // $order->payment_complete();

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }

		
		

    } // end \WC_Gateway_Offline class


}




add_filter( 'woocommerce_payment_gateways', 'vtupress_custom_gateway_class' );
function vtupress_custom_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Vtuwallet'; 
    return $methods;
}

add_action('woocommerce_checkout_process', 'vtupress_process_custom_payment');
function vtupress_process_custom_payment(){

$id = get_current_user_id();
    if($_POST['payment_method'] != 'vtuwallet')
        return;

    if( !isset($_POST['pin']) || empty($_POST['pin']) )
        wc_add_notice( 'Please ENter Your Transaction PIN' , 'error' );
	
$pin = sanitize_text_field($_POST["pin"]);
$mypin = sanitize_text_field(vp_getuser($id,"vp_pin",true));

if($pin != $mypin)
	wc_add_notice( 'Incorrect PIN' , 'error' );



}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'vtupress_payment_update_order_meta' );
function vtupress_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'vtuwallet')
        return;



}

}

}
else{

add_action("admin_notices","vtuwoo_actnote");

function vtuwoo_actnote(){
/*	
$path = get_plugin_data(__FILE__);
$version = $path["Version"];
*/
	
	
echo'
<style>
.vp-not{
padding:10px;	
	
}

</style>
<div class="notice vp-not notice-danger is-dismissible">
Please Install && Activate Vtupress Plugin To Use Vtupress For Woocommerce
</div>
';
}
	
	
}
?>