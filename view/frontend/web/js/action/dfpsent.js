/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
], function ($, urlBuilder, storage) {
    'use strict';

    return function () {
        var serviceUrl = urlBuilder.createUrl('/carts/mine/computop-dfpSent', {});
        var request = {};

        return storage.post(
            serviceUrl,
            JSON.stringify(request)
        ).done(
            function (response) {
                if (response.success === true) {
                    // do nothing
                }
            }
        ).fail(
            function (response) {}
        );
    };
});
