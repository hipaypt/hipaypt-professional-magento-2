/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
		'jquery',
		'Magento_Checkout/js/view/payment/default',
		'Magento_Checkout/js/action/place-order',
		'Magento_Checkout/js/action/select-payment-method',
		'Magento_Customer/js/model/customer',
		'Magento_Checkout/js/checkout-data',
		'Magento_Checkout/js/model/payment/additional-validators',
		'mage/url',       
    ],
    function ( $,
    Component,
    placeOrderAction,
    selectPaymentMethodAction,
    customer,
    checkoutData,
    additionalValidators,
    url) {
    'use strict';

        return Component.extend({
            defaults: {
                template: 'Hipay_HipayProfessionalGateway/payment/form',
                redirectAfterPlaceOrder: false,
                transactionResult: ''
            },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }
            var self = this,
                placeOrder,
                emailValidationResult = customer.isLoggedIn(),
                loginFormSelector = 'form[data-role=email-with-possible-login]';
            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }
            if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                $.when(placeOrder).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).done(this.afterPlaceOrder.bind(this));
                return true;
            }
            return false;
        },
        
        selectPaymentMethod: function() {
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.item.method);
            return true;
        },
        afterPlaceOrder: function () {
            $('.checkout').prop("disabled", true);
            var hRandom = Date.now() + Math.floor(Math.random() * 101);
            window.location.replace(url.build('hipay_professional_gateway/payment/redirect/index?'+hRandom));
        },
        
            getHipayProfessionalIcon: function () {
                return window.checkoutConfig.payment.hipay_professional_gateway.paymentImageSrc;
            },
                    
            initObservable: function () {
                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'hipay_professional_gateway';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'transaction_result': this.transactionResult()
                    }
                };
            },

            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.hipay_professional_gateway.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            }
        });
    }
);
