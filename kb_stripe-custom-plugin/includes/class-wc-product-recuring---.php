<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_Product_Recuring extends WC_Product_Simple {

    public function __construct( $product ) {
        $this->product_type = 'recuring';
        parent::__construct( $product );
    }

    public function get_subscription_period() {
        return $this->get_meta( '_subscription_period' );
    }

    public function set_subscription_period( $value ) {
        $this->update_meta_data( '_subscription_period', $value );
    }

    public function get_subscription_interval() {
        return $this->get_meta( '_subscription_interval' );
    }

    public function set_subscription_interval( $value ) {
        $this->update_meta_data( '_subscription_interval', $value );
    }
	
    public static function add_custom_product_type_options( $types ) {
		global $post;
        echo '<div class="options_group">';
        /* woocommerce_wp_checkbox(array(
            'id' => '_recurring',
            'label' => __('Recurring Product', 'woocommerce'),
            'description' => __('Check this box if the product is recurring.', 'woocommerce')
        )); */
		 woocommerce_wp_checkbox( array(
                'id'          => '_recurring',
                'label'       => __( 'Recurring Product', 'woocommerce' ),
                'description' => __( 'Check this box if the product is recurring.', 'woocommerce' )
            ));

        echo '</div>';
 
    }
    public static function add_product_type_selector( $types ) {
		
        //$types['recuring'] = __( 'Recuring Product', 'recuring_product' );
		/* echo "<pre>";
		print_r($types);
		die; */
        return $types;
    }

    public static function add_product_options() {
        global $post;
		
		// /* $product = wc_get_product($post->ID);
		
		// echo "<pre>";
		// print_r($product);
		// echo "</pre>";
		// die; */
		

        if ( 'recuring' === get_post_meta( $post->ID, '_product_type', true ) ) {
            echo '<div class="options_group">';

            woocommerce_wp_text_input( array(
                'id'          => '_subscription_period',
                'label'       => __( 'Subscription Period', 'recuring_product' ),
                'placeholder' => 'Enter subscription period (e.g., 1 month)',
                'desc_tip'    => true,
                'description' => __( 'Enter the period for the subscription (e.g., 1 month, 3 months).', 'recuring_product' ),
            ) );

            woocommerce_wp_text_input( array(
                'id'          => '_subscription_interval',
                'label'       => __( 'Subscription Interval', 'recuring_product' ),
                'placeholder' => 'Enter subscription interval (e.g., 1)',
                'desc_tip'    => true,
                'description' => __( 'Enter the interval for the subscription (e.g., 1 for monthly ).', 'recuring_product' ),
            ) );

            echo '</div>';
        }
    }

    public static function save_product_options( $post_id ) {
		$producttype = isset( $_POST['product-type'] ) ? sanitize_text_field( $_POST['product-type'] ) : '';
		if($producttype=='recuring'){
			update_post_meta($post_id,'_product_type', $producttype);
		}else{
		} 
        $product_type = get_post_meta( $post_id, '_product_type', true );
        if ( 'recuring' === $product_type ) {
            $subscription_period = isset( $_POST['_subscription_period'] ) ? sanitize_text_field( $_POST['_subscription_period'] ) : '';
            $subscription_interval = isset( $_POST['_subscription_interval'] ) ? sanitize_text_field( $_POST['_subscription_interval'] ) : '';

            update_post_meta( $post_id, '_subscription_period', $subscription_period );
            update_post_meta( $post_id, '_subscription_interval', $subscription_interval );
        } 
    }
}

// Register the custom product type and options
 function my_custom_plugin_init() {
	//add_action('woocommerce_product_data_panels', 'add_custom_product_type_options');
	add_action( 'woocommerce_product_data_panels', array( 'WC_Product_Recuring', 'add_custom_product_type_options' ) );
    //if ( class_exists( 'WC_Product_Simple' ) ) {
       // add_filter( 'product_type_selector', array( 'WC_Product_Recuring', 'add_product_type_selector' ) );
        //add_action( 'woocommerce_product_options_pricing', array( 'WC_Product_Recuring', 'add_product_options' ) );
        //add_action( 'woocommerce_process_product_meta', array( 'WC_Product_Recuring', 'save_product_options' ) );
		
		
    //}
}
add_action( 'init', 'my_custom_plugin_init' ); 

// Display custom fields on the product data panel
/* function add_custom_product_type_options() {
    global $post;
        echo '<div class="options_group">';

        // Recurring checkbox
        woocommerce_wp_checkbox(array(
            'id' => '_recurring',
            'label' => __('Recurring Product', 'woocommerce'),
            'description' => __('Check this box if the product is recurring.', 'woocommerce')
        ));

        echo '</div>';
    //}
} */
//add_action('woocommerce_product_data_panels', 'add_custom_product_type_options');
