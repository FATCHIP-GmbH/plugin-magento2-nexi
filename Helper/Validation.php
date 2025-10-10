<?php

namespace Fatchip\Nexi\Helper;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\Service;

class Validation extends Base
{
    /**
     * Length of Magento increment_id used as refNr
     *
     * @var int
     */
    protected $refNrLength = 9;

    /**
     * @var \Fatchip\Nexi\Helper
     */
    protected $apiHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Fatchip\Nexi\Helper\Api               $apiHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Fatchip\Nexi\Helper\Api $apiHelper
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->apiHelper = $apiHelper;
    }

    /**
     * Generate a random incrementId
     *
     * @return string
     */
    protected function getRandomIncrementId()
    {
        $randomNumber = (string)rand(1,(pow(10, $this->refNrLength)) - 1); // Number between 1 and 999999999
        while (strlen($randomNumber) < $this->refNrLength) {
            $randomNumber = "0".$randomNumber; // Add 0 at beginning till randomNumber has a length of refNrLength
        }
        return $randomNumber;
    }

    /**
     * Get currently relevant regex rules
     *
     * @return array
     */
    protected function getRefNrRegexRules()
    {
        $rules = [];
        if ((bool)$this->getConfigParam('active', ComputopConfig::METHOD_IDEAL, 'payment') === true) {
            $idealService = $this->getConfigParam('service', ComputopConfig::METHOD_IDEAL, 'computop_payment');
            if ($idealService == Service::SERVICE_PPRO) {
                $rules[] = [
                    'regex' => '^[a-zA-Z0-9,_-]{1,40}$', // a-zA-Z0-9,-_   max 40
                    'errormessage' => __("Order number prefix/suffix validation failed: RefNr param may only consist of letters, numbers, comma, underscore and dash. Additionally the combination of prefix, the 9-digit order number and suffix must not be longer than 40 characters in total. Please change prefix/suffix accordingly."),
                ];
            }
            if ($idealService == Service::SERVICE_DIRECT) {
                $rules[] = [
                    'regex' => '^.{1,15}$', // max 15
                    'errormessage' => __("Order number prefix/suffix validation failed: The combination of prefix, the 9-digit order number and suffix must not be longer than 15 characters in total. Please change prefix/suffix accordingly."),
                ];
            }
        }
        return $rules;
    }

    /**
     * Validate reference number prefix and suffix
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateRefNrExtensions()
    {
        $rndIncrementNr = $this->apiHelper->getReferenceNumber($this->getRandomIncrementId());

        $regexArray = $this->getRefNrRegexRules();
        foreach ($regexArray as $rule) {
            preg_match('/'.$rule['regex'].'/', $rndIncrementNr, $matches);
            if (empty($matches)) {
                throw new \Magento\Framework\Exception\LocalizedException($rule['errormessage']);
            }
        }
    }
}
