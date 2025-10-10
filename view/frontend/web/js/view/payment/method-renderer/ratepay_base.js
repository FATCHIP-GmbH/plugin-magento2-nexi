define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'mage/translate'
    ],
    function (Component, quote, customer, $t) {
        'use strict';
        return Component.extend({
            isBirthdaySet: function () {
                if (customer.customerData.dob == undefined || customer.customerData.dob === null) {
                    return false;
                }
                return true;
            },
            isCompanySet: function () {
                if (quote.billingAddress() != null && typeof quote.billingAddress().company != "undefined" && quote.billingAddress().company !== null && quote.billingAddress().company.length > 1) {
                    return true;
                }
                return false;
            },
            isTelephoneSet: function () {
                if (quote.billingAddress() != null && typeof quote.billingAddress().telephone != "undefined" && quote.billingAddress().telephone !== null && quote.billingAddress().telephone.length > 1) {
                    return true;
                }
                return false;
            },

            isB2BOrder: function () {
                if (this.isCompanySet() === true) {
                    return true;
                }
                return false;
            },

            isBirthdayNeeded: function () {
                if (this.isB2BOrder() === false && this.isBirthdaySet() === false) {
                    return true;
                }
                return false;
            },
            isTelephoneNumberNeeded: function () {
                if (this.isTelephoneSet() === false) {
                    return true;
                }
                return false;
            },

            isBirthdayValid: function (year, month, day) {
                var minDate = new Date(new Date().setYear(new Date().getFullYear() - 18));
                var birthDate = new Date(year + "-" + month + "-" + day);
                if (year == "" || month == "" || day == "" || !(birthDate instanceof Date && !isNaN(birthDate)) || birthDate > minDate) {
                    return false;
                }
                ;
                return true;
            },

            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.isTelephoneNumberNeeded()) {
                    parentReturn.additional_data.telephone = this.telephone();
                }
                if (this.isBirthdayNeeded()) {
                    parentReturn.additional_data.dateofbirth = this.birthyear() + "-" + this.birthmonth() + "-" + this.birthday();
                } else if(this.isBirthdaySet() === true) {
                    parentReturn.additional_data.customer_dateofbirth = customer.customerData.dob;
                }
                return parentReturn;
            },
            validate: function () {
                if (this.isTelephoneNumberNeeded() === true && this.telephone() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid telephone number!')});
                    return false;
                }
                if (this.isBirthdayNeeded() === true && !this.isBirthdayValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use Ratepay payment methods!')});
                    return false;
                }
                return true;
            }
        });
    }
);
