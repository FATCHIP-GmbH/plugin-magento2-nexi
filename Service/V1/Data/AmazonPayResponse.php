<?php

namespace Fatchip\Nexi\Service\V1\Data;

use Fatchip\Nexi\Api\Data\AmazonPayResponseInterface;

class AmazonPayResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements AmazonPayResponseInterface
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
     * Returns Amazon Pay button payload
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->_get('payload');
    }

    /**
     * Returns Amazon Pay button signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->_get('signature');
    }
}
