<?php

namespace Fatchip\Nexi\Service\V1\Data;

use Fatchip\Nexi\Api\Data\StartPayPalExpressResponseInterface;

/**
 * Object for
 */
class StartPayPalExpressResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements StartPayPalExpressResponseInterface
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
}
