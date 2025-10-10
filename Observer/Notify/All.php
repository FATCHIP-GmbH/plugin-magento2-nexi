<?php

namespace Fatchip\Computop\Observer\Notify;

use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Event observer for all Notify messages
 */
class All implements ObserverInterface
{
    /**
     * Handly Notify OK status
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $order Order */
        $order = $observer->getOrder();
        $notify = $observer->getResponse();
        if (empty($order) || empty($notify)) {
            return;
        }

        /** @var BaseMethod $methodInstance */
        $methodInstance = $order->getPayment()->getMethodInstance();
        $methodInstance->handleNotifySpecific($order, $notify);
    }
}
