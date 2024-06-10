<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Stripe Payment API
 * Plugin URI:        https://github.com/abhijit-goswami
 * Description:       This plugin adds a REST API endpoint to place a woocommerce order and process Stripe payment. 
 *                    This API can be used for any frontend or mobile app.
 * Version:           0.1
 * Author:            Abhijit Goswami
 * @author            Abhijit Goswami
 */

if (! defined('ABSPATH')) {
    die;
}

define('WOOCOMMERCE_STRIPE_API_VERSION', '0.1');
define('WOOCOMMERCE_STRIPE_API_NAME', 'woocommerce-stripe-api');

require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-stripe-api.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';


/**
 * Begins execution of the plugin.
 *
 */
function WooCommerceStripeAPIInit() {
    try {
        new WooCommerceStripeAPI( WOOCOMMERCE_STRIPE_API_NAME, WOOCOMMERCE_STRIPE_API_VERSION );
    } catch (\Exception $e) {
        print_r($e->getTrace());
    }
}
WooCommerceStripeAPIInit();
