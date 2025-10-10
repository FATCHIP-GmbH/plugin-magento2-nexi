<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Service implements ArrayInterface
{
    const SERVICE_DIRECT = 'direct';
    const SERVICE_PPRO = 'ppro';

    /**
     * Return existing address check types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SERVICE_DIRECT,
                'label' => __('Direct'),
            ],
            [
                'value' => self::SERVICE_PPRO,
                'label' => __('via PPRO')
            ],
        ];
    }
}
