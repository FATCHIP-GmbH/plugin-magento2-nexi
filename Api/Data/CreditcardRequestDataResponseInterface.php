<?php

namespace Fatchip\Nexi\Api\Data;

interface CreditcardRequestDataResponseInterface
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
     * Return Data param for silent mode cc request
     *
     * @return string
     */
    public function getDataParam();

    /**
     * Return Len param for silent mode cc request
     *
     * @return string
     */
    public function getLenParam();

    /**
     * Return MerchantID param for silent mode cc request
     *
     * @return string
     */
    public function getMerchantId();
}
