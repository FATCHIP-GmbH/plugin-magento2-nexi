<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class DirectDebit extends ServerToServerPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_DIRECTDEBIT;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "edddirect.aspx";

    /**
     * @var string
     */
    protected $requestType = "LASTSCHRIFT";

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'bank',
        'iban',
        'bic',
        'accountholder',
    ];

    /**
     * Determines if payment method will receive Notify calls from Computop
     *
     * @var bool
     */
    protected $isNotifyPaymentType = false;

    /**
     * Each ELV payment needs a unique mandateID.
     * For now, it is the ordernumber plus date
     *
     * @param  string $orderID
     * @return string
     */
    public function createMandateId($orderId)
    {
        return $orderId.date('yzGis');
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

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $infoInstance = $this->getInfoInstance();
        return [
            'AccBank' => $infoInstance->getAdditionalInformation('bank'),
            'AccOwner' => $infoInstance->getAdditionalInformation('accountholder'),
            'IBAN' => $infoInstance->getAdditionalInformation('iban'),
            'BIC' => $infoInstance->getAdditionalInformation('bic'),
            'MandateID' => $this->createMandateId($order->getIncrementId()),
            'DtOfSgntr' => date('d-m-Y'),
            'Capture' => $this->getPaymentConfigParam('capture_method'),
        ];
    }
}
