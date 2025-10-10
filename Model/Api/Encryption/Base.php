<?php

namespace Fatchip\Nexi\Model\Api\Encryption;

abstract class Base
{
    /**
     * @var \Fatchip\Nexi\Helper\Base
     */
    protected $baseHelper;

    /**
     * Constructor
     *
     * @param \Fatchip\Nexi\Helper\Base $baseHelper
     */
    public function __construct(
        \Fatchip\Nexi\Helper\Base $baseHelper
    ) {
        $this->baseHelper = $baseHelper;
    }

    /**
     * @return string
     */
    protected function getPasswordFromConfig()
    {
        return $this->baseHelper->getConfigParam('password');
    }

    /**
     * @param string $splitString
     * @return array
     */
    protected function ctSplit($splitString)
    {
        // $splitString will be a URL-like param string but must not be treated like that!
        // Like a=1&b=2&c=3
        $splitArray = explode('&', $splitString); // what happens when there is a legit "&" in one of the parameters values???

        $result = [];
        foreach ($splitArray as $text) {
            $split = explode("=", $text, 2); // Limit 2 is important to not truncate values with a legit "=" in it
            $result[$split[0]] = $split[1];
        }
        return $result;
    }

    /**
     * Encrypt the passed text (any encoding)
     *
     * @param string $plaintext
     * @param integer $len
     * @param string $password
     * @return bool|string
     */
    public function ctEncrypt($plaintext, $len, $password = false)
    {
        // Has to be implemented by child classes
        return "";
    }

    /**
     * Decrypt the passed HEX string
     *
     * @param string  $data
     * @param integer $len
     * @param string  $password
     * @return array
     */
    public function ctDecrypt($data, $len, $password = false)
    {
        // Has to be implemented by child classes
        return [];
    }
}
