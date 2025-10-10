define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/action/place-order'
    ],
    function (Component, $, additionalValidators, url, $t, checkoutData, selectPaymentMethodAction, placeOrderAction) {
        'use strict';
        return Component.extend({
            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getRouteName: function () {
                return 'nexi';
            },
            initialize: function () {
                let parentReturn = this._super();
                if(this.getCode() === window.checkoutConfig.payment.computop.cancelledPaymentMethod) {
                    selectPaymentMethodAction({method: this.getCode()});
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    this.messageContainer.addSuccessMessage({'message': $t('Payment has been cancelled.')});
                }
                return this;
            },
            isDateValid: function (year, month, day) {
                if (!$.isNumeric(year) || !$.isNumeric(month) || !$.isNumeric(day)) {
                    return false;
                }

                var birthDate = new Date(year + "-" + month + "-" + day);
                if (birthDate.toString() === 'Invalid Date' ||
                    birthDate.getFullYear() !== parseInt(year) ||
                    birthDate.getMonth() + 1 !== parseInt(month) ||
                    birthDate.getDate() !== parseInt(day)
                ) {
                    return false;
                }
                return true;
            },
            isBirthdayValid: function (year, month, day) {
                var birthDate = new Date(year + "-" + month + "-" + day);
                var minDate = new Date(new Date().setYear(new Date().getFullYear() - 18));
                if (birthDate > minDate) {
                    return false;
                }
                return true;
            },
            continueToComputop: function () {
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction(this.getRouteName() + '/onepage/redirect/');
                    return false;
                }
            },
            redirect: function(redirectUrl) {
                window.location.replace(url.build(redirectUrl));
            },
            getFrontendConfigParam: function(param) {
                if (this.getCode() in window.checkoutConfig.payment.computop && param in window.checkoutConfig.payment.computop[this.getCode()]) {
                    return window.checkoutConfig.payment.computop[this.getCode()][param];
                }
                return null;
            },
            handleRedirectAction: function(url) {
                var self = this;

                this.isPlaceOrderActionAllowed(false);
                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(
                        function () {
                            self.afterPlaceOrder();
                            self.redirect(url);
                        }
                );
            }
        });
    }
);
