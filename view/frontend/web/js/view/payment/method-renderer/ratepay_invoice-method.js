define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/ratepay_base'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/ratepay_invoice',
                birthday: '',
                birthmonth: '',
                birthyear: '',
                telephone: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear',
                        'telephone'
                    ]);
                return this;
            }
        });
    }
);
