<?php

namespace Fatchip\Nexi\Block\Onepage\Review;

use Magento\Store\Model\ScopeInterface;

/**
 * @api
 * @since 100.0.2
 */
class EasyCreditInfo extends \Magento\Framework\View\Element\Template
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

    public function getFinancingInfo()
    {
        $info = $this->checkoutSession->getComputopEasyCreditInfo();
        return json_decode(base64_decode($info['financing']), true);
    }

    /**
     * Return financing info value
     *
     * @param  string $param
     * @param  bool   $priceFormat
     * @return array|string|string[]
     */
    public function getFinancingInfoValue($param, $priceFormat = false)
    {
        $return = '';

        $info = $this->getFinancingInfo();
        if (isset($info['decision'][$param])) {
            $return = $info['decision'][$param];
            if ($priceFormat === true) {
                $return = number_format($return, 2, ",", "");
            }
        }
        return $return;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
    }
}
