<?php

namespace Fatchip\Nexi\Model\Api\Request;

class Inquire extends Base
{
    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = "INQUIRE";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "inquire.aspx";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpointByTransId = "inquire24.aspx";

    /**
     * @var bool
     */
    protected $logRequest = false;

    /**
     * Collects ideal issuer list from Computop API
     *
     * @return array
     */
    public function getPaymentStatus($payId, $transId)
    {
        $params = [
            'PayId' => $payId,
            'TransID' => $transId,
        ];
        $response = $this->handleStandardCurlRequest($params);
        return $response;
    }

    /**
     * Collects ideal issuer list from Computop API
     *
     * @return array
     */
    public function getPaymentStatusByTransId($transId)
    {
        $params = [
            'TransID' => $transId,
        ];
        $response = $this->handleStandardCurlRequest($params, null, $this->apiEndpointByTransId);
        return $response;
    }
}
