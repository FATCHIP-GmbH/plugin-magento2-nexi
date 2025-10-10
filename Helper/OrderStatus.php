<?php

namespace Fatchip\Nexi\Helper;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class OrderStatus extends Base
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
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param InvoiceService                             $invoiceService
     * @param InvoiceSender                              $invoiceSender
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * @param $order
     * @return void
     */
    public function createInvoice($order)
    {
        if ($order->getInvoiceCollection()->count() == 0) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
            $invoice->setTransactionId($order->getPayment()->getLastTransId());
            $invoice->register();
            $invoice->save();

            $order->save();

            if ($this->getConfigParam('send_invoice_email', 'emails')) {
                $this->invoiceSender->send($invoice);
            }
        }
    }
}
