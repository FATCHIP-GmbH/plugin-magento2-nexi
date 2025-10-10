<?php

namespace Fatchip\Nexi\Controller\Onepage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Cancel extends Failure implements CsrfAwareActionInterface
{
    /**
     * @return string
     */
    protected function getRedirectUrl()
    {
        return $this->urlBuilder->getUrl('checkout').'#payment';
    }

    /**
     * @param $order
     * @return void
     */
    protected function handleOrder($order)
    {
        parent::handleOrder($order);

        if (!empty($order->getPayment()->getMethod())) {
            $this->checkoutSession->setComputopCancelledPaymentMethod($order->getPayment()->getMethod());
        }
    }
}
