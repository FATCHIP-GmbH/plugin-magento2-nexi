<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Helper\Api;
use Fatchip\Nexi\Helper\Payment;
use Fatchip\Nexi\Model\Api\Request\Capture;
use Fatchip\Nexi\Model\Api\Request\Credit;
use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Fatchip\Nexi\Model\Api\Request\RefNrChange;

abstract class BaseMethod extends Adapter
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * Url builder object
     *
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * @var \Fatchip\Nexi\Model\Api\Request\Authorization
     */
    protected $authRequest;

    /**
     * @var string
     */
    protected $requestType = "";

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Capture
     */
    protected $captureRequest;

    /**
     * @var Credit
     */
    protected $creditRequest;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * OrderSender object
     *
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var LoggerInterface
     */
    protected $loggerObject;

    /**
     * InvoiceSender object
     *
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var RefNrChange
     */
    protected $refNrChange;

    /**
     * Defines if transaction id is set pre or post authorization
     * True = pre auth
     * False = post auth with response
     *
     * @var bool
     */
    protected $setTransactionPreAuthorization = true;

    /**
     * Determines if initialize payment step shall be used instead of direct authorization
     *
     * @var bool
     */
    protected $useInitializeStep = false;

    /**
     * Determines if payment method will receive Notify calls from Computop
     *
     * @var bool
     */
    protected $isNotifyPaymentType = true;

    /**
     * Determines if auth requests adds address parameters to the request
     *
     * @var bool
     */
    protected $sendAddressData = false;

    /**
     * Determines if auth requests adds billing address parameters to the request
     *
     * @var bool
     */
    protected $addBillingAddressData = false;

    /**
     * Determines if auth requests adds shipping address parameters to the request
     *
     * @var bool
     */
    protected $addShippingAddressData = false;

    /**
     * @var bool
     */
    protected $addLanguageToUrl = false;

    /**
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Fatchip\Nexi\Model\Api\Request\Authorization $authRequest
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Payment $paymentHelper
     * @param Api $apiHelper
     * @param Capture $captureRequest
     * @param Credit $creditRequest
     * @param InvoiceService $invoiceService
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param RefNrChange $refNrChange
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        \Magento\Framework\Url $urlBuilder,
        \Fatchip\Nexi\Model\Api\Request\Authorization $authRequest,
        \Magento\Checkout\Model\Session                   $checkoutSession,
        Payment                                           $paymentHelper,
        Api                                               $apiHelper,
        Capture                                           $captureRequest,
        Credit                                            $creditRequest,
        InvoiceService                                    $invoiceService,
        OrderSender                                       $orderSender,
        InvoiceSender                                     $invoiceSender,
        RefNrChange                                       $refNrChange,
        ?CommandPoolInterface                             $commandPool = null,
        ?ValidatorPoolInterface                           $validatorPool = null,
        ?CommandManagerInterface                          $commandExecutor = null,
        ?LoggerInterface                                  $logger = null
    ) {
        if (empty($this->methodCode)) {
            throw new \Exception("MethodCode is empty!");
        }
        $code = $this->methodCode;

        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->urlBuilder = $urlBuilder;
        $this->authRequest = $authRequest;
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->apiHelper = $apiHelper;
        $this->captureRequest = $captureRequest;
        $this->creditRequest = $creditRequest;
        $this->invoiceService = $invoiceService;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->loggerObject = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->refNrChange = $refNrChange;
    }

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }

    /**
     * Returns request type
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Returns if address parameters have to be added in auth request
     *
     * @return bool
     */
    public function isAddressDataNeeded()
    {
        return $this->sendAddressData;
    }

    /**
     * Returns if address parameters have to be added in auth request
     *
     * @return bool
     */
    public function isBillingAddressDataNeeded()
    {
        return $this->addBillingAddressData;
    }

    /**
     * Returns if address parameters have to be added in auth request
     *
     * @return bool
     */
    public function isShippingAddressDataNeeded()
    {
        return $this->addShippingAddressData;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        return []; // filled in child classes
    }

    /**
     * Return parameters specific to this payment type that have to be added to the unencrypted URL
     *
     * @param Order|null $order
     * @return array
     */
    public function getUnencryptedParameters(?Order $order = null)
    {
        $params = [];
        if ($this->addLanguageToUrl === true) {
            $params['language'] = strtolower($this->apiHelper->getStoreLocale());
        }
        return $params;
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
     * Returns redirect url for failure case
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/failure');
    }

    /**
     * Returns redirect url for cancel case
     *
     * @return string|null
     */
    public function getCancelUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/cancel');
    }

    /**
     * Returns URL for notify controller
     *
     * @return string|null
     */
    public function getNotifyUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/notify');
    }

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        return $this->getPaymentConfigParam('capture_method');
    }

    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        if (!empty($this->assignKeys)) {
            $infoInstance = $this->getInfoInstance();
            $additionalData = $data->getAdditionalData();
            foreach ($this->assignKeys as $key) {
                if (!empty($additionalData[$key])) {
                    $infoInstance->setAdditionalInformation($key, $additionalData[$key]);
                }
            }
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param               $response
     * @return void
     */
    protected function handleResponseSpecific(InfoInterface $payment, $response)
    {
        // hook for extention by child methods
    }

    /**
     * @param Order $order
     * @param array $notify
     * @return void
     */
    public function handleNotifySpecific(Order $order, $notify)
    {
        // hook for extention by child methods
    }

    /**
     * @param  array $response
     * @return void
     */
    protected function checkResponseForSuccess($response)
    {
        if ($this->authRequest->getApiHelper()->isSuccessStatus($response) === false) {
            throw new LocalizedException(__($response['Description'] ?? 'Error'));
        }
    }

    /**
     * @param  InfoInterface $payment
     * @param  array         $response
     * @param  bool          $finalizeOrder
     * @return void
     */
    public function handleResponse(InfoInterface $payment, $response, $finalizeOrder = true)
    {
        $this->checkResponseForSuccess($response);

        if ($this instanceof ServerToServerPayment || $this->hasTransactionToBeSetPreAuthorization() === false || ($this instanceof PayPal && $this->isExpressAuthStep())) { // false = set POST auth
            $save = true;
            if ($this instanceof ServerToServerPayment || ($this instanceof PayPal && $this->isExpressAuthStep())) {
                $save = false;
            }
            $this->setTransactionId($payment, $response['TransID'], $save);
        }

        if ($finalizeOrder === true && !$this instanceof RedirectNoOrder) { // RedirectNoOrder methods did not create an order yet
            $this->finalizeOrder($payment, $response);
        }
    }

    /**
     * @param  InfoInterface $payment
     * @param  array         $response
     * @return void
     */
    protected function finalizeOrder(InfoInterface $payment, $response)
    {
        $order = $payment->getOrder();
        $order->setComputopPayid($response['PayID']);
        $order->save();

        $this->handleResponseSpecific($payment, $response);

        if (!$order->getEmailSent()) { // the email should not have been sent at this given moment, but some custom modules may have changed this behaviour
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->loggerObject->critical($e);
            }
        }

        if ($this->isNotifyPaymentMethod() === false && $this->getCaptureMode() == CaptureMethods::CAPTURE_AUTO && in_array($response['Status'], [ComputopConfig::STATUS_AUTHORIZED, ComputopConfig::STATUS_OK])) {
            if ($order->getInvoiceCollection()->count() == 0) {
                $transId = $order->getPayment()->getLastTransId();
                if ($this instanceof ServerToServerPayment || ($this instanceof PayPal && $this->isExpressAuthStep())) {
                    $transId = $response['TransID'];
                }

                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
                $invoice->setTransactionId($transId);
                $invoice->register();
                $invoice->pay();
                $invoice->save();

                $order->save();

                $this->invoiceSender->send($invoice);
            }
        }
    }

    /**
     * @param  string $quoteId
     * @return string
     */
    public function getTemporaryRefNr($quoteId)
    {
        return ComputopConfig::QUOTE_REFNR_PREFIX.$quoteId.date('his');
    }

    /**
     * @param  InfoInterface $payment
     * @param  string $transactionId
     * @param  bool $save
     * @return void
     */
    public function setTransactionId(InfoInterface $payment, $transactionId, $save = false)
    {
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(0);
        if ($save === true) {
            $payment->save();
        }
    }

    /**
     * Trying to retrieve current storecode from various sources
     *
     * @return string|null
     */
    protected function getStoreCode()
    {
        try {
            $infoInstance = $this->getInfoInstance();
            if (empty($infoInstance)) {
                return null;
            }
        } catch (\Exception $exc) {
            return null;
        }

        $order = $infoInstance->getOrder();
        if (empty($order)) {
            $order = $infoInstance->getQuote();
            if (empty($order)) {
                return null;
            }
        }

        $store = $order->getStore();
        if (empty($store)) {
            return null;
        }
        return $store->getCode();
    }

    /**
     * Returns a config param for this payment type
     *
     * @param  string $param
     * @param  string $storeCode
     * @return string
     */
    public function getPaymentConfigParam($param, $storeCode = null)
    {
        if ($storeCode === null) {
            $storeCode = $this->getStoreCode();
        }
        return $this->paymentHelper->getConfigParam($param, $this->getCode(), 'computop_payment', $storeCode);
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [];
    }

    /**
     * Capture payment abstract method
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        #$parentReturn = parent::capture($payment, $amount);
        $this->captureRequest->sendRequest($payment, $amount);
        return $this;
    }

    /**
     * Return invoice parameters specific to this payment type
     *
     * @param InfoInterface $payment
     * @param float         $amount
     * @return array
     */
    public function getPaymentSpecificInvoiceParameters(InfoInterface $payment, $amount)
    {
        return []; // filled in child classes
    }

    /**
     * Refund specified amount for payment
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        #$parentReturn = parent::refund($payment, $amount);
        $this->creditRequest->sendRequest($payment, $amount);
        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param InfoInterface $payment
     * @return $this
     */
    public function cancel(InfoInterface $payment)
    {
        #$parentReturn = parent::cancel($payment);
        // DO NOTHING FOR NOW

        return $this;
    }

    /**
     * @param $quote
     * @return void
     */
    public function preReviewPlaceOrder($quote)
    {
        // Method can be overwritten by inheriting classes
    }

    /**
     * @return string
     */
    public function postReviewPlaceOrder()
    {
        // Method can be overwritten by inheriting classes
        return '';
    }

    /**
     * @return bool
     */
    public function hasTransactionToBeSetPreAuthorization()
    {
        return $this->setTransactionPreAuthorization;
    }

    /**
     * @inheritdoc
     */
    public function isInitializeNeeded()
    {
        return $this->useInitializeStep;
    }

    /**
     * Returns if payment method
     *
     * @return bool
     */
    public function isNotifyPaymentMethod()
    {
        return $this->isNotifyPaymentType;
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function getCurrentCurrency(Order $order)
    {
        $currency = '';
        if ($order instanceof Order) {
            $currency = $order->getOrderCurrencyCode();
        } elseif ($order instanceof Quote && method_exists($order, 'getQuoteCurrencyCode')) {
            $currency = $order->getQuoteCurrencyCode();
        }
        return $currency;
    }

    /**
     * @param InfoInterface $payment
     * @param $sTransId
     * @return void
     */
    public function authorizePayment(InfoInterface $payment, $sTransId)
    {
        $order = $payment->getOrder();
        #$totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        $this->setTransactionId($payment, $sTransId);

        $payment->authorize(true, $baseTotalDue);
    }

    /**
     * @param Order         $order
     * @param InfoInterface $infoInstance
     * @return false
     */
    protected function getTelephoneNumber(Order $order, InfoInterface $infoInstance)
    {
        if ($order && !empty($order->getBillingAddress()) && !empty($order->getBillingAddress()->getTelephone())) {
            return $order->getBillingAddress()->getTelephone();
        }

        if ($order && !empty($order->getShippingAddress()) && !empty($order->getShippingAddress()->getTelephone())) {
            return $order->getShippingAddress()->getTelephone();
        }

        if (!empty($infoInstance->getAdditionalInformation('telephone'))) {
            return $infoInstance->getAdditionalInformation('telephone');
        }
        return false;
    }

    /**
     * @param InfoInterface $infoInstance
     * @param Order         $order
     * @return false
     */
    protected function getBirthday(InfoInterface $infoInstance, ?Order $order = null)
    {
        $dobTime = false;
        if ($order && !empty($order->getCustomerDob())) {
            $dobTime = strtotime($order->getCustomerDob());
        }

        if (!empty($infoInstance->getAdditionalInformation('customer_dateofbirth'))) {
            $dobTime = strtotime($infoInstance->getAdditionalInformation('customer_dateofbirth'));
        }

        if ($dobTime !== false) {
            return date('Y-m-d', $dobTime);
        }

        if (!empty($infoInstance->getAdditionalInformation('dateofbirth'))) {
            return $infoInstance->getAdditionalInformation('dateofbirth');
        }
        return false;
    }

    /**
     * @return string
     */
    public function getReviewPath()
    {
        // Default
        return '*/*/review';
    }
}
