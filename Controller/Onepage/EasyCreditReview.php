<?php

namespace Fatchip\Nexi\Controller\Onepage;

use Fatchip\Nexi\Model\Api\Request\GetEasyCreditInfo;
use Fatchip\Nexi\Model\ComputopConfig;

class EasyCreditReview extends Review
{
    /**
     * @var GetEasyCreditInfo
     */
    protected $getEasyCreditInfo;

    /**
     * List of all payment methods available for this review step
     *
     * @var array
     */
    protected $availableReviewMethods = [
        ComputopConfig::METHOD_EASYCREDIT
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory            $pageFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface            $quoteRepository
     * @param \Fatchip\Nexi\Model\Api\Request\GetEasyCreditInfo $getEasyCreditInfo
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Fatchip\Nexi\Model\Api\Request\GetEasyCreditInfo $getEasyCreditInfo
    ) {
        parent::__construct($context, $checkoutSession, $pageFactory, $quoteRepository);
        $this->getEasyCreditInfo = $getEasyCreditInfo;
    }

    /**
     * Validates if the review step can be shown by checking some status flags
     *
     * @return bool
     */
    protected function canReviewBeShown()
    {
        if (empty($this->checkoutSession->getQuote()) || empty($this->checkoutSession->getQuote()->getPayment()) || empty($this->checkoutSession->getQuote()->getPayment()->getQuote()) || (empty($this->checkoutSession->getComputopNoOrderRedirectResponse()) && empty($this->checkoutSession->getComputopEasyCreditInfo()))) {
            return false;
        }
        return parent::canReviewBeShown();
    }

    /**
     * Render order review
     * Redirect to basket if quote or payment is missing
     *
     * @return null|Page|CoreRedirect
     */
    public function execute()
    {
        $return = parent::execute();

        if ($return instanceof \Magento\Framework\Controller\Result\Redirect) { // probably some kind of error occured
            return $return;
        }

        if (empty($this->checkoutSession->getComputopEasyCreditInfo())) {
            $info = $this->getEasyCreditInfo->sendRequest($this->checkoutSession->getQuote()->getPayment(), $this->checkoutSession->getComputopNoOrderRedirectResponse());
            if (empty($info['Status']) || $info['Status'] != 'AUTHORIZE_REQUEST' || empty($info['financing'])) {
                /** @var CoreRedirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout');
            }

            $financingInfo = json_decode(base64_decode($info['financing']), true);
            $this->checkoutSession->setComputopEasyCreditInfo($info);
        }

        return $return;
    }
}
