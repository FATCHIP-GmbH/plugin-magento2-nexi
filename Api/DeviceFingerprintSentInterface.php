<?php

namespace Fatchip\Nexi\Api;

interface DeviceFingerprintSentInterface
{
    /**
     * Set session flag to indicate that DFP was sent
     *
     * @return \Fatchip\Nexi\Service\V1\Data\DeviceFingerprintSentResponse
     */
    public function markDfpAsSent();
}
