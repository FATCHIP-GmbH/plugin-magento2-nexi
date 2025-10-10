<?php

namespace Fatchip\Nexi\Model\Method;

use Magento\Payment\Model\InfoInterface;

abstract class RedirectPayment extends BaseMethod
{
    /**
     * @var string
     */
    protected $requestType = "REDIRECT";

    /**
     * Determines if initialize payment step shall be used instead of direct authorization
     *
     * @var bool
     */
    protected $useInitializeStep = true;

    /**
     * Returns if auth request is needed
     * Can be overloaded by other classes
     *
     * @return bool
     */
    protected function isAuthRequestNeeded()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasTransactionToBeSetPreAuthorization()
    {
        // default = true
        if ($this->isInitializeNeeded() === true) {
            // return false if init is used
            return false;
        }

        return $this->setTransactionPreAuthorization;
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->initializeRedirectPayment($stateObject);
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        if ($this->isInitializeNeeded() === false) {
            $this->initializeRedirectPayment();
        }

        return $this;
    }

    /**
     * @param  InfoInterface $payment
     * @param  array         $response
     * @return void
     */
    protected function finalizeOrder(InfoInterface $payment, $response)
    {
        // Authorize is supposed to happen when Notify arrives
        #if ($this->isInitializeNeeded() === true) {
        #    $this->authorizePayment($payment, $response);
        #}

        parent::finalizeOrder($payment, $response);
    }

    /**
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initializeRedirectPayment($stateObject = null)
    {
        $payment = $this->getInfoInstance();

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $amount = $order->getTotalDue();

        $transactionId = false;
        if ($this->isAuthRequestNeeded()) {
            $request = $this->authRequest->generateRequestFromOrder($order, $payment, $amount, true, true);

            $url = $this->authRequest->getFullApiEndpoint($this->getApiEndpoint())."?".http_build_query($request);

            $this->checkoutSession->setComputopRedirectUrl($url);

            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $order->setStatus('pending_payment');

            if ($stateObject !== null) {
                $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $stateObject->setData('status', 'pending_payment');
            }

            $order->save();

            $params = $this->authRequest->getParameters();
            $transactionId = $params['TransID'];
        }

        if ($this->hasTransactionToBeSetPreAuthorization() === true) {
            if ($transactionId === false) {
                // This is needed for CC Silent mode. TransactionId is generated before order creation and will later be used for auth request
                $transactionId = $this->paymentHelper->getTransactionId();
            }
            $this->setTransactionId($payment, $transactionId);
        }
    }
}
