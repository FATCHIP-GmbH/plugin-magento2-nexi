<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Helper\Api;
use Fatchip\Nexi\Helper\Payment;
use Fatchip\Nexi\Model\Api\Request\Capture;
use Fatchip\Nexi\Model\Api\Request\Credit;
use Fatchip\Nexi\Model\Api\Request\EasyCreditConfirm;
use Magento\Payment\Model\InfoInterface;
use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Fatchip\Nexi\Model\Api\Request\RefNrChange;
use Fatchip\Nexi\Model\Api\Request\GetEasyCreditInfo;

class EasyCredit extends RedirectNoOrder
{
    /**
     * @var GetEasyCreditInfo
     */
    protected $getEasyCreditInfo;

    /**
     * @var EasyCreditConfirm
     */
    protected $easyCreditConfirm;

    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_EASYCREDIT;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "easyCredit.aspx";

    /**
     * Determines if auth requests adds billing address parameters to the request
     *
     * @var bool
     */
    protected $addBillingAddressData = true;

    /**
     * Determines if auth requests adds shipping address parameters to the request
     *
     * @var bool
     */
    protected $addShippingAddressData = true;

    /**
     * Determines if payment method will receive Notify calls from Computop
     *
     * @var bool
     */
    protected $isNotifyPaymentType = false;

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
     * @param GetEasyCreditInfo $getEasyCreditInfo
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
        \Magento\Checkout\Model\Session $checkoutSession,
        Payment                  $paymentHelper,
        Api                      $apiHelper,
        Capture                  $captureRequest,
        Credit                   $creditRequest,
        InvoiceService           $invoiceService,
        OrderSender              $orderSender,
        InvoiceSender            $invoiceSender,
        RefNrChange              $refNrChange,
        GetEasyCreditInfo        $getEasyCreditInfo,
        EasyCreditConfirm        $easyCreditConfirm,
        ?CommandPoolInterface    $commandPool = null,
        ?ValidatorPoolInterface  $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface         $logger = null
    ) {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $urlBuilder, $authRequest, $checkoutSession, $paymentHelper, $apiHelper, $captureRequest, $creditRequest, $invoiceService, $orderSender, $invoiceSender, $refNrChange, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->getEasyCreditInfo = $getEasyCreditInfo;
        $this->easyCreditConfirm = $easyCreditConfirm;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $dataSource = $order;
        if ($order === null) {
            $dataSource = $this->checkoutSession->getQuote();
        }

        $infoInstance = $this->getInfoInstance();

        return [
            #'Capture' => $this->getPaymentConfigParam('capture_method'),
            'EventToken' => 'INT',
            'version' => 'v3',
            'Email' => $dataSource->getBillingAddress()->getEmail(),
            'salutation' => 'Mr', // salutation is not gathered in Magento, therefor always send 'Mr'
            'FirstName' => $dataSource->getBillingAddress()->getFirstname(),
            'LastName' => $dataSource->getBillingAddress()->getLastname(),
            'DateOfBirth' => $this->getBirthday($this->getInfoInstance(), $order),
            #'orderDesc' => 'Demoshop',
            #'IPAddress' => '178.19.213.38',
            #'language' => 'de',
        ];
    }

    /**
     * @param InfoInterface $infoInstance
     * @param Order         $order
     * @return false
     */
    protected function getBirthday(InfoInterface $infoInstance, ?Order $order = null)
    {
        $dateOfBirth = $this->checkoutSession->getComputopEasyCreditDob();
        if (!empty($dateOfBirth)) {
            return $dateOfBirth;
        }
        return parent::getBirthday($infoInstance, $order);
    }

    /**
     * URL where payment/order is finished
     *
     * @return string
     * @throws \Exception
     */
    public function getFinishUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/easyCreditReview');
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $parentReturn = parent::authorize($payment, $amount);

        $order = $payment->getOrder();

        $infoResponse = $this->checkoutSession->getComputopEasyCreditInfo();

        // Throws exception when status is not successful, so no check for that needed here
        $confirmResponse = $this->easyCreditConfirm->sendRequest($payment, $infoResponse);
        $this->checkoutSession->setComputopEasyCreditConfirmResponse($confirmResponse);

        if (!empty($confirmResponse['PayID'])) {
            $this->refNrChange->changeRefNr($confirmResponse['PayID'], $this->apiHelper->getReferenceNumber($order->getIncrementId()));
        }

        if (!empty($confirmResponse['TransID'])) {
            $this->setTransactionId($payment, $confirmResponse['TransID']);
        }

        $this->finalizeOrder($payment, $confirmResponse);

        return $parentReturn;
    }

    /**
     * @return string|false
     */
    public function postReviewPlaceOrder()
    {
        $response = $this->checkoutSession->getComputopEasyCreditConfirmResponse();
        if ($this->apiHelper->isSuccessStatus($response)) {
            return 'checkout/onepage/success';
        }
        return false;
    }

    public function getAuthRequestFromQuote()
    {
        $return = parent::getAuthRequestFromQuote();
        $this->checkoutSession->unsComputopEasyCreditInfo();
        $this->checkoutSession->unsComputopNoOrderRedirectResponse();
        $this->checkoutSession->unsComputopEasyCreditConfirmResponse();
        return $return;
    }

    /**
     * @return string
     */
    public function getReviewPath()
    {
        return ComputopConfig::ROUTE_NAME.'/onepage/easyCreditReview';
    }
}
