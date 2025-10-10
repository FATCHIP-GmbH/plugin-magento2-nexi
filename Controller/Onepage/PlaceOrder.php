<?php

namespace Fatchip\Nexi\Controller\Onepage;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Controller for creating the PaypalExpress orders
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var \Fatchip\Nexi\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Fatchip\Nexi\Helper\Api
     */
    protected $apiHelper;

    /**
     * Order repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Quote\Api\CartManagementInterface         $cartManagement
     * @param \Fatchip\Nexi\Helper\Checkout                  $checkoutHelper
     * @param \Fatchip\Nexi\Helper\Api                       $apiHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Fatchip\Nexi\Helper\Checkout $checkoutHelper,
        \Fatchip\Nexi\Helper\Api $apiHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->agreementsValidator = $agreementValidator;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->checkoutHelper = $checkoutHelper;
        $this->apiHelper = $apiHelper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();

        $methodInstance = $quote->getPayment()->getMethodInstance();

        if ($this->isValidationRequired() &&
            !$this->agreementsValidator->isValid(array_keys($this->getRequest()->getPost('agreement', [])))
        ) {
            $e = new \Magento\Framework\Exception\LocalizedException(
                __('Please agree to all the terms and conditions before placing the order.')
            );
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect($methodInstance->getReviewPath());
            return;
        }

        try {
            if ($this->checkoutHelper->getQuoteComparisonString($quote) != $this->checkoutSession->getComputopQuoteComparisonString()) {
                // The basket was changed - abort current checkout
                $this->messageManager->addErrorMessage('An error occured during the Checkout.');
                $this->_redirect('checkout/cart');
                return;
            }

            $methodInstance->preReviewPlaceOrder($quote);

            $orderId = $this->placeOrder($quote);

            $redirectTarget = $methodInstance->postReviewPlaceOrder();

            if (!empty($redirectTarget)) {
                $this->clearSessionParams();

                $this->_redirect($redirectTarget);
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t place the order.')
            );
            $this->_redirect($methodInstance->getReviewPath());
        }

        // Processing shouldn't land here, an error occured if it does - so redirect to cart instead of showing a white page
        $this->messageManager->addErrorMessage('An error occured during the Checkout. X');
        $this->_redirect('checkout/cart');
    }

    /**
     * @return void
     */
    protected function clearSessionParams()
    {
        $this->checkoutSession->unsComputopTmpRefnr();
        $this->checkoutSession->unsComputopPpeIsExpressOrder();
        $this->checkoutSession->unsComputopPpeIsExpressAuthStep();
        $this->checkoutSession->unsComputopPpePayId();
        $this->checkoutSession->unsComputopPpeCompleteResponse();
        $this->checkoutSession->unsComputopQuoteComparisonString();
        $this->checkoutSession->unsComputopCancelledPaymentMethod();
    }

    /**
     * Place the order and put it in a finished state
     *
     * @param Magento\Quote\Model\Quote $quote
     * @return int
     */
    protected function placeOrder($quote)
    {
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        if ($this->checkoutHelper->getCurrentCheckoutMethod($quote) == Onepage::METHOD_GUEST) {
            $quote->setCustomerId(null)
                ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        $quote->setInventoryProcessed(false);
        $quote->collectTotals()->save();

        $orderId = $this->cartManagement->placeOrder($quote->getId());
        return $orderId;
    }

    /**
     * Return true if agreements validation required
     *
     * @return bool
     */
    protected function isValidationRequired()
    {
        return is_array($this->getRequest()->getBeforeForwardInfo()) && empty($this->getRequest()->getBeforeForwardInfo());
    }
}
