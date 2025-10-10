<?php

namespace Fatchip\Nexi\Helper;

use Fatchip\Nexi\Model\ComputopConfig;
use Locale;
use Magento\Framework\Exception\LocalizedException;

class Api extends Base
{
    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $store;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var string
     */
    protected $defaultLocale = 'EN';

    /**
     * Source: https://en.wikipedia.org/wiki/ISO_4217#Active_codes_(List_One)
     *
     * @var array
     */
    protected $nonDecimalCurrencies = [
        'BIF', // Burundian franc	 Burundi
        'CLP', // Chilean peso	 Chile
        'DJF', // Djiboutian franc	 Djibouti
        'GNF', // Guinean franc	 Guinea
        'ISK', // Icelandic króna (plural: krónur)	 Iceland
        'JPY', // Japanese yen	 Japan
        'KMF', // Comoro franc	 Comoros
        'KRW', // South Korean won	 South Korea
        'PYG', // Paraguayan guaraní	 Paraguay
        'RWF', // Rwandan franc	 Rwanda
        'UGX', // Ugandan shilling	 Uganda
        'UYI', // Uruguay Peso en Unidades Indexadas (URUIURUI) (funds code)	 Uruguay
        'VND', // Vietnamese đồng	 Vietnam
        'VUV', // Vanuatu vatu	 Vanuatu
        'XAF', // CFA franc BEAC	 Cameroon (CM),  Central African Republic (CF),  Republic of the Congo (CG),  Chad (TD),  Equatorial Guinea (GQ),  Gabon (GA)
        'XOF', // CFA franc BCEAO	 Benin (BJ),  Burkina Faso (BF),  Côte d'Ivoire (CI),  Guinea-Bissau (GW),  Mali (ML),  Niger (NE),  Senegal (SN),  Togo (TG)
        'XPF', // CFP franc (franc Pacifique)	French territories of the Pacific Ocean:  French Polynesia (PF),  New Caledonia (NC),  Wallis and Futuna (WF)
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Store\Api\Data\StoreInterface     $store
     * @param \Magento\Framework\App\ProductMetadata     $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->store = $store;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Formats amount for API
     * Docs say: Amount in the smallest currency unit (e.g. EUR Cent)
     *
     * @param double $amount
     * @param string $currencyCode
     * @return float|int
     */
    public function formatAmount($amount, $currencyCode = 'EUR')
    {
        $decimalMultiplier = 100;
        if (in_array($currencyCode, $this->nonDecimalCurrencies)) {
            $decimalMultiplier = 1;
        }
        return number_format($amount * $decimalMultiplier, 0, '.', '');
    }

    /**
     * Returns the current locale of the store
     *
     * @return false|string
     */
    public function getStoreLocale()
    {
        $locale = substr($this->store->getLocale() ?? '', 0, 2);
        if (empty($locale)) {
            $locale = substr($this->store->getDefaultLocale() ?? '', 0, 2);
        }
        if (empty($locale)) {
            $locale = Locale::getPrimaryLanguage($this->getConfigParam('code', 'locale', 'general'));
        }
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }
        return strtoupper($locale);
    }

    /**
     * Returns Magento version of current shop installtion
     *
     * @return mixed
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get identification string for requests
     *
     * @return string
     */
    public function getIdentString()
    {
        return 'Magento '.$this->getMagentoVersion().', Module version: '.ComputopConfig::MODULE_VERSION;
    }

    /**
     * Encode array in json and then in base64 for api requests
     *
     * @param  array $array
     * @return string
     */
    public function encodeArray($array)
    {
        return base64_encode(json_encode($array));
    }

    /**
     * Returns reference number
     *
     * @param  string $incrementId
     * @return string
     */
    public function getReferenceNumber($incrementId)
    {
        return trim($this->getConfigParam('ordernr_prefix') ?? '').$incrementId.trim($this->getConfigParam('ordernr_suffix') ?? '');
    }

    /**
     * Check if given response has a success response
     *
     * @param  array $response
     * @return bool
     */
    public function isSuccessStatus($response)
    {
        if (isset($response['Code']) && ($response['Code'] == ComputopConfig::STATUS_CODE_SUCCESS || substr($response['Code'], 0, 1) == '0')) { # 0 = Ok, 2 = Error, 4 = Fatal Error
            return true;
        }
        return false;
    }

    /**
     * Check if given response has a pending response
     *
     * @param  array $response
     * @return bool
     */
    public function isPendingStatus($response)
    {
        if (isset($response['Status']) && ($response['Status'] == ComputopConfig::STATUS_PENDING || substr($response['Code'], 0, 1) == '6')) { # 6 = Pending
            return true;
        }
        return false;
    }

    /**
     * @param  string $transId
     * @return string
     * @throws LocalizedException
     */
    public function getTruncatedTransactionId($transId)
    {
        if (empty($transId)) {
            throw new LocalizedException(__('Error: Transaction couldn\'t be found.'));
        }

        if (strpos($transId, '-') !== false) {
            $split = explode('-', $transId);
            $transId = $split[0];
        }

        $transId = preg_replace('/[^a-zA-Z0-9]/', '', $transId); // remove all special characters

        return $transId;
    }
}
