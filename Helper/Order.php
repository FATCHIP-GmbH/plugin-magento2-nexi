<?php

namespace Fatchip\Nexi\Helper;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Method\PayPal;
use Fatchip\Nexi\Model\Method\ServerToServerPayment;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order as CoreOrder;

class Order extends Base
{
    /**
     * InvoiceService object
     *
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * InvoiceSender object
     *
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param InvoiceService                             $invoiceService
     * @param InvoiceSender                              $invoiceSender
     * @param \Magento\Sales\Model\OrderFactory          $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param string $incrementId
     * @return CoreOrder
     */
    public function getOrderByIncrementId($incrementId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if ($order && $order->getId()) {
            return $order;
        }
        return null;
    }

    /**
     * @param $order
     * @return void
     */
    public function createInvoice($order, $responseTransId = null)
    {
        $methodInstance = $order->getPayment()->getMethodInstance();
        if ($methodInstance->getCaptureMode() == CaptureMethods::CAPTURE_AUTO) {
            if ($order->getInvoiceCollection()->count() == 0) {
                $transId = $order->getPayment()->getLastTransId();
                if ($methodInstance instanceof ServerToServerPayment || ($methodInstance instanceof PayPal && $methodInstance->isExpressAuthStep())) {
                    $transId = $responseTransId;
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
}
