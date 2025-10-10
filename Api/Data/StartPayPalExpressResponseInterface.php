<?php

namespace Fatchip\Nexi\Api\Data;

interface StartPayPalExpressResponseInterface
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
}
