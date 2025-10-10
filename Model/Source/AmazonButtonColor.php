<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class AmazonButtonColor implements ArrayInterface
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
                'value' => 'Gold',
                'label' => __('Gold'),
            ],
            [
                'value' => 'LightGray',
                'label' => __('Light Gray')
            ],
            [
                'value' => 'DarkGray',
                'label' => __('Dark Gray')
            ]
        ];
        return $options;
    }
}
