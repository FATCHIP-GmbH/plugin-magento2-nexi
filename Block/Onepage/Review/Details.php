<?php

namespace Fatchip\Nexi\Block\Onepage\Review;

use Magento\Sales\Model\Order\Address;
use Magento\Checkout\Block\Cart\Totals;

class Details extends Totals
{
    /**
     * Address object
     *
     * @var Address
     */
    protected $_address;

    /**
     * Returns the totals of the current quote
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->getQuote()->getTotals();
    }

    /**
     * Returns the current shipping address of the quote
     *
     * @return Address
     */
    public function getAddress()
    {
        if (!$this->_address) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        return $this->_address;
    }
}
