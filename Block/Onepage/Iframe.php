<?php

namespace Fatchip\Nexi\Block\Onepage;

class Iframe extends \Magento\Framework\View\Element\Template
{
    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param  \Magento\Checkout\Model\Session                  $checkoutSession
     * @param  \Magento\Framework\View\Element\Template\Context $context
     * @param  array                                            $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns redirect url as iframe url
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->checkoutSession->getComputopRedirectUrl();
    }
}
