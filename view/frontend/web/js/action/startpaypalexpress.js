/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Customer/js/model/customer'
], function ($, urlBuilder, storage, customer) {
    'use strict';

    /** Override default place order action and add agreement_ids to request */
    return function (quoteId, data, len) {
        var serviceUrl;

        var request = {
            cartId: quoteId,
            data: data,
            len: len
        };
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/computop-startPayPalExpress', {
                cartId: quoteId
            });
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/computop-startPayPalExpress', {});
        }

        return storage.post(
            serviceUrl,
            JSON.stringify(request)
        ).done(
            function (response) {
                // do nothing
            }
        ).fail(
            function (response) {
                //errorProcessor.process(response, messageContainer);
                alert('An error occured.');
            }
        );
    };
});
