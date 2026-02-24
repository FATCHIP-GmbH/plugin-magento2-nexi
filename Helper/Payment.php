<?php

namespace Fatchip\Nexi\Helper;

use Fatchip\Nexi\Model\ComputopConfig;

class Payment extends Base
{
    /**
     * List of all currently available Computop payment methods
     *
     * @var array
     */
    protected $availablePayments = [
        ComputopConfig::METHOD_CREDITCARD,
        ComputopConfig::METHOD_DIRECTDEBIT,
        ComputopConfig::METHOD_PAYPAL,
        ComputopConfig::METHOD_KLARNA,
        ComputopConfig::METHOD_IDEAL,
        ComputopConfig::METHOD_EASYCREDIT,
        ComputopConfig::METHOD_AMAZONPAY,
        ComputopConfig::METHOD_RATEPAY_DIRECTDEBIT,
        ComputopConfig::METHOD_RATEPAY_INVOICE,
        ComputopConfig::METHOD_PRZELEWY24,
        ComputopConfig::METHOD_WERO,
    ];

    /**
     * Return all available payment types
     *
     * @return array
     */
    public function getAvailablePaymentTypes()
    {
        return $this->availablePayments;
    }

    /**
     * Generates random transaction id for TransID parameter
     * Taken from library-computop generateTransID() method
     *
     * @param  int $digitCount
     * @return string
     */
    public function getTransactionId($digitCount = 12)
    {
        mt_srand(intval(microtime(true) * 1000000));

        $transID = (string)mt_rand();
        // y: 2 digits for year
        // m: 2 digits for month
        // d: 2 digits for day of month
        // H: 2 digits for hour
        // i: 2 digits for minute
        // s: 2 digits for second
        $transID .= date('ymdHis');
        // $transID = md5($transID);
        return substr($transID, 0, $digitCount);
    }

    /**
     * Generates a request id
     * Doc says: To avoid double payments or actions (e.g. by ETM), enter an alphanumeric value which identifies your transaction and may be assigned only once.
     * If the transaction or action is submitted again with the same ReqID, Computop Paygate will not carry out the payment or new action,
     * but will just return the status of the original transaction or action.
     *
     * @return string
     */
    public function getRequestId()
    {
        mt_srand(intval(microtime(true) * 1000000));
        $reqID = (string)mt_rand();
        $reqID .= date('yzGis');
        return $reqID;
    }
}
