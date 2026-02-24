define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    return function () {
        // 1. Marks the 'cart' section in LocalStorage as outdated
        customerData.invalidate(['cart']);

        // 2. Forces the immediate reload of the 'cart' section from the server
        customerData.reload(['cart'], true);
    };
});