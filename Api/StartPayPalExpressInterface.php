<?php

namespace Fatchip\Nexi\Api;

interface StartPayPalExpressInterface
{
    /**
     * Logs the PPE auth request and set PPE as used payment method
     *
     * @param  string $cartId
     * @param  string $data
     * @param  string $len
     * @return \Fatchip\Nexi\Service\V1\Data\StartPayPalExpressResponse
     */
    public function start($cartId, $data, $len);
}
