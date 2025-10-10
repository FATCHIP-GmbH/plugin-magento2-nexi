<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class AmazonMarketplace implements ArrayInterface
{
    /**
     * Return Amazon button colors
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => 'EU',
                'label' => __('EU'),
            ],
                        [
                'value' => 'UK',
                'label' => __('UK'),
            ],
                        [
                'value' => 'US',
                'label' => __('US'),
            ],
            [
                'value' => 'JP',
                'label' => __('JP')
            ]
        ];
        return $options;
    }
}
