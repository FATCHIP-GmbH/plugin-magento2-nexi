<?php

namespace Fatchip\Nexi\Model\Method\Ratepay;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class DirectDebit extends Base
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_RATEPAY_DIRECTDEBIT;

    /**
     * @var string|null
     */
    protected $rpMethod = Base::METHOD_DIRECT_DEBIT;

    /**
     * @var string|null
     */
    protected $rpDebitPayType = Base::DEBIT_PAY_TYPE_DIRECT_DEBIT;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'iban',
        'bic',
        'accountholder',
        'dateofbirth',
        'telephone',
    ];

    /**
     * Return parameters specific to this payment subtype
     *
     * @param  Order $order
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $order)
    {
        $return = parent::getSubTypeSpecificParameters($order);

        $infoInstance = $this->getInfoInstance();

        $return['AccOwner'] = $infoInstance->getAdditionalInformation('accountholder');
        $return['IBAN'] = $infoInstance->getAdditionalInformation('iban');

        if (!empty($infoInstance->getAdditionalInformation('bic'))) {
            $return['BIC'] = $infoInstance->getAdditionalInformation('bic');
        }
        return $return;
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'requestBic' => (bool)$this->getPaymentConfigParam('request_bic'),
        ];
    }
}
