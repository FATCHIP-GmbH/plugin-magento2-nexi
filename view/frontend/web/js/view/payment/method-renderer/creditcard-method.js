define(
    [
        'jquery',
        'Fatchip_Nexi/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Fatchip_Nexi/js/action/creditcardrequestdata',
    ],
    function ($, Component, additionalValidators, getCreditcardRequestData) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/creditcard',
                template_silent: 'Fatchip_Nexi/payment/creditcard_silent',
                number: '',
                securityCode: '',
                expiryDate: '',
                expiryMonth: '',
                expiryYear: '',
                brand: '',
                cardholder: '',
                dataParam: '',
                lenParam: '',
                merchantId: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'number',
                        'securityCode',
                        'expiryDate',
                        'expiryMonth',
                        'expiryYear',
                        'brand',
                        'cardholder',
                        'dataParam',
                        'lenParam',
                        'merchantId'
                    ]);
                return this;
            },
            initialize: function () {
                let parentReturn = this._super();
                let self = this;
                if (this.isSilentMode() === true) {
                    this.expiryMonth(1);
                    this.expiryYear(this.getCurrentYear());

                    this.expiryMonth.subscribe(function (value) {
                        self.updateExpiryDate();
                    });
                    this.expiryYear.subscribe(function (value) {
                        self.updateExpiryDate();
                    });
                }
                return parentReturn;
            },
            updateExpiryDate: function () {
                this.expiryDate(this.expiryYear() + this.expiryMonth());
            },
            isSilentMode: function () {
                if (this.getFrontendConfigParam('mode') === 'silent') {
                    return true;
                }
                return false;
            },
            getTemplate: function () {
                if (this.isSilentMode() === true) {
                    return this.template_silent;
                }
                return this.template;
            },
            getCcAvailableTypes: function () {
                return this.getFrontendConfigParam('types');
            },
            getCcMonths: function() {
                let months = [];
                for (let i = 1; i <= 12; i++) {
                    let value = i;
                    if (value < 10) {
                        value = "0" + value;
                    }
                    months.push({'id': value, 'title': i});
                }
                return months;
            },
            getCurrentYear: function() {
                return new Date().getFullYear();
            },
            getCcYears: function() {
                let years = [];
                let currentYear = this.getCurrentYear();
                for (let i = 0; i <= 14; i++) {
                    let year = currentYear + i;
                    years.push({'id': year, 'title': year});
                }
                return years;
            },
            handleSilentModeAction: function() {
                var self = this;

                this.isPlaceOrderActionAllowed(false);
                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(
                    function (orderId) {
                        self.afterPlaceOrder();

                        getCreditcardRequestData(self, orderId); // will trigger sendDataToComputop() when successful
                    }
                );
            },
            sendDataToComputop: function() {
                $('#co-payment-form').attr("action", "https://www.computop-paygate.com/payNow.aspx");
                $('#co-payment-form').attr("method", "POST");
                $('#co-payment-form').submit();
            },
            continueToComputop: function () {
                if (this.validate() && additionalValidators.validate()) {
                    let redirectTarget = this.getRouteName() + '/onepage/redirect/';
                    if (this.getFrontendConfigParam('mode') === 'iframe') {
                        redirectTarget = this.getRouteName() + '/onepage/payment/';
                    }
                    if (this.isSilentMode() === true) {
                        this.handleSilentModeAction();
                        return false;
                    }
                    this.handleRedirectAction(redirectTarget);
                    return false;
                }
            }
        });
    }
);
