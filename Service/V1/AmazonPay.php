<?php

namespace Fatchip\Nexi\Service\V1;

use Fatchip\Nexi\Api\AmazonPayInterface;
use Fatchip\Nexi\Api\Data\AmazonPayResponseInterfaceFactory;
use Magento\Checkout\Model\Session;

class AmazonPay implements AmazonPayInterface
{
    /**
     * Factory for the response object
     *
     * @var AmazonPayResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Constructor.
     *
     * @param AmazonPayResponseInterfaceFactory $responseFactory
     * @param Session                           $checkoutSession
     */
    public function __construct(
        AmazonPayResponseInterfaceFactory $responseFactory,
        Session $checkoutSession
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get Amazon Pay button parameters
     *
     * @param  string $orderId
     * @return \Fatchip\Nexi\Service\V1\Data\AmazonPayResponse
     */
    public function getAmazonPayApbSession($orderId)
    {
        $response = $this->responseFactory->create();

        $success = false;
        $payload = $this->checkoutSession->getComputopAmazonPayPayload();
        $signature = $this->checkoutSession->getComputopAmazonPaySignature();

        if (!empty($payload) && !empty($signature)) {
            $success = true;

            $response->setData('payload', $payload);
            $response->setData('signature', $signature);
        }

        $this->checkoutSession->unsComputopAmazonPayPayload();
        $this->checkoutSession->unsComputopAmazonPaySignature();

        $response->setData('success', $success);
        return $response;
    }
}
