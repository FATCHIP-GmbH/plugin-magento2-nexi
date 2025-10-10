<?php

namespace Fatchip\Nexi\Helper;

class Country extends Base
{
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Directory\Model\CountryFactory    $countryFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->countryFactory = $countryFactory;
    }

    /**
     * Returns iso3 code for given country id
     *
     * @param  string $countryId
     * @return string
     */
    public function getIso3Code($countryId)
    {
        $country = $this->countryFactory->create()->load($countryId);
        return $country->getData('iso3_code');
    }
}
