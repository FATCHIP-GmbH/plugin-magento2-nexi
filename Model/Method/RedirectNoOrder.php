<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Payment\Model\InfoInterface;

/**
 * This base class is used for payment methods that should NOT create an order before redirecting the customer to the payment redirect page
 */
abstract class RedirectNoOrder extends RedirectPayment
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
    protected $useInitializeStep = false;

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
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/returned');
    }

    /**
     * URL where payment/order is finished
     *
     * @return string
     * @throws \Exception
     */
    public function getFinishUrl()
    {
        // General no order redirect re-entry is not implemented yet.
        // This method has to be overloaded with a URL that has code that finishes the payment/order.
        throw new \Exception("An error occured.");
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

        return $this;
    }

    public function getAuthRequestFromQuote()
    {
        $request = $this->authRequest->generateRequestFromQuote($this->checkoutSession->getQuote(), $this, true, true);
        $url = $this->authRequest->getFullApiEndpoint($this->getApiEndpoint())."?".http_build_query($request);
        return $url;
    }
}
