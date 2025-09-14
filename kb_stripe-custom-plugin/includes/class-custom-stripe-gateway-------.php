<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_Gateway_KB_Custom_Stripe extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'kb_custom_stripe';
        $this->method_title       = 'KB Custom Stripe';
        $this->method_description = 'Custom Stripe Payment Gateway for WooCommerce.';
        $this->has_fields         = true;

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->testmode     = 'yes' === $this->get_option('testmode');
        $this->api_key      = $this->get_option('api_key');
        $this->pub_api_key  = $this->get_option('pub_api_key');

        // Actions
        add_action('woocommerce_api_' . $this->id, array($this, 'check_response'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable KB Custom Stripe Gateway',
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'Title that the user sees during checkout.',
                'default'     => 'Credit Card (KB Custom Stripe)',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Description that the user sees during checkout.',
                'default'     => 'Pay securely using your credit card via Stripe.',
            ),
            'testmode' => array(
                'title'   => 'Test Mode',
                'type'    => 'checkbox',
                'label'   => 'Enable Test Mode',
                'default' => 'yes',
            ),
            'api_key' => array(
                'title'       => 'API Key',
                'type'        => 'text',
                'description' => 'Your Stripe API key.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'pub_api_key' => array(
                'title'       => 'Stripe Publishable Key',
                'type'        => 'text',
                'description' => 'Your Stripe publishable key.',
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    public function process_admin_options() {
        parent::process_admin_options();
		
		echo "ffddfdf";
		die;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Retrieve API key from settings
        $api_key = $this->get_option('api_key');
        \Stripe\Stripe::setApiKey($api_key);

        try {
            // Create a charge
            $charge = \Stripe\Charge::create(array(
                'amount'   => $order->get_total() * 100, // Amount in cents
                'currency' => get_woocommerce_currency(),
                'description' => 'Order #' . $order_id,
                'source'  => sanitize_text_field($_POST['stripeToken']), // Token from Stripe Elements
            ));

            // Update order status
            $order->payment_complete($charge->id);

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Empty the cart
            WC()->cart->empty_cart();

            // Return success
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );

        } catch (Exception $e) {
            // Handle error
            wc_add_notice('Payment error: ' . $e->getMessage(), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
    }

    public function receipt_page($order) {
        echo '<p>Thank you for your order. Please click the button below to pay with Stripe.</p>';
        ?>
        <form action="<?php echo esc_url( $this->get_return_url( $order ) ); ?>" method="post" id="payment-form">
            <script src="https://js.stripe.com/v3/"></script>
            <div id="card-element"></div>
            <button type="submit">Pay</button>
        </form>
        <script>
            var stripe = Stripe('<?php echo esc_js( $this->pub_api_key ); ?>');
            var elements = stripe.elements();
            var card = elements.create('card');
            card.mount('#card-element');
            
            var form = document.getElementById('payment-form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        // Show error in payment form
                        console.error(result.error.message);
                    } else {
                        // Send token to server
                        var hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'stripeToken');
                        hiddenInput.setAttribute('value', result.token.id);
                        form.appendChild(hiddenInput);
                        form.submit();
                    }
                });
            });
        </script>
        <?php
    }

    // Debugging method to log settings
    private function log_settings() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KB Custom Stripe Settings:' );
            error_log( 'Title: ' . $this->title);
            error_log( 'Description: ' . $this->description );
            error_log( 'Test Mode: ' . ( $this->testmode ? 'Enabled' : 'Disabled' ) );
        }
    }
}
