<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;

class AmazonPay extends ServerToServerPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_AMAZONPAY;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "amazonAPA.aspx";

    /**
     * @var string
     */
    protected $requestType = "AMAZONPAY";

    /**
     * Determines if auth requests adds shipping address parameters to the request
     *
     * @var bool
     */
    protected $addShippingAddressData = true;

    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $finishOrderAfterAuth = false;

    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $handleSpecificAfterAuth = true;

    /**
     * Determines if initialize payment step shall be used instead of direct authorization
     *
     * @var bool
     */
    protected $useInitializeStep = true;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'telephone',
    ];

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'merchantId' => $this->getPaymentConfigParam('merchant_id'),
            'publicKeyId' => $this->getPaymentConfigParam('public_key_id'),
            'buttonColor' => $this->getPaymentConfigParam('button_color'),
        ];
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $shippingAddress = $order->getBillingAddress();
        // getIsVirtual returns int and not bool!
        if (!$order->getIsVirtual() && !empty($order->getShippingAddress())) { // is not a digital/virtual order? -> add shipping address
            $shippingAddress = $order->getShippingAddress();
        }

        $infoInstance = $this->getInfoInstance();
        return [
            'checkoutMode' => 'ProcessOrder',
            'TxType' => $this->getPaymentConfigParam('capture_method') == CaptureMethods::CAPTURE_AUTO ? 'AuthorizeWithCapture' : 'Authorize',
            'CountryCode' => $this->getPaymentConfigParam('marketplace_country_code'), // Country code of used marketplace. Options EU, UK, US and JP.
            'Name' => $shippingAddress->getFirstname()." ".$shippingAddress->getLastname(),
            'SDZipcode' => $shippingAddress->getPostcode(),
            'sdPhone' => $this->getTelephoneNumber($order, $infoInstance),
            #'ShopUrl' => '',
        ];
    }

    /**
     * Amazon response does NOT include the Code param.....
     * Therefor standard success check can not be used
     *
     * @param  array $response
     * @return void
     */
    protected function checkResponseForSuccess($response)
    {
        if (!isset($response['Status']) || $response['Status'] != ComputopConfig::STATUS_OK) {
            throw new LocalizedException(__($response['Description'] ?? 'Error'));
        }
    }

    /**
     * @param InfoInterface $payment
     * @param               $response
     * @return void
     */
    protected function handleResponseSpecific(InfoInterface $payment, $response)
    {
        if (isset($response['buttonsignature'], $response['buttonpayload'])) {
            // write amazon fields to session
            $this->checkoutSession->setComputopAmazonPaySignature($response['buttonsignature']);
            $this->checkoutSession->setComputopAmazonPayPayload($response['buttonpayload']);
        }
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
        parent::initialize($paymentAction, $stateObject);

        if ($stateObject !== null) {
            $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $stateObject->setData('status', 'pending_payment');
        }
    }
}
