<?php

namespace Fatchip\Nexi\Model\Api\Request;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote;

class Authorization extends Base
{
    /**
     * @var \Fatchip\Nexi\Helper\Country
     */
    protected $countryHelper;

    /**
     * Constructor
     *
     * @param \Fatchip\Nexi\Helper\Payment $paymentHelper
     * @param \Fatchip\Nexi\Helper\Api $apiHelper
     * @param \Fatchip\Nexi\Helper\Encryption $encryptionHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Fatchip\Nexi\Helper\Country $countryHelper
     */
    public function __construct(
        \Fatchip\Nexi\Helper\Payment $paymentHelper,
        \Fatchip\Nexi\Helper\Api $apiHelper,
        \Fatchip\Nexi\Helper\Encryption $encryptionHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fatchip\Nexi\Helper\Country $countryHelper
    ) {
        parent::__construct($paymentHelper, $apiHelper, $encryptionHelper, $curl, $apiLog, $checkoutSession);
        $this->countryHelper = $countryHelper;
    }

    /**
     * @param  BaseMethod $methodInstance
     * @param  double     $amount
     * @param  string     $currency
     * @param  string     $refNr
     * @param Order|null $order
     * @param  bool       $log
     * @param  bool       $encrypt
     * @return array
     */
    public function generateRequest(BaseMethod $methodInstance, $amount, $currency, $refNr, ?Order $order = null, $encrypt = false, $log = false)
    {
        if (!empty($order)) {
            $this->setStoreCode($order->getStore()->getCode(), false);
        }

        $this->addParameter('Currency', $currency);
        $this->addParameter('Amount', $this->apiHelper->formatAmount($amount, $currency));

        $this->addParameter('TransID', $this->getTransactionId($order));
        $this->addParameter('ReqId', $this->paymentHelper->getRequestId());
        $this->addParameter('EtiID', $this->apiHelper->getIdentString());

        $this->addParameter('RefNr', $this->apiHelper->getReferenceNumber($refNr));

        $this->addParameter('URLSuccess', $methodInstance->getSuccessUrl());
        $this->addParameter('URLFailure', $methodInstance->getFailureUrl());
        $this->addParameter('URLBack', $methodInstance->getCancelUrl());
        $this->addParameter('URLCancel', $methodInstance->getCancelUrl());
        $this->addParameter('URLNotify', $methodInstance->getNotifyUrl());
        $this->addParameter('Response', 'encrypt');

        $this->addParameter('orderDesc', $this->getParameter('TransID'));

        $this->addParameters($methodInstance->getPaymentSpecificParameters($order));

        $params = $this->getParameters();
        if ($log === true) {
            $this->apiLog->addApiLogEntry($methodInstance->getRequestType(), $params, null, $order);
        }

        if ($encrypt === true) {
            $params = $this->getEncryptedParameters($params);
            $params = array_merge($params, $methodInstance->getUnencryptedParameters($order));
        }
        return $params;
    }

    /**
     * @param  Order   $order
     * @param  Payment $payment
     * @param  double  $amount
     * @param  bool    $log
     * @param  bool    $encrypt
     * @return array
     */
    public function generateRequestFromOrder(Order $order, Payment $payment, $amount, $encrypt = false, $log = false)
    {
        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $amount = $order->getTotalDue(); // given amount is in base-currency - order currency is needed for transfer to computop
        $currency = $order->getOrderCurrencyCode();
        $refNr = $order->getIncrementId();

        $shippingAddress = $order->getBillingAddress();
        // getIsVirtual returns int and not bool!
        if (!$order->getIsVirtual() && !empty($order->getShippingAddress())) { // is not a digital/virtual order? -> add shipping address
            $shippingAddress = $order->getShippingAddress();
        }

        if ($methodInstance->isAddressDataNeeded() === true) {
            $this->addParameter('billingAddress', $this->getAddressInfo($order->getBillingAddress()));
            $this->addParameter('shippingAddress', $this->getAddressInfo($shippingAddress));
        }

        if ($methodInstance->isBillingAddressDataNeeded() === true) {
            $this->addParameters($this->getAddressParameters($order->getBillingAddress(), 'bd', $methodInstance));
        }

        if ($methodInstance->isShippingAddressDataNeeded() === true) {
            $this->addParameters($this->getAddressParameters($shippingAddress, 'sd', $methodInstance));
        }

        return $this->generateRequest($methodInstance, $amount, $currency, $refNr, $order, $encrypt, $log);
    }

    /**
     * @param  Quote      $quote
     * @param  BaseMethod $methodInstance
     * @param  bool       $encrypt
     * @param  bool       $log
     * @return array
     */
    public function generateRequestFromQuote(Quote $quote, BaseMethod $methodInstance, $encrypt = false, $log = false)
    {
        $amount = $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();
        $refNr = $methodInstance->getTemporaryRefNr($quote->getId());

        $shippingAddress = $quote->getBillingAddress();
        if (!$quote->getIsVirtual() && !empty($quote->getShippingAddress())) { // is not a digital/virtual order? -> add shipping address
            $shippingAddress = $quote->getShippingAddress();
        }

        if ($methodInstance->isBillingAddressDataNeeded() === true) {
            $this->addParameters($this->getAddressParameters($quote->getBillingAddress(), 'bd', $methodInstance));
        }

        if ($methodInstance->isShippingAddressDataNeeded() === true) {
            $this->addParameters($this->getAddressParameters($shippingAddress, 'sd', $methodInstance));
        }

        return $this->generateRequest($methodInstance, $amount, $currency, $refNr, null, $encrypt, $log);
    }

    /**
     * Split street in street name and street number
     *
     * @param  array $streetWithNr
     * @return array
     */
    protected function splitStreet($streetWithNr)
    {
        preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $streetWithNr, $matches);
        $street = $streetWithNr;
        $streetNr = "";
        if (is_array($matches) && count($matches) >= 2) {
            $street = $matches[1];
            $streetNr = $matches[2];
        }

        return [
            'street' => $street,
            'streetnr' => $streetNr,
        ];
    }

    /**
     * @param OrderAddress|QuoteAddress $address
     * @param string                    $prefix
     * @param BaseMethod|null           $methodInstance
     * @return array
     */
    protected function getAddressParameters($address, $prefix = '', ?BaseMethod $methodInstance = null)
    {
        $street = $address->getStreet();
        $street = is_array($street) ? implode(' ', $street) : $street; // street may be an array
        $split = $this->splitStreet(trim($street ?? ''));

        $params = [
            $prefix.'FirstName' => $address->getFirstname(),
            $prefix.'LastName' => $address->getLastname(),
            $prefix.'Zip' => $address->getPostcode(),
            $prefix.'City' => $address->getCity(),
            $prefix.'CountryCode' => $address->getCountryId(),
            $prefix.'Street' => $split['street'],
            $prefix.'StreetNr' => $split['streetnr'],
        ];

        if (!empty($address->getCompany())) {
            $params[$prefix.'CompanyName'] = $address->getCompany();
        }

        if ($methodInstance instanceof \Fatchip\Nexi\Model\Method\Ratepay\Base) {
            $params[$prefix.'ZIPCode'] = $params[$prefix.'Zip'];
            $params[$prefix.'StreetHouseNumber'] = $params[$prefix.'StreetNr'];
        }

        return $params;
    }

    /**
     * Returns address string (json and base64 encoded)
     *
     * @param  OrderAddress|QuoteAddress $address
     * @return string
     */
    protected function getAddressInfo($address)
    {
        $street = $address->getStreet();
        $street = is_array($street) ? implode(' ', $street) : $street; // street may be an array
        $address = [
            'city' => $address->getCity(),
            'country' => [
                'countryA3' => $this->countryHelper->getIso3Code($address->getCountryId()),
            ],
            'addressLine1' => [
                'street' => trim($street ?? ''),
                #'streetNumber' => '', // do we have to split the address in street and number?
            ],
            'postalCode' => $address->getPostcode(),
        ];
        return base64_encode(json_encode($address));
    }

    /**
     * @param  Order   $order
     * @param  Payment $payment
     * @param  double  $amount
     * @return array|null
     */
    public function sendCurlRequest(Order $order, Payment $payment, $amount)
    {
        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $params = $this->generateRequestFromOrder($order, $payment, $amount);
        $response = $this->handlePaymentCurlRequest($methodInstance, $params, $order);

        return $response;
    }
}
