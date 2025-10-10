<?php

namespace Fatchip\Nexi\Model\Api\Request;

class RefNrChange extends Base
{
    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = "REFNRCHANGE";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "RefNrChange.aspx";

    /**
     * Change RefNr of a payment
     *
     * @return array
     */
    public function changeRefNr($payId, $refNr)
    {
        $params = [
            'PayId' => $payId,
            'RefNr' => $refNr,
        ];
        $response = $this->handleStandardCurlRequest($params);
        return $response;
    }
}
