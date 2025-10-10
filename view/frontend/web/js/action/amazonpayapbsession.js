/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/model/customer'
], function ($, urlBuilder, storage, fullScreenLoader, customer) {
    'use strict';
    return function (orderId) {
        var serviceUrl;

        var request = {
            orderId: orderId
        };
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:orderId/computop-getAmazonPayApbSession', {
                orderId: orderId
            });
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/computop-getAmazonPayApbSession', {});
        }

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl,
            JSON.stringify(request)
        );
    };
});
