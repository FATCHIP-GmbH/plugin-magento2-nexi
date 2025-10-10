<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class CreditcardModes implements ArrayInterface
{
    const CC_MODE_IFRAME = 'iframe';
    const CC_MODE_PAYMENT_PAGE = 'paymentpage';
    const CC_MODE_SILENT = 'silent';

    /**
     * Return existing address check types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CC_MODE_IFRAME,
                'label' => __('iFrame'),
            ],
            [
                'value' => self::CC_MODE_PAYMENT_PAGE,
                'label' => __('Payment Page')
            ],
            [
                'value' => self::CC_MODE_SILENT,
                'label' => __('Silent Mode')
            ],
        ];
    }
}
