<?php

namespace Fatchip\Nexi\Model\Method\Ratepay;

use Fatchip\Nexi\Helper\Api;
use Fatchip\Nexi\Helper\Environment;
use Fatchip\Nexi\Helper\Payment;
use Fatchip\Nexi\Model\Api\Request\Capture;
use Fatchip\Nexi\Model\Api\Request\Credit;
use Fatchip\Nexi\Model\Api\Request\RefNrChange;
use Fatchip\Nexi\Model\Method\ServerToServerPayment;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\AbstractModel;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;

abstract class Base extends ServerToServerPayment
{
    const METHOD_DIRECT_DEBIT = 'PAY_NOW';

    const METHOD_INVOICE = 'OPEN_INVOICE';

    const DEBIT_PAY_TYPE_DIRECT_DEBIT = 'SEPA_DIRECT_DEBIT';

    const DEBIT_PAY_TYPE_BANK_TRANSFER = 'BANK_TRANSFER';

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
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "ratepay.aspx";

    /**
     * @var string
     */
    protected $requestType = "RATEPAY";

    /**
     * @var string|null
     */
    protected $rpMethod = null;

    /**
     * @var string|null
     */
    protected $rpDebitPayType = null;

    /**
     * @var Environment
     */
    protected $environmentHelper;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'dateofbirth',
        'customer_dateofbirth',
        'telephone',
    ];

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
     * @param \Fatchip\Nexi\Helper\Api $apiHelper
     * @param Environment $environmentHelper
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
        Payment $paymentHelper,
        Api $apiHelper,
        Capture $captureRequest,
        Credit $creditRequest,
        InvoiceService $invoiceService,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        RefNrChange $refNrChange,
        Environment $environmentHelper,
        ?CommandPoolInterface $commandPool = null,
        ?ValidatorPoolInterface $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $urlBuilder, $authRequest, $checkoutSession, $paymentHelper, $apiHelper, $captureRequest, $creditRequest, $invoiceService, $orderSender, $invoiceSender, $refNrChange, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->environmentHelper = $environmentHelper;
    }

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        // Ratepay has no capture mode, there orders always have to be captured so it is always MANUAL
        return CaptureMethods::CAPTURE_MANUAL;
    }

    /**
     * @param string $artNr
     * @param string $name
     * @param float $price
     * @param string $currency
     * @param float $qty
     * @param float $taxRate
     * @return array
     */
    protected function getBasketItem($artNr, $name, $price, $currency, $qty, $taxRate)
    {
        return [
            'artNr' => $artNr,
            'name' => $name,
            'unitPriceGross' => $this->apiHelper->formatAmount($price, $currency),
            'quantity' => $qty,
            'taxRate' => $taxRate,
        ];
    }

    /**
     * Get basket items array for auth request
     *
     * @param AbstractModel $orderOrInvoice
     * @return array
     */
    protected function getBasketItems(AbstractModel $orderOrInvoice)
    {
        $items = [];

        $currency = $orderOrInvoice->getOrderCurrencyCode();

        $taxRate = 0;

        $orderItems = $orderOrInvoice->getItems();
        foreach ($orderItems as $orderItem) {
            if ($this->isItemADummy($orderItem) === false) {
                $itemTaxRate = $this->getItemTaxPercent($orderItem);
                if ($itemTaxRate > $taxRate) {
                    $taxRate = $itemTaxRate;
                }
                $items[] = $this->getBasketItem(
                    $orderItem->getSku(),
                    $orderItem->getName(),
                    $orderItem->getPriceInclTax(),
                    $currency,
                    $this->getItemQty($orderItem),
                    $itemTaxRate
                );
            }
        }

        if (!empty($orderOrInvoice->getDiscountAmount())) {
            $name = (string)__('Discount');
            if ($orderOrInvoice->getCouponCode()) {
                $name = (string)__('Coupon').' - '.$orderOrInvoice->getCouponCode();
            }

            $items[] = $this->getBasketItem(
              'discount',
                $name,
                $orderOrInvoice->getDiscountAmount(),
                $currency,
                1,
                $taxRate
            );
        }

        if (!empty($orderOrInvoice->getShippingInclTax())) {
            $items[] = $this->getBasketItem(
                'shipping',
                'Shipping',
                $orderOrInvoice->getShippingInclTax(),
                $currency,
                1,
                $taxRate
            );
        }

        return $items;
    }

    /**
     * @param AbstractModel $item
     * @return bool
     */
    protected function isItemADummy($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Invoice\Item) {
            return $item->getOrderItem()->isDummy();
        }
        return $item->isDummy();
    }

    /**
     * @param AbstractModel $item
     * @return float
     */
    protected function getItemQty($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Invoice\Item) {
            return $item->getQty();
        }
        return $item->getQtyOrdered();
    }

    /**
     * @param AbstractModel $item
     * @return float
     */
    protected function getItemTaxPercent($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Invoice\Item) {
            return $item->getOrderItem()->getTaxPercent();
        }
        return $item->getTaxPercent();
    }

    /**
     * Get shopping basket array for auth request
     *
     * @param AbstractModel $orderOrInvoice
     * @return array
     */
    protected function getShoppingBasket(AbstractModel $orderOrInvoice)
    {
        $items = $this->getBasketItems($orderOrInvoice);

        $taxAmount = $orderOrInvoice->getTaxAmount();
        $netAmount = $orderOrInvoice->getGrandTotal() - $taxAmount;

        $taxRate = false;
        foreach ($items as $item) {
            if ($taxRate === false || $item['taxRate'] > $taxRate) {
                $taxRate = $item['taxRate'];
            }
        }

        $currency = $orderOrInvoice->getOrderCurrencyCode();

        $shoppingBasket = [
            [ // Nested array is strange here, but it doesn't work without it
                'shoppingBasketAmount' => $this->apiHelper->formatAmount($orderOrInvoice->getGrandTotal(), $currency),
                'items' => $items,
                'vats' => [
                    [ // Nested array is strange here, but it doesn't work without it
                        'netAmount' => $this->apiHelper->formatAmount($netAmount, $currency),
                        'taxAmount' => $this->apiHelper->formatAmount($taxAmount, $currency),
                        'taxRate' => $taxRate,
                    ]
                ],
            ]
        ];
        return $shoppingBasket;
    }

    /**
     * Return parameters specific to this payment subtype
     *
     * @param  Order $order
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $order)
    {
        return []; // Can be filled in child classes
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $baseParams = [
            'RPMethod' => $this->rpMethod,
            'DebitPayType' => $this->rpDebitPayType,
            'Email' => $order->getBillingAddress()->getEmail(),
            'Phone' => $this->getTelephoneNumber($order, $this->getInfoInstance()),
            'shoppingBasket' => $this->apiHelper->encodeArray($this->getShoppingBasket($order)),
            'IPAddr' => $this->environmentHelper->getRemoteIp(),
            'Language' => $this->environmentHelper->getLocale(),
        ];

        $birthday = $this->getBirthday($this->getInfoInstance(), $order);
        if (!empty($birthday)) {
            $baseParams['DateOfBirth'] = $birthday;
        }

        $subTypeParams = $this->getSubTypeSpecificParameters($order);
        $params = array_merge($baseParams, $subTypeParams);

        return $params;
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
        $params = [];

        $invoice = $payment->getOrder()->getInvoiceCollection()->getLastItem();
        if ($invoice->getIsUsedForRefund() !== true) {
            /*
             * Inactive for now
            $shoppingBasket = $this->getShoppingBasket($invoice);
            #$params['shoppingBasket'] = $this->apiHelper->encodeArray($shoppingBasket);
            $params['items'] = $this->apiHelper->encodeArray($shoppingBasket[0]['items']);
            $params['vats'] = $this->apiHelper->encodeArray($shoppingBasket[0]['vats']);
            */
        }
        return $params;
    }
}
