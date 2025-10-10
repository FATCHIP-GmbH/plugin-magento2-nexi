<?php

namespace Fatchip\Nexi\Api\Data;

interface AmazonPayResponseInterface
{
    /**
     * Returns whether the request was a success
     *
     * @return bool
     */
    public function getSuccess();

    /**
     * Returns errormessage
     *
     * @return string
     */
    public function getErrormessage();

    /**
     * Returns Amazon Pay button payload
     *
     * @return string
     */
    public function getPayload();

    /**
     * Returns Amazon Pay button signature
     *
     * @return string
     */
    public function getSignature();
}
