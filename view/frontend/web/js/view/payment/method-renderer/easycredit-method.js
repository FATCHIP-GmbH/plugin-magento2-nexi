define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/base',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/translate'
    ],
    function (Component, $, additionalValidators, customer, quote, setPaymentInformationAction, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/easycredit',
                birthday: '',
                birthmonth: '',
                birthyear: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear'
                    ]);
                return this;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestBirthday()) {
                    parentReturn.additional_data.dateofbirth = this.birthyear() + '-' + this.birthmonth() + '-' + this.birthday();
                }
                return parentReturn;
            },
            validate: function () {
                if (this.requestBirthday() === true && !this.isDateValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid date.')});
                    return false;
                }
                if (this.requestBirthday() === true && !this.isBirthdayValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
                    return false;
                }
                return true;
            },
            requestBirthday: function () {
                if (customer.customerData.dob == undefined || customer.customerData.dob === null) {
                    return true;
                }
                return false;
            },
            setPaymentInformation: function () {
                return setPaymentInformationAction(
                    this.messageContainer,
                    {
                        method: this.getCode()
                    }
                );
            },
            continueToComputop: function () {
                if (this.validate() && additionalValidators.validate()) {
                    if (quote.isVirtual() === true) {
                        let self = this;
                        $.when(
                            this.setPaymentInformation()
                        ).done(function () {
                            return self.redirectToComputop();
                        });
                    } else {
                        return this.redirectToComputop();
                    }
                }
            },
            redirectToComputop: function () {
                let data = this.getData();
                let addParam = '';
                if (data.additional_data !== undefined && data.additional_data.dateofbirth !== undefined) {
                    addParam = '?dob=' + data.additional_data.dateofbirth;
                }
                this.redirect(this.getRouteName() + '/onepage/redirect/' + addParam);
                return false;
            }
        });
    }
);
