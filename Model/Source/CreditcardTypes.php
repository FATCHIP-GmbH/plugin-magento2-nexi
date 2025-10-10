<?php

namespace Fatchip\Nexi\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class CreditcardTypes implements ArrayInterface
{
    /**
     * Creditcard types
     *
     * @var array
     */
    protected static $types = [
        'visa'            => array('name' => 'Visa',             'cardtype' => 'VISA',       'cvc_length' => 3),
        'mastercard'      => array('name' => 'Mastercard',       'cardtype' => 'MasterCard', 'cvc_length' => 3),
        'americanexpress' => array('name' => 'American Express', 'cardtype' => 'AMEX',       'cvc_length' => 4),
    ];

    /**
     * Return available creditcard type array
     *
     * @return array
     */
    public static function getCreditcardTypes()
    {
        return self::$types;
    }

    /**
     * Return existing creditcard types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (self::$types as $id => $type) {
            $options[] = [
                'value' => $id,
                'label' => $type['name'],
            ];
        }
        return $options;
    }
}
