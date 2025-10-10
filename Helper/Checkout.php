<?php

namespace Fatchip\Nexi\Helper;

use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;

class Checkout extends Base
{
    /**
     * Checkout session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutData;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Customer\Model\Session            $customerSession
     * @param \Magento\Checkout\Helper\Data              $checkoutData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->customerSession = $customerSession;
        $this->checkoutData = $checkoutData;
    }

    /**
     * Generate a string that represents the basket, used to compare it at different times during checkout
     *
     * @param  Quote $oQuote
     * @return string
     */
    public function getQuoteComparisonString(Quote $oQuote)
    {
        $sComparisonString = "";
        foreach ($oQuote->getAllItems() as $oItem) { // add invoice items for all order items
            $sComparisonString .= $oItem->getProductId().$oItem->getSku().$oItem->getQty()."|";
        }
        return $sComparisonString;
    }

    /**
     * Get checkout method
     *
     * @param  Quote $quote
     * @return string
     */
    public function getCurrentCheckoutMethod(Quote $quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }

    /**
     * @param  string $fullName
     * @return array
     */
    public function splitFullName($fullName)
    {
        $split = explode(" ", $fullName);
        $firstname = array_shift($split);
        $lastname = implode(" ", $split);
        return [$firstname, $lastname];
    }
}
