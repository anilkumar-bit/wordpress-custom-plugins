<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

   class WC_Custom_Stripe_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = "custom_stripe";
            $this->icon = "";
            $this->has_fields = true;
            $this->method_title = __("Custom Stripe", "woocommerce");
            $this->method_description = __(
                "Custom Stripe Payment Gateway",
                "woocommerce"
            );

            // Define settings
            $this->init_form_fields();
            $this->init_settings();

            // Get user settings
            $this->enabled = $this->get_option("enabled");
            $this->title = $this->get_option("title");
            $this->description = $this->get_option("description");
            $this->api_key = $this->get_option("api_key");
            $this->pub_key = $this->get_option("pub_api_key");

            // Save settings
            add_action(
                "woocommerce_update_options_payment_gateways_" . $this->id,
                [$this, "process_admin_options"]
            );
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                "enabled" => [
                    "title" => __("Enable/Disable", "woocommerce"),
                    "type" => "checkbox",
                    "label" => __(
                        "Enable Custom Stripe Payment Gateway",
                        "woocommerce"
                    ),
                    "default" => "no",
                ],
                "title" => [
                    "title" => __("Title", "woocommerce"),
                    "type" => "text",
                    "description" => __(
                        "This controls the title which the user sees during checkout.",
                        "woocommerce"
                    ),
                    "default" => __("Custom Stripe", "woocommerce"),
                    "desc_tip" => true,
                ],
                "description" => [
                    "title" => __("Description", "woocommerce"),
                    "type" => "textarea",
                    "description" => __(
                        "This controls the description which the user sees during checkout.",
                        "woocommerce"
                    ),
                    "default" => __(
                        "Pay with your credit card via Custom Stripe.",
                        "woocommerce"
                    ),
                ],
                "api_key" => [
                    "title" => __("Stripe API Key", "woocommerce"),
                    "type" => "text",
                    "description" => __("Your Stripe API key.", "woocommerce"),
                    "default" => "",
                ],
                  "pub_api_key" => [
                    "title" => __("Stripe Publie Key", "woocommerce"),
                    "type" => "text",
                    "description" => __("Your Stripe Publie key.", "woocommerce"),
                    "default" => "",
                ],
            ];
        }
        public function process_admin_options()
        {
            parent::process_admin_options();

            // Save custom settings to the custom table
            $enabled = $this->get_option("enabled") === "yes" ? 1 : 0;
            $title = $this->get_option("title");
            $description = $this->get_option("description");
            $api_key = $this->get_option("api_key");
            $pub_key = $this->get_option("pub_api_key");

            global $wpdb;
            $table_name = $wpdb->prefix . "custom_stripe_data1";

            // Check if the record already exists
            $record_id = $wpdb->get_var(
                "SELECT id FROM $table_name ORDER BY id DESC LIMIT 1"
            );

            if ($record_id) {
                // Update existing record
                $wpdb->update(
                    $table_name,
                    [
                        "enabled" => $enabled,
                        "title" => $title,
                        "description" => $description,
                        "api_key" => $api_key,
                        "pub_api_key" => $pub_key,
                    ],
                    ["id" => $record_id]
                );
            } else {
                // Insert new record
                $wpdb->insert($table_name, [
                    "enabled" => $enabled,
                    "title" => $title,
                    "description" => $description,
                    "api_key" => $api_key,
                    "pub_api_key" => $pub_key,
                ]);
            }
        }

        public function payment_fields()
        {
            ?>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>
            <?php
        }
		
		public function process_payment_old($order_id)
		{
			$order = wc_get_order($order_id);

			if (isset($_POST["stripeToken"]) && !empty($_POST["stripeToken"])) {
				$token = sanitize_text_field($_POST["stripeToken"]);
			} else {
				echo "<script>setTimeout(function(){";
				echo "alert('Token is missing. Please try again.');";
				echo "}, 2000);</script>";

				return [
					"result" => "failure",
					"redirect" => "",
				];
			}

			try {
				global $wpdb;
				$table_name = $wpdb->prefix . "custom_stripe_data1";
				$api_key = $wpdb->get_var("SELECT api_key FROM $table_name ORDER BY id DESC LIMIT 1");
				\Stripe\Stripe::setApiKey($api_key);

				$connected_account_id = esc_attr(get_option("kb_conncted_account_id_stripe"));

				// Create a charge with the token
				$custom_order_table = $wpdb->prefix . "custom_order_table";
				$order_id = $order->get_id();
				$amount = round($order->get_total() * 100);
				

				$order_exists = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM $custom_order_table WHERE order_id = %d",
					$order_id
				));

				if ($order_exists) {
				  
					error_log("Order ID $order_id already exists in the custom table. Charge not created.");
					wc_add_notice("Order already processed. Please contact support for assistance.", "error");
					return [
						"result" => "failure",
						"redirect" => wc_get_checkout_url(),
					];
				} else {
					// Check if transfer data already exists
					$commission_table = $wpdb->prefix . "Commission_data";
					$transferlog_table = $wpdb->prefix . "transferlog_table";
					$transfer_exists = $wpdb->get_var($wpdb->prepare(
						"SELECT COUNT(*) FROM $commission_table WHERE order_id = %d",
						$order_id
					)); 
					
					// Example order total, replace with actual order total
					$Orderamount = round($order->get_total() * 100); // Amount in pence

					$percentageFee = 0.025; // 2.5% as a decimal
					$fixedFee = 20; // 20 pence

					// Calculate Stripe fees
					$stripe_fee = round(($percentageFee * $Orderamount) + $fixedFee, 0); // Round to nearest whole number

					$amount = $Orderamount - $stripe_fee; // Amount after Stripe fees

					// Get the percentage the admin receives
					$percent = get_option("kb_conncted_account_commission"); // e.g., 20 for 20%

					// Percentage the connected account receives
					$percentage = (100 - $percent) / 100;

					// Calculate the amount for the connected account
					$percentageAmountInPence = round($amount * $percentage, 0); // Round to nearest whole number

					$res = array(
						"OrderAmount" => $Orderamount,
						"stripe_fee" => $stripe_fee,
						"amount" => $amount,
						"percentageAmountInPence" => $percentageAmountInPence,
					);

					// Log the calculation results for debugging
					//error_log(print_r($res, true));
					if (!$transfer_exists) {
						$site_title = get_bloginfo('name');
						try {
							// Create PaymentIntent
							$paymentIntent = \Stripe\PaymentIntent::create([
								'amount' => $Orderamount,
								'currency' => strtolower(get_woocommerce_currency()),
								'payment_method_data' => [
									'type' => 'card',
									'card' => [
										'token' => $token,
									],
								],
								'statement_descriptor' => $site_title,
								'statement_descriptor_suffix' => $site_title,
								'confirm' => true,
								'automatic_payment_methods' => [
									'enabled' => true,
									'allow_redirects' => 'never',
								],
								'transfer_data' => [
									'destination' => $connected_account_id,
									'amount' => $percentageAmountInPence,
								],
							]);

							// Payment was successful
							//echo 'Payment successful, PaymentIntent ID: ' . $paymentIntent->id;

							/* echo "<br><pre>";
							print_r($paymentIntent);
							echo "</pre>"; */
							if ($paymentIntent && $paymentIntent->status == "succeeded") {
								
								$wpdb->insert(
									$custom_order_table,
									[
										"order_id" => $order_id,
										"amount" => $amount,
									],
									["%d", "%d"]
								);
								
								$wpdb->insert($commission_table, [
									"connected_account_id" => $connected_account_id,
									"amount" => $percentageAmountInPence, 
									"order_id" => $order_id,
								], ["%s", "%f", "%d"]);
							}
							
							
							
							
							
							$order->payment_complete();
							$order->add_order_note(
								__("Payment successfully processed by Custom Stripe.", "woocommerce")
							);
							WC()->cart->empty_cart();
							//exit;
							 return [
								"result" => "success",
								"redirect" => $this->get_return_url($order),
							];
							
						} catch (\Stripe\Exception\ApiErrorException $e) {
							// Handle error
							//echo 'Error: ' . $e->getMessage();
								$wpdb->insert($transferlog_table, [
								"error_message" => $e->getMessage(),
								"error_code" => $e->getCode(),
								"order_id" => $order_id,
								"connected_account_id" => $connected_account_id,
								"amount" => $percentageAmountInPence,
								"currency" => strtolower(get_woocommerce_currency()), // Add currency field
								"status" => 'failed', // Assuming 'failed' status on error
								"charge_id" => '357hhhhh', // Placeholder for charge ID
								"timestamp" => current_time('mysql'),
							], ["%s", "%d", "%d", "%s", "%f", "%s", "%s", "%s"]);
						}
					}else{
						$order->payment_complete();
						$order->add_order_note(
							__("Payment successfully processed by Custom Stripe.", "woocommerce")
						);
						WC()->cart->empty_cart();

						return [
							"result" => "success",
							"redirect" => $this->get_return_url($order),
						];
					}	
				}
			} catch (\Stripe\Exception\CardException $e) {
				// If card exception occurs, display error message
				wc_add_notice(
					__("Payment error: ", "woocommerce") . $e->getMessage(),
					"error"
				);
				error_log("Stripe Card Exception: " . $e->getMessage());
				
				return [
					"result" => "failure",
					"redirect" => wc_get_checkout_url(),
				];
			} catch (\Exception $e) {
				// If general exception occurs, display error message
				wc_add_notice(
					__("Payment error: ", "woocommerce") . $e->getMessage(),
					"error"
				);
				error_log("General Exception: " . $e->getMessage());
				return [
					"result" => "failure",
					"redirect" => wc_get_checkout_url(),
				];
			}
		}

    }