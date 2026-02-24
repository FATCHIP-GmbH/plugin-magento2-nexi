<?php

namespace Fatchip\Nexi\Model;

/**
 * Collection of constant values
 */
abstract class ComputopConfig
{
    /* Module version */
    const MODULE_VERSION = '1.0.0';
    const MODULE_NAME = 'Fatchip_Nexi';

    /* Payment method codes */
    const METHOD_CREDITCARD = 'computop_creditcard';
    const METHOD_DIRECTDEBIT = 'computop_directdebit';
    const METHOD_PAYPAL = 'computop_paypal';
    const METHOD_KLARNA = 'computop_klarna';
    const METHOD_IDEAL = 'computop_ideal';
    const METHOD_EASYCREDIT = 'computop_easycredit';
    const METHOD_AMAZONPAY = 'computop_amazonpay';
    const METHOD_RATEPAY_DIRECTDEBIT = 'computop_ratepay_directdebit';
    const METHOD_RATEPAY_INVOICE = 'computop_ratepay_invoice';
    const METHOD_PRZELEWY24  = 'computop_przelewy24';
    const METHOD_WERO  = 'computop_wero';

    const STATUS_CODE_SUCCESS = '00000000';

    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_OK = 'OK';
    const STATUS_PENDING = 'PENDING';

    const QUOTE_REFNR_PREFIX = 'tmp_';

    const ROUTE_NAME = 'nexi';
}
