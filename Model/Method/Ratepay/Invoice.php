<?php

namespace Fatchip\Nexi\Model\Method\Ratepay;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class Invoice extends Base
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_RATEPAY_INVOICE;

    /**
     * @var string|null
     */
    protected $rpMethod = Base::METHOD_INVOICE;

    /**
     * @var string|null
     */
    protected $rpDebitPayType = Base::DEBIT_PAY_TYPE_BANK_TRANSFER;
}
