<?php

namespace Fatchip\Nexi\Api;

interface CreditcardRequestDataInterface
{
    /**
     * Returns Data and Len parameters for creditcard silent mode request
     *
     * @param  string $orderId
     * @param  bool   $javaEnabled
     * @param  int    $screenHeight
     * @param  int    $screenWidth
     * @param  int    $colorDepth
     * @param  int    $timeZoneOffset
     * @return \Fatchip\Nexi\Service\V1\Data\CreditcardRequestDataResponse
     */
    public function getCreditcardRequestData($orderId, $javaEnabled, $screenHeight, $screenWidth, $colorDepth, $timeZoneOffset);
}
