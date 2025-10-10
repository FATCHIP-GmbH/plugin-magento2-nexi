<?php

namespace Fatchip\Nexi\Api;

interface AmazonPayInterface
{
    /**
     * Get Amazon Pay button parameters
     *
     * @param  string $orderId
     * @return \Fatchip\Nexi\Service\V1\Data\AmazonPayResponse
     */
    public function getAmazonPayApbSession($orderId);
}
