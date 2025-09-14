var customStripePayment = function($) { 
    console.log('customStripePayment function is called');

    // Variable to track if token has been generated
    var tokenGenerated = false;

    setTimeout(function() {
		/* var placeOrderButton = document.querySelector('#place_order');
		if (placeOrderButton) {
			placeOrderButton.style.display = 'none';
		} */
		//processPaymentButton.style.display = 'none';
		var preventDefaultButton = document.getElementById('preventdefaultbutton');
		preventDefaultButton.disabled = true;
		
        var stripeScript = document.createElement('script');
        stripeScript.src = 'https://js.stripe.com/v3/';
        document.head.appendChild(stripeScript);

        stripeScript.onload = function() {
            console.log('Stripe script loaded');

            var stripe = Stripe(custom_stripe_vars.stripe_pk_key);
            var elements = stripe.elements();

            // Create card element with custom options
            var card = elements.create('card', {
                style: {
                    base: {
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                        fontFamily: 'Arial, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        ':-webkit-autofill': {
                            color: '#000',
                        },
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a',
                    },
                },
                // Customize the postal code field
                hidePostalCode: true
            });

            // Mount the card element to the DOM
            card.mount('#card-element');
			
			var placeOrderButton = document.querySelector('#place_order');
			if (placeOrderButton) {
				//alert("ddd")
				placeOrderButton.style.display = 'none';
			}
			// Create a token when the card details are complete
			 card.on('change', function(event) {
			  if (event.complete) {
				stripe.createToken(card).then(function(result) {
				  if (result.error) {
					// Inform the user if there was an error
					console.error(result.error.message);
					preventDefaultButton.disabled = true;
				  } else {
					// Token created successfully, you can now use result.token
					console.log(result.token);
					preventDefaultButton.disabled = false;
					
					var existingInput = document.querySelector('input[name="' + custom_stripe_vars.stripe_token_name + '"]');
					if (existingInput) {
						existingInput.parentNode.removeChild(existingInput); // Remove existing input if found
					}
					 // Add the token to the form
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', custom_stripe_vars.stripe_token_name);
                    hiddenInput.setAttribute('value', result.token.id);
                    //$('form[name="checkout"]').appendChild(hiddenInput);
					
					var checkoutForm = document.querySelector('form[name="checkout"]');
					checkoutForm.appendChild(hiddenInput);
					
					// You can now use the token for further processing or validation
				  }
				});
			  }
			}); 
			var preventDefaultButton = document.getElementById('preventdefaultbutton');
			if (preventDefaultButton) {
				preventDefaultButton.addEventListener('click', async function(e) {
					e.preventDefault();
					preventDefaultButton.disabled = true;
					if (!validateFields()) {
						preventDefaultButton.disabled = false;
						alert('Please add required info.');
						return;
					} 
					if (!card) {
						alert('Payment method is not properly initialized.');
						/* $('#card-errors').html('<div class="woocommerce-error">Payment method is not properly initialized.</div>'); */
						return;
						
					}
					try {
						var tokenName = custom_stripe_vars.stripe_token_name;
						var token = document.querySelector('input[name="' + tokenName + '"]').value;
						const firstNameField = document.getElementById('billing_first_name_field');
						const emailField = document.getElementById('billing_email');
						//alert(firstNameField.value);
						//alert(emailField.value);
						//return false;
						const response = await fetch(custom_stripe_vars.ajax_url, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: new URLSearchParams({
								action: 'create_payment_intentnew',
								payment_method: card,
								stripeToken: token,
								emailField: emailField.value,
								firstNameField: firstNameField.value,
								
							})
						});
						
						const { clientSecret, orderReturn, order_id, error, recurringType } = await response.json(); 
						if (error) {
							console.error('Failed to create PaymentIntent:', error);
							$('#card-errors').html('<div class="woocommerce-error">' + error + '</div>');
							return;
						}

						if(recurringType){
							window.location = orderReturn;
							$('#card-errors').html('Payment succeed');
							/* console.log("Result:", recurringType);
							$('#card-errors').html('<div class="woocommerce-error">Order is of recurring type</div>'); */
							return;
						} 
							
						// Confirm the PaymentIntent on the client-side
						const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
							payment_method: {
								card: card,
								billing_details: {
									name: 'Customer Name' // Optional
								}
							}
						});
						if (confirmError) {
							console.error('Failed to confirm Payment', confirmError);
							
							//$('#card-errors').html('<div class="woocommerce-error">'+ confirmError + '</div>');
							$('#card-errors').html('<div class="woocommerce-error"> Failed to confirm PaymentI </div>');
							
							preventDefaultButton.disabled = false;
							
						//} else if (paymentIntent.status === 'requires_action') {
							//console.log("need to confirm payment");		
						//} else if (paymentIntent.status === 'succeeded') {
						}else{
							try {
								//const paymentIntentString = JSON.stringify(paymentIntent);
								const res = await fetch(custom_stripe_vars.ajax_url, {
									method: 'POST',
									headers: {
										'Content-Type': 'application/x-www-form-urlencoded'
									},
									body: new URLSearchParams({
										action: 'confirm_payment_intent',
										paymentIntentId: paymentIntent.id,
										paymentMethodId: paymentIntent.payment_method,
										paymentStatus: paymentIntent.status,
										order_id: order_id,
										stripeToken: token,
									})
								});
								const {result, orderReturn, error } = await res.json();
								/* const { clientSecret, orderReturn, order_id, error } = await response.json(); */
								if (error) {
									// Handle API errors
									 console.error('API error:', error);
									 preventDefaultButton.disabled = false;
								} else {
									console.log("Result:", result);
									window.location = orderReturn;
									$('#card-errors').html('Payment succeed');
								}
								
							} catch (err) {
								// Handle fetch or parsing errors
								console.error('Fetch error:', err);
								preventDefaultButton.disabled = false;
							}
							
						}
						
					} catch (error) {
						console.log('Fetch Error:', error);
						preventDefaultButton.disabled = false;
					}
				});
			}

        };

    }, 4000);
};
function validateFields() {
	const firstNameField = document.getElementById('billing_first_name_field');
	const emailField = document.getElementById('billing_email');
	let isValid = true; 
  
	// Validate first name
	const firstName = firstNameField.value;
	if (firstName === '') {
		isValid = false;
	} else if (!/^[A-Za-z]+$/.test(firstName)) { 
		isValid = false;
	}

	// Validate email
	const email = emailField.value;
	if (email === '') {
		isValid = false;
	} else if (!/\S+@\S+\.\S+/.test(email)) { // Simple regex for email validation
		isValid = false;
	}

	return isValid; 
}
jQuery(document).ready(function($) {
    customStripePayment($);
	
});
