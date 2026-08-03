<?php

namespace Fatchip\Nexi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepo;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepo;

class ReactivateQuote implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var QuoteRepo
     */
    protected $quoteRepository;

    /**
     * @var OrderRepo
     */
    protected $orderRepository;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param QuoteRepo $quoteRepository
     * @param OrderRepo $orderRepository
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        QuoteRepo $quoteRepository,
        OrderRepo $orderRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (empty($this->checkoutSession->getComputopCustomerIsRedirected())) {
            return; // No flag - nothing to do
        }

        $orderId = $this->checkoutSession->getLastOrderId();
        if (empty($orderId)) {
            $this->clearFlag();
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            $this->clearFlag();
            return;
        }

        $this->reactivateQuote($order);
        $this->clearFlag();
    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    protected function reactivateQuote(OrderInterface $order)
    {
        $quoteId = $order->getQuoteId();
        if (!$quoteId) {
            return;
        }

        try {
            $quote = $this->quoteRepository->get((int) $quoteId);
        } catch (\Exception $e) {
            return;
        }

        $quote->setIsActive(true);
        $quote->setReservedOrderId(null);

        $this->quoteRepository->save($quote);

        $this->checkoutSession->replaceQuote($quote);
    }

    /**
     * @return void
     */
    protected function clearFlag()
    {
        $this->checkoutSession->unsComputopCustomerIsRedirected();
    }
}
