<?php

namespace Fatchip\Nexi\Helper;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Environment extends Base
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Framework\App\State                $state
     * @param \Magento\Framework\App\RequestInterface     $request
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->request = $request;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Get IP address of the customer
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $isProxyMode = (bool)$this->getConfigParam('proxy_mode');
        $clientIp = $this->request->getClientIp($isProxyMode); // may return a comma separated ip list like "<client>, <proxy1>, <proxy2>"
        $splitIp = explode(",", $clientIp); // split by comma
        return trim(current($splitIp)); // return first array element
    }

    /**
     * Returns ISO 639-1 alpha 2 locale
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = $this->localeResolver->getLocale();
        if (empty($locale)) {
            return 'en';
        }
        return substr($locale, 0, 2);
    }
}
