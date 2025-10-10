<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

class PayPal extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_PAYPAL;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "ExternalServices/paypalorders.aspx";

    /**
     * @var string
     */
    protected $apiExpressEndpoint = "paypalComplete.aspx";

    /**
     * @var bool
     */
    protected $isExpressOrder = false;

    /**
     * @var bool
     */
    protected $isExpressAuthStep = false;

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        if ($this->isExpressAuthStep() === true) {
            return $this->apiExpressEndpoint;
        }
        return $this->apiEndpoint;
    }

    /**
     * @param  bool $isExpressOrder
     * @return void
     */
    public function setIsExpressOrder($isExpressOrder)
    {
        $this->isExpressOrder = $isExpressOrder;
    }

    /**
     * Returns if current PayPal order process is in PayPal Express mode
     *
     * @return bool
     */
    public function isExpressOrder()
    {
        if ($this->checkoutSession->getComputopPpeIsExpressOrder() === true) {
            return true;
        }
        return $this->isExpressOrder;
    }


    /**
     * @param  bool $isExpressAuthStep
     * @return void
     */
    public function setIsExpressAuthStep($isExpressAuthStep)
    {
        $this->isExpressAuthStep = $isExpressAuthStep;
    }

    /**
     * Returns if current PayPal order process is in PayPal Express mode
     *
     * @return bool
     */
    public function isExpressAuthStep()
    {
        if ($this->checkoutSession->getComputopPpeIsExpressAuthStep() === true) {
            return true;
        }
        return $this->isExpressAuthStep;
    }

    /**
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        if ($this->isExpressOrder() === true) {
            return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/ppeReturn');
        }
        return parent::getSuccessUrl();
    }

    /**
     * Returns redirect url for failure case
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        if ($this->isExpressOrder() === true) {
            return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/ppeReturn');
        }
        return parent::getFailureUrl();
    }


    /**
     * @param Order $order
     * @return array
     */
    protected function getPayPalAddressData(Order $order)
    {
        $address = $order->getShippingAddress();
        if (empty($address)) {
            $address = $order->getBillingAddress();
        }
        $street = $address->getStreet();
        $street = is_array($street) ? implode(' ', $street) : $street; // street may be an array
        return [
            'FirstName' => $address->getFirstname(),
            'LastName' => $address->getLastname(),
            'AddrStreet' => $street,
            'AddrCity' => $address->getCity(),
            'AddrZip' => $address->getPostcode(),
            'AddrCountryCode' => $address->getCountryId(),
        ];
    }

    /**
     * @param Order|null $order
     * @return array
     */
    protected function getPayPalCompleteCallParams(?Order $order = null)
    {
        $params = [
            'PayID' => $this->checkoutSession->getComputopPpePayId(),
        ];
        if (!empty($order)) {
            $params['RefNr'] = $this->authRequest->getApiHelper()->getReferenceNumber($this->checkoutSession->getComputopTmpRefnr());
        }
        return $params;
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if ($this->isExpressAuthStep() === true) {
            return $this->authorizeServerToServer($payment, $amount);
        }
        return parent::authorize($payment, $amount);
    }

    /**
     * @inheritdoc
     */
    protected function authorizeServerToServer(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $response = $this->authRequest->sendCurlRequest($payment->getOrder(), $payment, $amount);
        $this->checkoutSession->setComputopPpeCompleteResponse($response);
        $this->handleResponse($payment, $response);

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param               $response
     * @return void
     */
    protected function handleResponseSpecific(InfoInterface $payment, $response)
    {
        if ($this->isExpressAuthStep() === true && !empty($response['PayID'])) {
            $this->refNrChange->changeRefNr($response['PayID'], $payment->getOrder()->getIncrementId());
        }
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        if ($this->isExpressAuthStep() === true) {
            return $this->getPayPalCompleteCallParams($order);
        }

        $params = [
            'Capture' => $this->getPaymentConfigParam('capture_method'),
            #'NoShipping' => '1',
        ];

        if ($params['Capture'] == CaptureMethods::CAPTURE_MANUAL) {
            $params['TxType'] = 'Auth';
        }
        if ($this->isExpressOrder() === true) {
            $params['PayPalMethod'] = 'shortcut';
        } elseif (!empty($order)) {
            $params['mode'] = 'redirect';
            $params = array_merge($params, $this->getPayPalAddressData($order));
        }
        return $params;
    }

    /**
     * @param $quote
     * @return void
     */
    public function preReviewPlaceOrder($quote)
    {
        $this->checkoutSession->setComputopPpeIsExpressOrder(true);
        $this->checkoutSession->setComputopPpeIsExpressAuthStep(true);
        $this->setIsExpressOrder(true);
        $this->setIsExpressAuthStep(true);
    }

    /**
     * @return string|false
     */
    public function postReviewPlaceOrder()
    {
        $response = $this->checkoutSession->getComputopPpeCompleteResponse();
        if ($this->apiHelper->isSuccessStatus($response)) {
            // "last successful quote"
            #$quoteId = $quote->getId();
            #$this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            #$quote->setIsActive(false)->save();

            return 'checkout/onepage/success';
        } elseif(!empty($response['paypalurl'])) {
            return $response['paypalurl'];
        }
        return false;
    }

    /**
     * Returns if payment method
     *
     * @return bool
     */
    public function isNotifyPaymentMethod()
    {
        if ($this->isExpressOrder() === true || $this->isExpressAuthStep() === true) {
            return false; // PPE orders dont receive a Notify call, but "normal" PayPal orders do
        }
        return $this->isNotifyPaymentType;
    }

    /**
     * @return bool
     */
    public function isInitializeNeeded()
    {
        if ($this->isExpressOrder() === true || $this->isExpressAuthStep() === true) {
            return false;
        }
        return $this->useInitializeStep;
    }
}
