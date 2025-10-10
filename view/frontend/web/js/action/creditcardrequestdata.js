/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function ($, urlBuilder, storage, fullScreenLoader, quote, customer) {
    'use strict';

    /** Override default place order action and add agreement_ids to request */
    return function (baseView, orderId) {
        var serviceUrl;

        var request = {
            orderId: orderId,
            javaEnabled: navigator.javaEnabled(),
            screenHeight: screen.height,
            screenWidth: screen.width,
            colorDepth: screen.colorDepth,
            timeZoneOffset: new Date().getTimezoneOffset(),
        };

        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:orderId/computop-creditcardRequestData', {
                orderId: orderId,
            });
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/computop-creditcardRequestData', {});
        }

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl,
            JSON.stringify(request)
        ).done(
            function (response) {
                if (response.success === true && response.data_param !== undefined  && response.len_param !== undefined
                    && response.data_param.length > 0 && response.len_param.length > 0
                ) {
                    baseView.dataParam(response.data_param);
                    baseView.lenParam(response.len_param);
                    baseView.merchantId(response.merchant_id);

                    baseView.sendDataToComputop();
                } else {
                    alert(response.errormessage);
                }
                fullScreenLoader.stopLoader();
            }
        ).fail(
            function (response) {
                //errorProcessor.process(response, messageContainer);
                alert('An error occured.');
                fullScreenLoader.stopLoader();
            }
        );
    };
});
