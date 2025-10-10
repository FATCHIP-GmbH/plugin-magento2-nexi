define(
    [
        'Fatchip_Nexi/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Nexi/payment/paypal'
            }
        });
    }
);
