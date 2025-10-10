<?php

namespace Fatchip\Nexi\Model\Method;

use Magento\Payment\Model\InfoInterface;

abstract class ServerToServerPayment extends BaseMethod
{
    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $finishOrderAfterAuth = true;

    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $handleSpecificAfterAuth = false;


    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->initializePayment($stateObject);
    }

    /**
     * @param \Magento\Framework\DataObject|null $stateObject
     * @return void
     */
    protected function initializePayment(?\Magento\Framework\DataObject $stateObject = null)
    {
        $payment = $this->getInfoInstance();

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $amount = $order->getTotalDue();

        $response = $this->authRequest->sendCurlRequest($order, $payment, $amount);

        $this->handleResponse($payment, $response, $this->finishOrderAfterAuth);

        if ($this->handleSpecificAfterAuth === true) {
            $this->handleResponseSpecific($payment, $response);
        }
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        if ($this->isInitializeNeeded() === false) {
            $this->initializePayment();
        }

        return $this;
    }
}
