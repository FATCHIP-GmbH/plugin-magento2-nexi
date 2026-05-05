<?php

namespace Fatchip\Nexi\Api\Data;

interface DeviceFingerprintSentResponseInterface
{
    /**
     * Returns if the request was a success
     *
     * @return bool
     */
    public function getSuccess();
}