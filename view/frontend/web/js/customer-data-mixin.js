define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (customerData) {
        customerData.onAjaxComplete = wrapper.wrap(customerData.onAjaxComplete, function (originalFunction, jsonResponse, settings) {
            if (window.hasOwnProperty('computopRedirectInitiated') && window.computopRedirectInitiated === true) {
                if (settings.url.indexOf('/payment-information') !== -1) { // Request that creates the order
                    let intResponse = Number.parseInt(jsonResponse);
                    if (Number.isInteger(intResponse)) { // Int response = order id -> order creation successful
                        // Hinders the code to try to reload certain parts of the checkout, when a redirect has already been initiated
                        // Otherwise race conditions with session flags could occur that lead to faulty behaviour
                        return;
                    }
                    delete window.computopRedirectInitiated;
                }
            }
            return originalFunction(jsonResponse, settings);
        });

        // Return modified object
        return customerData;
    };
});