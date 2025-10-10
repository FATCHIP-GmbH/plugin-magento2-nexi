<?php

namespace Fatchip\Nexi\Model\Api\Request;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Method\BaseMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;

class EasyCreditConfirm extends Base
{
    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = "CON";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "easyCreditDirect.aspx";

    /**
     * @param  InfoInterface $payment
     * @param  array         $response
     * @return array
     */
    public function generateRequest(InfoInterface $payment, $response)
    {
        $order = $payment->getOrder();

        $this->addParameter('Currency', $order->getOrderCurrencyCode());
        $this->addParameter('Amount', $this->apiHelper->formatAmount($order->getTotalDue(), $order->getOrderCurrencyCode()));

        if (!empty($response['PayID'])) {
            $this->addParameter('PayID', $response['PayID']);
        }
        if (!empty($response['TransID'])) {
            $this->addParameter('TransID', $response['TransID']);
        }
        if (!empty($response['refnr'])) {
            $this->addParameter('refnr', $response['refnr']);
        }

        $this->addParameter('ReqId', $this->paymentHelper->getRequestId());
        $this->addParameter('EtiID', $this->apiHelper->getIdentString());
        #$this->addParameter('RefNr', $this->apiHelper->getReferenceNumber($order->getIncrementId()));

        $this->addParameter('EventToken', 'CON');

        $params = $this->getParameters();

        return $params;
    }

    /**
     * Send capture request to Computop API
     *
     * @param  InfoInterface $payment
     * @param  array         $response
     * @return array
     */
    public function sendRequest(InfoInterface $payment, $response)
    {
        $params = $this->generateRequest($payment, $response);

        $response = $this->handleStandardCurlRequest($params, $payment->getOrder());
        if (empty($response)) {
            throw new \Exception("An unknown error occured.");
        }
        if ($this->apiHelper->isSuccessStatus($response) === false) {
            if ($response['Status'] == 'FAILED' && $response['Description'] == 'DISABLED') {
                throw new LocalizedException(__('An error occured. We were not able to confirm the easyCredit payment.'));
            }
            throw new \Exception("An error occured: ".strtolower($response['Description']));
        }
        return $response;
    }
}
