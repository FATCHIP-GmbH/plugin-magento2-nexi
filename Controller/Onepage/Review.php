<?php

namespace Fatchip\Nexi\Controller\Onepage;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Framework\View\Result\Page;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect as CoreRedirect;

/**
 * Controller for order review
 */
class Review extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Page result factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * List of all payment methods available for this review step
     *
     * @var array
     */
    protected $availableReviewMethods = [
        ComputopConfig::METHOD_PAYPAL
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context        $context
     * @param \Magento\Checkout\Model\Session              $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory   $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->pageFactory = $pageFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Render order review
     * Redirect to basket if quote or payment is missing
     *
     * @return null|Page|CoreRedirect
     */
    public function execute()
    {
        if ($this->canReviewBeShown() === false) {
            /** @var CoreRedirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout');
        }

        $pageObject = $this->pageFactory->create();

        $selectedShippingMethod = $this->getRequest()->getParam('shipping_method');
        if ($selectedShippingMethod) {
            $this->updateShippingMethod($selectedShippingMethod);
        }

        return $pageObject;
    }

    /**
     * Validates if the review step can be shown by checking some status flags
     *
     * @return bool
     */
    protected function canReviewBeShown()
    {
        if (in_array($this->checkoutSession->getQuote()->getPayment()->getMethod(), $this->availableReviewMethods)) {
            return true;
        }
        return false;
    }

    /**
     * Update shipping method
     *
     * @param  string $shippingMethod
     * @return void
     */
    protected function updateShippingMethod($shippingMethod)
    {
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        if (!$quote->getIsVirtual() && $shippingAddress) {
            if ($shippingMethod != $shippingAddress->getShippingMethod()) {
                $this->ignoreAddressValidation($quote);
                $shippingAddress->setShippingMethod($shippingMethod)->setCollectShippingRates(true);
                $cartExtension = $quote->getExtensionAttributes();
                if ($cartExtension && $cartExtension->getShippingAssignments()) {
                    $cartExtension->getShippingAssignments()[0]->getShipping()->setMethod($shippingMethod);
                }
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
            }
        }
    }

    /**
     * Disable validation to make sure addresses will always be saved
     *
     * @param  Quote $quote
     * @return void
     */
    protected function ignoreAddressValidation(Quote $quote)
    {
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }
    }
}
