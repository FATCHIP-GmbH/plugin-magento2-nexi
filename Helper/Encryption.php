<?php

namespace Fatchip\Nexi\Helper;

use Fatchip\Nexi\Model\Api\Encryption\AES;
use Fatchip\Nexi\Model\Api\Encryption\Blowfish;

class Encryption extends Base
{
    /**
     * @var Blowfish
     */
    protected $blowfish;

    /**
     * @var AES
     */
    protected $aes;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Framework\App\State                $state
     * @param Blowfish                                    $blowfish
     * @param AES                                         $aes
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        Blowfish $blowfish,
        AES $aes
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->blowfish = $blowfish;
        $this->aes = $aes;
    }

    /**
     * @return Blowfish|AES
     * @throws \Exception
     */
    public function getEncryptionObject()
    {
        if ($this->getConfigParam('encryption') === 'blowfish') {
            return $this->blowfish;
        }

        if ($this->getConfigParam('encryption') === 'aes') {
            return $this->aes;
        }

        throw new \Exception('Invalid encryption method');
    }

    /**
     * @param  string  $plaintext
     * @param  integer $len
     * @param  string  $password
     * @return bool|string
     * @throws \Exception
     */
    public function encrypt($plaintext, $len, $password = false)
    {
        return $this->getEncryptionObject()->ctEncrypt($plaintext, $len, $password);
    }

    /**
     * @param  string  $plaintext
     * @param  integer $len
     * @param  string  $password
     * @return bool|string
     * @throws \Exception
     */
    public function decrypt($data, $len, $password = false)
    {
        return $this->getEncryptionObject()->ctDecrypt($data, $len, $password);
    }
}
