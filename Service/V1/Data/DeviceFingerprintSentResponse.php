<?php

namespace Fatchip\Nexi\Service\V1\Data;

use Fatchip\Nexi\Api\Data\DeviceFingerprintSentResponseInterface;

class DeviceFingerprintSentResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements DeviceFingerprintSentResponseInterface
{
    /**
     * Returns if the request was successful
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->_get('success');
    }
}