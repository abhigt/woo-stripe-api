<?php

if (! defined('ABSPATH')) {
    die;
}

class WooCommerceStripeAPI {


    private $secret;

    public function __construct($pluginName, $version)
    {
        /*
         * Add REST Endpoint
         */
        add_action('rest_api_init', array( $this, 'registerApiEndpoints' ) );

        /*
         * Get stripe  test or secret key from woocommerce settings
         */
        $options = get_option( 'woocommerce_stripe_settings' );
        $this->secret = 'yes' === $options['testmode'] ? $options['test_secret_key'] : $options['secret_key'] ;

        \Stripe\Stripe::setApiKey($this->secret);

    }

    public function registerApiEndpoints()
    {
        register_rest_route( 'wc-stripe-api/v1', '/order-and-charge', array(
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'place_order' ),
                'permission_callback' => array( $this, 'hasPermission' ),
                'description'		  => 'Place Order with product items',
                'args'                => array(
					'products' => array(
						'type' => 'array',
						'required' => true,
						'description' => 'Enter package product ids',
                    ),
					'shipping_address_1' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter address_1',
                    ),
					'shipping_address_2' => array(
						'type' => 'string',
						'required' => false,
						'description' => 'Enter address_2',
                    ),
					'shipping_city' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter city',
                    ),
					'shipping_state' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter state',
                    ),
					'shipping_country' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter country',
                    ),
					'shipping_postcode' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter postcode',
                    ),
                    'billing_address_1' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter address_1',
                    ),
					'billing_address_2' => array(
						'type' => 'string',
						'required' => false,
						'description' => 'Enter address_2',
                    ),
					'billing_city' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter city',
                    ),
					'billing_state' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter state',
                    ),
					'billing_country' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter country',
                    ),
					'billing_postcode' => array(
						'type' => 'string',
						'required' => true,
						'description' => 'Enter postcode',
                    ),
				),
            ),
        ) );
    }

    public function hasPermission(\WP_REST_Request $request)
    {
		$user = wp_get_current_user();
        if (
            is_wp_error( $user )
            || !$user
            || $user->ID == 0
        ) {
	        return false;
        }

        return true;
    }

    public function place_order(\WP_REST_Request $request )
    {
        $products  = $request['products'];
        if( 
            is_array($products) // More validations can be added
        ) {
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid product items',
                ],
                400
            );
        }
        $shipping_address_1  = $request['shipping_address_1'];
        $shipping_address_2  = $request['shipping_address_2'];
        $shipping_city  = $request['shipping_city'];
        $shipping_state  = $request['shipping_state'];
        $shipping_country  = $request['shipping_country'];
        $shipping_postcode  = $request['shipping_postcode'];
        $billing_address_1  = $request['billing_address_1'];
        $billing_address_2  = $request['billing_address_2'];
        $billing_city  = $request['billing_city'];
        $billing_state  = $request['billing_state'];
        $billing_country  = $request['billing_country'];
        $billing_postcode  = $request['billing_postcode'];
        
        $current_user = wp_get_current_user();
        $currency = apply_filters( 'woocommerce_currency', get_option( 'woocommerce_currency' ) );
        
        // Create order
        $order = wc_create_order();
        $order->set_customer_id( $current_user->ID );

        // Add products
        foreach($products as $key => $item )
        {
            $order->add_product( wc_get_product( $item['id'] ) , $item['qty'] );
        }

        $shipping_address = array(
            'first_name' => get_user_meta($current_user->ID, 'first_name', true),
            'last_name'  => get_user_meta($current_user->ID, 'last_name', true),
            'email'      => $current_user->user_email,
            'address_1'  => $shipping_address_1,
            'address_2'  => $shipping_address_2, 
            'city'       => $shipping_city,
            'state'      => $shipping_state,
            'postcode'   => $shipping_postcode,
            'country'    => $shipping_country
        );
        $order->set_address( $shipping_address, 'shipping' );
        
        $billing_address = array(
            'first_name' => get_user_meta($current_user->ID, 'first_name', true),
            'last_name'  => get_user_meta($current_user->ID, 'last_name', true),
            'email'      => $current_user->user_email,
            'address_1'  => $billing_address_1,
            'address_2'  => $billing_address_2, 
            'city'       => $billing_city,
            'state'      => $billing_state,
            'postcode'   => $billing_postcode,
            'country'    => $billing_country
        );
        $order->set_address( $billing_address, 'billing' );

        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $order->set_payment_method($payment_gateways['stripe']);

        $order->calculate_totals(true);

        // Get total for the order as a number
        $total = $order->get_total();
        $amount = $total * 100;
        
        try {
            
            $stripe_cus_key = get_user_meta($current_user->ID, 'stripe_cus_key', true);
            if(
                !$stripe_cus_key
            ) {
                $customer = \Stripe\Customer::create([
                    'description' => 'WP User ID: '. $current_user->ID,
                    'email' => $current_user->user_email,
                ]);
                update_user_meta( $current_user->ID, 'stripe_cus_key', $customer->id );
                $stripe_cus_key = $customer->id;
            }
            $args = array(
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'customer' => $stripe_cus_key, // Specify customer id
                'metadata' => [
                    'integration_check' => 'accept_a_payment',
                    'order_id' => $order->get_id(),
                    ],
            );

            $intent = \Stripe\PaymentIntent::create($args);
            
            $order->add_meta_data('_stripe_intent_id', $intent->id );
            
            $order->save();

            return new \WP_REST_Response(
                [
                    'success' => true,
                    'message'	  => 'Order created successfully.',
                    'data' => [
                        'client_secret' => $intent->client_secret,
                        'order_id' => $order->get_id(),
                        ]
                ],
                200
            );
        } catch (\Stripe\Error\Base $e) {
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'message'	  => 'Error',
                    'data' => ['error' => $e->getMessage()]
                ],
                400
            );
        } catch (\Error $e) {
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'message'	  => 'Error',
                    'data' => ['error' => $e->getMessage()]
                ],
                400
            );
        }

    }

}
