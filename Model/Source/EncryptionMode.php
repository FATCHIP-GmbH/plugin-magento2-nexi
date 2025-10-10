<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class EncryptionMode implements ArrayInterface
{
    const ENC_MODE_BLOWFISH = 'blowfish';
    const ENC_MODE_AES = 'aes';

    /**
     * Return existing encryption modes
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENC_MODE_BLOWFISH,
                'label' => __('Blowfish'),
            ],
            [
                'value' => self::ENC_MODE_AES,
                'label' => __('AES')
            ],
        ];
    }
}
