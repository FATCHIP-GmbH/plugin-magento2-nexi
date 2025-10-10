<?php

namespace Fatchip\Nexi\Controller\Onepage;

class Payment extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context      $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory,
     * @param \Magento\Checkout\Model\Session            $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return void
     */
    public function execute()
    {
        $iframeUrl = $this->checkoutSession->getComputopRedirectUrl();
        if (empty($iframeUrl)) {
            $this->_redirect($this->_url->getUrl('checkout'));
            return;
        }

        $this->checkoutSession->setComputopCustomerIsRedirected(true);
        return $this->pageFactory->create();
    }
}
