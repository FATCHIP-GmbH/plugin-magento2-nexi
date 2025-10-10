<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class CaptureMethods implements ArrayInterface
{
    const CAPTURE_AUTO = 'AUTO';
    const CAPTURE_MANUAL = 'MANUAL';

    /**
     * Direct debit capture methods
     *
     * @var array
     */
    protected static $methods = [
        self::CAPTURE_AUTO,
        self::CAPTURE_MANUAL,
    ];

    /**
     * Return capture methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (self::$methods as $method) {
            $options[] = [
                'value' => $method,
                'label' => $method,
            ];
        }
        return $options;
    }
}
