<?php

namespace Fatchip\Nexi\Service\V1\Data;

use Fatchip\Nexi\Api\Data\CreditcardRequestDataResponseInterface;

class CreditcardRequestDataResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements CreditcardRequestDataResponseInterface
{
    /**
     * Returns whether the request was a success
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->_get('success');
    }

    /**
     * Returns errormessage
     *
     * @return string
     */
    public function getErrormessage()
    {
        return $this->_get('errormessage');
    }

    /**
     * Return Data param for silent mode cc request
     *
     * @return string
     */
    public function getDataParam()
    {
        return $this->_get('dataParam');
    }

    /**
     * Return Len param for silent mode cc request
     *
     * @return string
     */
    public function getLenParam()
    {
        return $this->_get('lenParam');
    }

    /**
     * Return MerchantID param for silent mode cc request
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_get('merchantId');
    }
}
