/*
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author      Novalnet AG
 * @copyright   Copyright (c) Novalnet
 * @license     https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Novalnet payment form loading script
*/

jQuery(document).ready(function () {
    // Initialize the payment form
    const v13PaymentForm = new NovalnetPaymentForm();
    // Hide the Novalnet payment radio option
    var paymentId = jQuery('#nn_payment_id').val();
    var novalnetPaymentValue = jQuery('#' + paymentId).find('input').val();
    // Hide the uncheck the Novalnet payment if other payment method is selected
    jQuery('input[type="radio"]').on('click',function() {
        if(this.value != novalnetPaymentValue) {
            v13PaymentForm.uncheckPayment();
        }
    });
    // Set the payment form style
    const paymentFormDisplay = {
        iframe: '#v13PaymentForm',
        initForm : {
            orderInformation : {
                lineItems: jQuery.parseJSON(jQuery("#nn_wallet_data").val())
            },
            uncheckPayments: true,
            showButton: false,
        }
    };
    jQuery("div[id*='_novalnet']").append(jQuery('#v13PaymentForm'));
    // Initiatialize the payment form 
    v13PaymentForm.initiate(paymentFormDisplay);
    // Get the payment methods response
    if(jQuery("div[id*='_novalnet']").closest('form').find('.submit_once').length == 1) {
            jQuery('form.checkout-shipping-form').submit(function(e) {
                if(jQuery('#nn_seamless_payment_form_response').length == 0 && jQuery('#'+paymentId+ ' ' + 'input[name="Zahlungsart"]').is(':checked')) {                    
                    // callback for checkout button clicked
                    if(jQuery('#nn_seamless_payment_form_response').length <= 0 || jQuery('#nn_seamless_payment_form_response').val() == '' ) {
                        e.preventDefault();
                    }                    
                    v13PaymentForm.getPayment(
                        (data) => {
                            if(data.result.status == 'ERROR') {                             
                                jQuery('#novalnet_payment_form_error_alert').text(data.result.message);
                                jQuery('#novalnet_payment_form_error_alert').removeClass('d-none'); 
                                jQuery('html, body').animate({
                                    scrollTop: (jQuery('#checkout').offset().top - 160)
                                    }, 500, function() {
                                        jQuery('#checkout').prepend(jQuery('#novalnet_payment_form_error_alert'));
                                });
                                jQuery('.submit_once').prop('disabled',false);
                                return false;
                            } else {
                                jQuery('form.checkout-shipping-form').append('<input type="hidden" name="nn_seamless_payment_form_response" id="nn_seamless_payment_form_response">');
                                jQuery('#nn_seamless_payment_form_response').val(btoa(JSON.stringify(data))); 
                                jQuery('form.checkout-shipping-form').submit();
                                return true;
                            }
                        }
                    )
                }
            }); 
        }
            
    // Handle wallet payment response
    v13PaymentForm.walletResponse({
        "onProcessCompletion": async (response) =>  {
            jQuery('form.checkout-shipping-form').append('<input type="hidden" name="nn_seamless_payment_form_response" id="nn_seamless_payment_form_response">');
            jQuery('#nn_seamless_payment_form_response').val(btoa(JSON.stringify(response)));
            jQuery("div[id*='_novalnet']").closest('form').submit();
        }
    });
    // receive form selected payment action
    v13PaymentForm.selectedPayment(
        (data)=> {
            // Set Novalnet payment as selected
            jQuery('#' + paymentId).find('input').prop('checked', true);
            jQuery('#'+paymentId).find('input[type="radio"]').click();
       }
    )
});
