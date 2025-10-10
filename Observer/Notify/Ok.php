<?php

namespace Fatchip\Nexi\Observer\Notify;

use Fatchip\Nexi\Helper\Order as OrderHelper;
use Fatchip\Nexi\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Event observer for Notify OK status
 */
class Ok implements ObserverInterface
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * Constructor.
     *
     * @param OrderHelper $orderHelper
     */
    public function __construct(OrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * Handly Notify OK status
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $oOrder Order */
        $order = $observer->getOrder();
        if (empty($order)) {
            return;
        }

        $transId = null;
        if (!empty($observer->getResponse()['TransID'])) {
            $transId = $observer->getResponse()['TransID'];
        }

        $payment = $order->getPayment();

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();
        if ($methodInstance->isInitializeNeeded() === true) { // initializeNeeded true means the payment has not been authorized yet - do it here
            $methodInstance->authorizePayment($payment, $transId);

            $order->save(); // Transaction is created in order save and therefor needs to be done
        }

        $this->orderHelper->createInvoice($order, $transId);
    }
}
