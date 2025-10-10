define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        'mage/translate'
    ],
    function (Component, quote, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/przelewy24',
                accountholder: '',
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'accountholder'
                    ]);
                return this;
            },
            initialize: function () {
                let parentReturn = this._super();
                if (this.accountholder() == '') {
                    let billingAddress = quote.billingAddress();
                    this.accountholder(billingAddress.firstname + " " + billingAddress.lastname);
                }
                return parentReturn;
            },
            requestAccountHolder: function() {
                if (this.isPpro() === true) {
                    return true;
                }
                return false;
            },
            isPpro: function() {
                if (this.getFrontendConfigParam('service') === 'ppro') {
                    return true;
                }
                return false;
            },
            validate: function () {
                if (this.requestAccountHolder() === true && this.accountholder() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please the account holder name!')});
                    return false;
                }
                return true;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.accountholder = this.accountholder();
                return parentReturn;
            }
        });
    }
);
