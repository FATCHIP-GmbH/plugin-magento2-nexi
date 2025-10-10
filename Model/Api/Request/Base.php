<?php

namespace Fatchip\Nexi\Model\Api\Request;

use Fatchip\Nexi\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;

class Base
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Store code for the current context
     *
     * @var string
     */
    protected $storeCode = null;

    /**
     * Computop payment helper
     *
     * @var \Fatchip\Nexi\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Computop API helper
     *
     * @var \Fatchip\Nexi\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Fatchip\Nexi\Helper\Encryption
     */
    protected $encryptionHelper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Fatchip\Nexi\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * Checkout session model
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType;

    /**
     * URL to Computop API
     *
     * @var string
     */
    protected $apiBaseUrl = "https://www.computop-paygate.com/";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var bool
     */
    protected $logRequest = true;

    /**
     * Constructor
     *
     * @param \Fatchip\Nexi\Helper\Payment $paymentHelper
     * @param \Fatchip\Nexi\Helper\Api $apiHelper
     * @param \Fatchip\Nexi\Helper\Encryption $encryptionHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Fatchip\Nexi\Helper\Payment $paymentHelper,
        \Fatchip\Nexi\Helper\Api $apiHelper,
        \Fatchip\Nexi\Helper\Encryption $encryptionHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->apiHelper = $apiHelper;
        $this->encryptionHelper = $encryptionHelper;
        $this->curl = $curl;
        $this->apiLog = $apiLog;
        $this->checkoutSession = $checkoutSession;
        $this->initRequest();
    }

    /**
     * Initialize request
     * Set all default parameters
     *
     * @param bool $clearParams
     * @return void
     */
    protected function initRequest($clearParams = true)
    {
        if ($clearParams === true) {
            $this->parameters = []; // clear parameters
        }
        $this->addParameter('MerchantID', $this->paymentHelper->getConfigParam('merchantid', 'global', 'computop_general', $this->storeCode));
    }

    /**
     * Returns transaction id for this request
     *
     * @param Order|null $order
     * @return string
     */
    public function getTransactionId(?Order $order = null)
    {
        if (empty($this->transactionId)) {
            if (!empty($order)) {
                $this->transactionId = $order->getIncrementId();
            } else {
                $this->transactionId = $this->paymentHelper->getTransactionId();
            }
        }
        return $this->transactionId;
    }

    /**
     * Set transaction id for later use with the request
     *
     * @param  string $transactionId
     * @return void
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * Returns all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        $return = $this->parameters;
        $return['MAC'] = $this->getHmac();
        return $return;
    }

    /**
     * Returns API helper object
     *
     * @return \Fatchip\Nexi\Helper\Api
     */
    public function getApiHelper()
    {
        return $this->apiHelper;
    }

    /**
     * Returns a certain parameter if set
     *
     * @param  string $paramName
     * @param  mixed $defaultEmptyReturn
     * @return string|mixed
     */
    public function getParameter($paramName, $defaultEmptyReturn = null)
    {
        if (isset($this->parameters[$paramName])) {
            return $this->parameters[$paramName];
        }
        return $defaultEmptyReturn;
    }

    /**
     * Adds parameter to parameters array
     *
     * @param  string $paramName
     * @param  string $paramValue
     * @return void
     */
    public function addParameter($paramName, $paramValue)
    {
        $this->parameters[$paramName] = $paramValue;
    }

    /**
     * Adds multiple parameters to parameters array
     *
     * @param  array $parameters
     * @return void
     */
    public function addParameters($parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Removes certain parameter from parameters array
     *
     * @param  string $paramName
     * @return void
     */
    public function removeParameter($paramName)
    {
        if (isset($this->parameters[$paramName])) {
            unset($this->parameters[$paramName]);
        }
    }

    /**
     * Returns the API endpoint
     *
     * @param  bool $apiEndpoint
     * @return string
     */
    public function getFullApiEndpoint($apiEndpoint = false)
    {
        if ($apiEndpoint === false) {
            $apiEndpoint = $this->apiEndpoint;
        }
        return rtrim($this->apiBaseUrl, "/") . "/" .$apiEndpoint;
    }

    /**
     * Set current store code and reinit base parameters
     *
     * @param  string $storeCode
     * @param  biil   $clearParams
     * @return void
     */
    public function setStoreCode($storeCode, $clearParams = true)
    {
        if ($this->storeCode != $storeCode) {
            $this->storeCode = $storeCode;
            $this->initRequest($clearParams); //reinit base parameters
        }
    }

    /**
     * Returns request type for API log
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Generates Hmac string and returns it
     *
     * @return string
     */
    protected function getHmac()
    {
        $hashParts = [
            $this->getParameter("PayID", ""), // may be empty, but that's ok - i.e. for Authorization
            $this->getParameter("TransID", ""),
            $this->getParameter("MerchantID", ""),
            $this->getParameter("Amount", ""),
            $this->getParameter("Currency", ""),
        ];
        $hashString = implode("*", $hashParts);
        $secret = $this->paymentHelper->getConfigParam('mac', 'global', 'computop_general', $this->storeCode);

        return hash_hmac('sha256', $hashString, $secret);
    }

    /**
     * Returns parameters in encrypted format
     *
     * @param  array $params
     * @return array
     */
    public function getEncryptedParameters($params = null)
    {
        if ($params === null) {
            $params = $this->getParameters();
        }

        $dataQuery = urldecode(http_build_query($params));
        $length = mb_strlen($dataQuery);

        return [
            'MerchantID' => $this->getParameter('MerchantID'),
            'Len' => $length,
            'Data' => $this->encryptionHelper->encrypt($dataQuery, $length, $this->paymentHelper->getConfigParam('password', 'global', 'computop_general', $this->storeCode))
        ];
    }

    /**
     * Send request to given url and decode given response
     *
     * @param  string      $url
     * @param  string      $requestType
     * @param  array|null  $params
     * @param Order|null $order
     * @return array|null
     */
    protected function handleCurlRequest($url, $requestType, $params, ?Order $order = null)
    {
        $response = null;

        try {
            $this->curl->post($url, $this->getEncryptedParameters($params));

            $responseBody = $this->curl->getBody();
        } catch (\Exception $exc) {
            throw $exc;
        }

        if (!empty($responseBody)) {
            parse_str($responseBody, $parsedResponse);
            if (isset($parsedResponse['Data']) && isset($parsedResponse['Len'])) {
                $response = $this->encryptionHelper->decrypt($parsedResponse['Data'], $parsedResponse['Len'], $this->paymentHelper->getConfigParam('password', 'global', 'computop_general', $this->storeCode));;
            } elseif (isset($parsedResponse['mid'])) { // not encrypted? this is the case with PPE paypalComplete call
                $response = $parsedResponse;
            }
        }

        if ($this->logRequest === true) {
            $this->handleLogging($requestType, $params, $response, $order);
        }

        return $response;
    }

    /**
     * @param string     $requestType
     * @param array      $request
     * @param array      $response
     * @param Order|null $order
     * @return void
     */
    protected function handleLogging($requestType, $request, $response = null, ?Order $order = null)
    {
        $this->checkoutSession->setComputopApiLogData(['type' => $requestType, 'request' => $request, 'response' => $response]);
        $this->apiLog->addApiLogEntry($requestType, $request, $response, $order);
    }

    /**
     * Sends a standard curl request to Computop API
     * Endpoint and request type are taken from the properties of the request class
     *
     * @param  array|null $params
     * @param Order|null $order
     * @param  bool $apiEndpoint
     * @return array|null
     */
    protected function handleStandardCurlRequest($params, ?Order $order = null, $apiEndpoint = false)
    {
        return $this->handleCurlRequest($this->getFullApiEndpoint($apiEndpoint), $this->getRequestType(), $params, $order);
    }

    /**
     * Sends a payment curl request to Computop API
     * Endpoint and request type are taken from method instance object
     *
     * @param  BaseMethod $methodInstance
     * @param  array $params
     * @param Order|null $order
     * @return array|null
     */
    public function handlePaymentCurlRequest(BaseMethod $methodInstance, $params, ?Order $order = null)
    {
        return $this->handleCurlRequest($this->getFullApiEndpoint($methodInstance->getApiEndpoint()), $methodInstance->getRequestType(), $params, $order);
    }
}
