define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/base',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Fatchip_Nexi/js/action/amazonpayapbsession',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function (Component, $, quote, checkoutData, getAmazonPayApbSession, fullScreenLoader, additionalValidators, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/amazonpay',
                telephone: '',
                buttonLoaded: false
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'buttonLoaded',
                        'telephone'
                    ]);
                return this;
            },
            validate: function () {
                if (this.requestTelephone() === true && this.telephone() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter your telephone number!')});
                    return false;
                }
                return true;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestTelephone()) {
                    parentReturn.additional_data.telephone = this.telephone();
                }
                return parentReturn;
            },
            initialize: function () {
                let parentReturn = this._super();
                if (checkoutData.getSelectedPaymentMethod() === this.getCode()) {
                    this.initAmazonPayButton();
                }
                return parentReturn;
            },
            selectPaymentMethod: function () {
                this.initAmazonPayButton();
                return this._super();
            },
            initAmazonPayButton: function () {
                if (this.buttonLoaded() === false) {
                    var amazonPayMethod = this;
                    if (!document.getElementById('AmazonPayAdditionalPaymentButton')) { // button might not be rendered yet
                        setTimeout(function() {
                            window.requestAnimationFrame(function() {
                                amazonPayMethod.initAmazonPayButton();
                            });
                        }, 250);
                    } else {
                        $.getScript("https://static-eu.payments-amazon.com/checkout.js", function () {
                            amazonPayMethod.renderAmazonPayButton();
                        });
                        this.buttonLoaded(true);
                    }
                }
            },
            getOrderTotal: function () {
                var total = quote.getTotals();
                if (total) {
                    return parseFloat(total()['base_grand_total']);
                }
                return 0;
            },
            getCurrency: function () {
                return quote.totals().base_currency_code;
            },
            requestTelephone: function () {
                if (quote.billingAddress() == null || (typeof quote.billingAddress().telephone != 'undefined' && quote.billingAddress().telephone != '')) {
                    return false;
                }
                return true;
            },
            renderAmazonPayButton: function () {
                var self = this;

                let buttonConfig = {
                    // set checkout environment
                    merchantId: this.getFrontendConfigParam('merchantId'),
                    publicKeyId: this.getFrontendConfigParam('publicKeyId'),
                    ledgerCurrency: this.getCurrency(),
                    //productType: quote.isVirtual() ? 'PayOnly' : 'PayAndShip', // disabled until Computop implements "PayOnly" mode for Amazon Pay
                    productType: 'PayAndShip',
                    placement: 'Checkout',
                    buttonColor: this.getFrontendConfigParam('buttonColor')
                };

                let amazonPayButton = amazon.Pay.renderButton('#AmazonPayAdditionalPaymentButton', buttonConfig);
                amazonPayButton.onClick(function(){
                    if (self.validate() && additionalValidators.validate()) {
                        self.isPlaceOrderActionAllowed(false);
                        self.getPlaceOrderDeferredObject()
                            .fail(
                                function () {
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                            function (orderId) {
                                self.afterPlaceOrder();

                                let ajaxCall = getAmazonPayApbSession(orderId);
                                ajaxCall.done(
                                    function (response) {
                                        fullScreenLoader.stopLoader();
                                        if (response && response.success === true) {
                                            amazonPayButton.initCheckout({
                                                estimatedOrderAmount: {
                                                    "amount": self.getOrderTotal(),
                                                    "currencyCode": self.getCurrency()
                                                },
                                                createCheckoutSessionConfig: {
                                                    payloadJSON: response.payload,
                                                    signature: response.signature,
                                                    publicKeyId: self.getFrontendConfigParam('publicKeyId')
                                                }
                                            });
                                        } else if (response && response.success === false) {
                                            alert('An error occured.');
                                        }
                                    }
                                ).fail(
                                    function (response) {
                                        fullScreenLoader.stopLoader();
                                        //errorProcessor.process(response, messageContainer);
                                        alert('An error occured.');
                                    }
                                );
                            }
                        );
                    }
                });
            }
        });
    }
);
