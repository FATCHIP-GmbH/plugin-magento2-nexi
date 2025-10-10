<?php

namespace Fatchip\Nexi\Model\Entities;

use Magento\Framework\Model\AbstractModel;

/**
 * ApiLog entity model
 */
class ApiLog extends AbstractModel
{
    /**
     * These keys have base64 encoded values and neede to be decoded
     *
     * @var string[]
     */
    protected $base64EncodedKeys = [
        'card',
        'threedsdata',
        'resultsresponse',
        'browserInfo',
        'billingAddress',
        'shippingAddress',
        'resultsresponse',
        'credentialOnFile',
        'billToCustomer',
        'userData',
        'ArticleList',
        'shoppingBasket',
        'items',
        'vats',
        'Articlelist',
    ];

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Fatchip\Nexi\Model\ResourceModel\ApiLog');
    }

    /**
     * Returns json decoded request
     *
     * @return array
     */
    public function getRequestDetails()
    {
        if (empty($this->getData('request_details'))) {
            return [];
        }

        $details = json_decode($this->getData('request_details'), true);
        foreach ($details as $key => $value) {
            if (in_array($key, $this->base64EncodedKeys)) {
                $details[$key] = json_decode(base64_decode($value), true);
            }
        }
        return $details;
    }

    /**
     * Returns json decoded response
     *
     * @return array
     */
    public function getResponseDetails()
    {
        if (empty($this->getData('response_details'))) {
            return [];
        }

        $details = json_decode($this->getData('response_details'), true);
        foreach ($details as $key => $value) {
            if (in_array($key, $this->base64EncodedKeys)) {
                $details[$key] = json_decode(base64_decode($value), true);
            }
        }
        return $details;
    }
}
