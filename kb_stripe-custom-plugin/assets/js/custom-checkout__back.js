var customStripePayment = function($) {
    console.log('customStripePayment function is called');

    // Variable to track if token has been generated
    var tokenGenerated = false;

    setTimeout(function() {
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
                            color: '#fce883',
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

            $('form[name="checkout"]').on('submit', async function(event) {
                event.preventDefault();


                if (tokenGenerated) {
                    console.log('Token already generated, skipping...');
                    return;
                }

                const { token, error } = await stripe.createToken(card);

                if (error) {
                    console.error('Stripe token error:', error);
                } else {
                    console.log('Stripe token created:', token.id);

                    // Add the token to the form
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', custom_stripe_vars.stripe_token_name);
                    hiddenInput.setAttribute('value', token.id);
                    this.appendChild(hiddenInput);

                    // Set token generated flag to true
                    tokenGenerated = true;

                    // Submit the form with the token
                    var formData = $(this).serialize();
                    $.ajax({
                        type: 'POST',
                        url: wc_checkout_params.checkout_url,
                        data: formData,
                        success: function(response) {
                            if (response.result === 'success') {
                                window.location = response.redirect;
                            }
                        }
                    });
                }
            });
        };

    }, 3000);
};

jQuery(document).ready(function($) {
    customStripePayment($);
});
