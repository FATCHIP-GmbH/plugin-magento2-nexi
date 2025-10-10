<?php

namespace Fatchip\Nexi\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Area;

class Base extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->state = $state;
    }

    /**
     * Returns the config value for given path parts
     *
     * @param  string $key
     * @param  string $group
     * @param  string $section
     * @param  string $storeCode
     * @return string
     */
    public function getConfigParam($key, $group = 'global', $section = 'computop_general', $storeCode = null)
    {
        $path = $section."/".$group."/".$key;
        return $this->getConfigParamByPath($path, $storeCode);
    }

    /**
     * Get parameter from the config by path
     *
     * @param  string $path
     * @param  string $storeCode
     * @return string
     */
    public function getConfigParamByPath($path, $storeCode = null)
    {
        $scopeCode = ScopeInterface::SCOPE_STORES;
        if (!$storeCode) {
            list($storeCode, $scopeCode) = $this->fetchCurrentStoreCode();
        }
        return $this->scopeConfig->getValue($path, $scopeCode, $storeCode);
    }

    /**
     * Get parameter from the request
     *
     * @param  string $parameter
     * @return mixed
     */
    public function getRequestParameter($parameter)
    {
        return $this->_getRequest()->getParam($parameter);
    }

    /**
     * Trying to fetch the current storeCode
     * Fetching the correct storeCode in the Magento2 backend is very inaccurate
     *
     * @return array
     */
    protected function fetchCurrentStoreCode()
    {
        $scopeCode = ScopeInterface::SCOPE_STORES;
        $storeCode = $this->storeManager->getStore()->getCode();
        if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {
            $storeCode = 0; // 0 = default config, which should be used when neither website nor store parameter are present, storeManager returns default STORE though, which would be wrong
            if (!empty($this->getRequestParameter('website'))) {
                $storeCode = $this->getRequestParameter('website');
                $scopeCode = ScopeInterface::SCOPE_WEBSITES;
            }
            if (!empty($this->getRequestParameter('store'))) {
                $storeCode = $this->getRequestParameter('store');
            }
        }

        return [$storeCode, $scopeCode];
    }
}
