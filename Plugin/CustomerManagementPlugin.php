<?php

namespace Fatchip\Nexi\Plugin;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote as QuoteEntity;

class CustomerManagementPlugin
{
    protected $paymentMethodSkipAddressValidation = [
        ComputopConfig::METHOD_PAYPAL,
    ];

    /**
     * Around plugin for the validateAddresses method
     *
     * @param CustomerManagement $subject
     * @param \Closure           $proceed
     * @param QuoteEntity        $quote
     * @return void
     */
    public function aroundValidateAddresses(CustomerManagement $subject, \Closure $proceed, QuoteEntity $quote)
    {
        if ($quote->getCustomerIsGuest() && in_array($quote->getPayment()->getMethod(), $this->paymentMethodSkipAddressValidation)) {
            return;
        }
        $proceed($quote);
    }
}
