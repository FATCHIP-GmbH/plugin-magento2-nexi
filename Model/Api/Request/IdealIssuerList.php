<?php

namespace Fatchip\Nexi\Model\Api\Request;

class IdealIssuerList extends Base
{
    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = "IDEALISSUERLIST";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "idealIssuerList.aspx";

    /**
     * @var array[]
     */
    protected $issuerListFallback = [
        ['issuerId' => 'ABNANL2A', 'name' => 'ABN AMRO',     'country' => 'DE'],
        ['issuerId' => 'ASNBNL21', 'name' => 'ASN Bank',     'country' => 'DE'],
        ['issuerId' => 'BUNQNL2A', 'name' => 'Bunq',         'country' => 'DE'],
        ['issuerId' => 'INGBNL2A', 'name' => 'INGING',       'country' => 'DE'],
        ['issuerId' => 'KNABNL2H', 'name' => 'Knab',         'country' => 'DE'],
        ['issuerId' => 'RABONL2U', 'name' => 'Rabo',         'country' => 'DE'],
        ['issuerId' => 'RBRBNL21', 'name' => 'RegioBank',    'country' => 'DE'],
        ['issuerId' => 'SNSBNL2A', 'name' => 'SNS Bank',     'country' => 'DE'],
        ['issuerId' => 'TRIONL2U', 'name' => 'Triodos Bank', 'country' => 'DE'],
        ['issuerId' => 'FVLBNL22', 'name' => 'van Lanschot', 'country' => 'DE'],
    ];

    /**
     * Parse Response
     * Expected value like this: "BANKX,Issuer Test 1,Country A|BANKY,Issuer Test 2,CountryB|"
     *
     * @param  array $response
     * @return array
     */
    protected function parseResponse($response)
    {
        $return = [];
        if (!empty($response['IdealIssuerList'])) {
            $issuers = explode('|', $response['IdealIssuerList']);
            foreach ($issuers as $issuer) {
                $splitIssuer = explode(',', $issuer);
                if (is_array($splitIssuer) && count($splitIssuer) == 3) {
                    $return[] = [
                        'issuerId' => $splitIssuer[0],
                        'name' => $splitIssuer[1],
                        'country' => $splitIssuer[2],
                    ];
                }
            }
        }
        return $return;
    }

    /**
     * Collects ideal issuer list from Computop API
     *
     * @return array
     */
    public function getIssuerList()
    {
        $response = $this->handleStandardCurlRequest(null);
        $response = $this->parseResponse($response);
        if (empty($response)) {
            $response = $this->issuerListFallback;
        }
        return $response;
    }
}
