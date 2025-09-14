<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_Product_Recuring extends WC_Product_Simple {
    public static function add_custom_product_type_options() {
		global $post;
        $recurring = get_post_meta($post->ID, '_recurring', true);
        $checked = $recurring === 'yes' ? 'checked' : '';
		
		
		$subscription_interval = get_post_meta($post->ID, '_subscription_interval', true);
        $subscription_period = get_post_meta($post->ID, '_subscription_period', true);
        $stripe_product_id = get_post_meta($post->ID, '_stripe_product_id', true);
		$stripe_price_id = get_post_meta($post->ID, '_stripe_price_id', true);
		
        echo '<div class="options_group" style="width: 40%;margin-left: 22%;">';
        woocommerce_wp_checkbox(array(
            'id'          => '_recurring',
            'label'       => __('Recurring Product', 'woocommerce'),
            'desc_tip'    => true,
            'description' => __('Check this box if the product is recurring.', 'woocommerce')
        )); 
		
		
		// Subscription Interval
        woocommerce_wp_text_input(array(
            'id'          => '_subscription_interval',
            'label'       => __('Subscription Interval', 'woocommerce'),
            'desc_tip'    => true,
            'description' => __('Enter the subscription interval (e.g., 1).', 'woocommerce'),
            'type'        => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '1'
            ),
            'value'       => $subscription_interval
        ));
          
        // Subscription Period
        woocommerce_wp_select(array(
            'id'          => '_subscription_period',
            'label'       => __('Subscription Period', 'woocommerce'),
            'desc_tip'    => true,
            'description' => __('Select the subscription period (e.g., day, week, month, year).', 'woocommerce'),
            'options'     => array(
                ''      => __('Select an option', 'woocommerce'),
                'day'   => __('Daily', 'woocommerce'),
                'week'  => __('Weekly', 'woocommerce'),
                'month' => __('Monthly', 'woocommerce'),
                'year'  => __('Yearly', 'woocommerce')
            ),
            'value'       => $subscription_period
        ));

        // Stripe Product ID
        woocommerce_wp_text_input(array(
            'id'          => '_stripe_product_id',
            'label'       => __('Stripe Product ID', 'woocommerce'),
            'desc_tip'    => true,
            'description' => __('Enter the Stripe product ID.', 'woocommerce'),
            'type'        => 'text',
            'value'       => $stripe_product_id
        ));
		
		// Stripe Price ID
        woocommerce_wp_text_input(array(
            'id'          => '_stripe_price_id',
            'label'       => __('Stripe Price ID', 'woocommerce'),
            'desc_tip'    => true,
            'description' => __('Enter the Stripe price ID for the subscription.', 'woocommerce'),
            'type'        => 'text',
            'value'       => $stripe_price_id
        ));
		
		echo '<button type="button" id="create_stripe_product" class="button-primary">Create Stripe Product</button>';
		
        echo '</div>';
		?>
		
		<script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#create_stripe_product').on('click', function() {
                var data = {
                    action: 'create_stripe_product',
                    post_id: <?php echo $post->ID; ?>
                };

                $.post(ajaxurl, data, function(response) {
					//console.log(response);
                    if (response.success) {
                        alert('Stripe product created successfully!');
                        $('#_stripe_product_id').val(response.data.product_id);
                    } else {
                        alert('Error creating Stripe product: ' + response.data.message);
                    } 
					
                });
            });
        });
        </script>
        <?php
    }

    public static function save_custom_product_type_options($post_id) {
        
		if (isset($_POST['_recurring'])) {
            $recurring = 'yes' === $_POST['_recurring'] ? 'yes' : 'no';
            update_post_meta($post_id, '_recurring', $recurring);
        } else {
            update_post_meta($post_id, '_recurring', 'no');
        }
		
		 // Save subscription interval
        if (isset($_POST['_subscription_interval'])) {
            $subscription_interval = sanitize_text_field($_POST['_subscription_interval']);
            update_post_meta($post_id, '_subscription_interval', $subscription_interval);
        }

        // Save subscription period
        if (isset($_POST['_subscription_period'])) {
            $subscription_period = sanitize_text_field($_POST['_subscription_period']);
            update_post_meta($post_id, '_subscription_period', $subscription_period);
        }

        // Save Stripe product ID
        if (isset($_POST['_stripe_product_id'])) {
            $stripe_product_id = sanitize_text_field($_POST['_stripe_product_id']);
            update_post_meta($post_id, '_stripe_product_id', $stripe_product_id);
        }
		
		// Save Stripe price ID
        if (isset($_POST['_stripe_price_id'])) {
            $stripe_price_id = sanitize_text_field($_POST['_stripe_price_id']);
            update_post_meta($post_id, '_stripe_price_id', $stripe_price_id);
        }
		
    }
	
	public static function create_stripe_product() {
		// Check for proper permissions
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => 'You do not have sufficient permissions to access this feature.'));
		}
		
		// Retrieve the saved Stripe product ID and price ID
		
		
		// Include Stripe PHP Library
		//require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
		global $wpdb;
        $table_name = $wpdb->prefix . "custom_stripe_data1";
        $api_key = $wpdb->get_var("SELECT api_key FROM $table_name ORDER BY id DESC LIMIT 1");
        $connected_account_id = esc_attr(get_option("kb_conncted_account_id_stripe"));

        \Stripe\Stripe::setApiKey($api_key);
		
		// Retrieve the saved Stripe product ID and price ID
		$existing_product_id = get_post_meta($_POST['post_id'], '_stripe_product_id', true);
		
		$existing_price_id = get_post_meta($_POST['post_id'], '_stripe_price_id', true);

		// Check if the Stripe product already exists
		if ($existing_product_id && $existing_price_id) {
			wp_send_json_error(array('message' => 'Stripe product and price already exist.'));
		}
		
		
		// Create a Stripe product
		try {
			 // Retrieve the WooCommerce product price
			 
			$product = wc_get_product($_POST['post_id']);
			$product_price = $product->get_regular_price(); 
			
			$stripe_product  = \Stripe\Product::create([
				'name' => 'Recuring payment for Product ' . get_the_title($_POST['post_id']),
				'type' => 'service'
			]);
			
			// Create a Stripe price for the product
			$stripe_price  = \Stripe\Price::create([
				'unit_amount' => $product_price * 100, 
				'currency'    => strtolower(get_woocommerce_currency()),
				'recurring'   => [
					'interval' => get_post_meta($_POST['post_id'], '_subscription_period', true),
					'interval_count' => get_post_meta($_POST['post_id'], '_subscription_interval', true),
				],
				'product'     => $stripe_product->id,
			]);
			
			
			
			// Save the Stripe product ID
			update_post_meta($_POST['post_id'], '_stripe_product_id', $stripe_product->id);
			
			update_post_meta($_POST['post_id'], '_stripe_price_id', $stripe_price->id);

			wp_send_json_success(array('product_id' => $stripe_product->id));
			
		} catch (Exception $e) {
			
			wp_send_json_error(array('message' => $e->getMessage()));
		}
		
		//echo "<pre>"; print_r($_POST);echo "<pre>";die;
    }
}

function my_custom_plugin_init() {
    add_action('woocommerce_product_data_panels', array('WC_Product_Recuring', 'add_custom_product_type_options'));
    add_action('woocommerce_process_product_meta', array('WC_Product_Recuring', 'save_custom_product_type_options'));
	
    add_action('woocommerce_process_product_meta', array('WC_Product_Recuring', 'save_custom_product_type_options'));
	
	add_action('wp_ajax_create_stripe_product', array('WC_Product_Recuring', 'create_stripe_product')); 
	
	add_action('wp_ajax_nopriv_create_stripe_product', array('WC_Product_Recuring', 'create_stripe_product')); 
	
   
}
add_action('init', 'my_custom_plugin_init');

