<?php

namespace Fatchip\Nexi\Model\Api\Encryption;

class AES extends Base
{
    /**
     * Encrypt the passed text (any encoding)
     *^
     * @param string $plaintext
     * @param integer $len
     * @param string $password
     * @return bool|string
     */
    public function ctEncrypt($plaintext, $len, $password = false)
    {
        if ($password === false) {
            $password = $this->getPasswordFromConfig();
        }

        // Define the cipher method
        $cipher = "";
        if (strlen($password) == 16) {
            $cipher = "AES-128-CBC";
        } elseif (strlen($password) == 24) {
            $cipher = "AES-192-CBC";
        } elseif (strlen($password) == 32) {
            $cipher = "AES-256-CBC";
        } else {
            throw new \Exception('Password length for ctAESEncrypt must be 16, 24 or 32 Byte.');
        }

        // Use OpenSSl Encryption method to determin IV-length
        $iv_length = openssl_cipher_iv_length($cipher); // 16 Bytes for AES

        // Generate Initializing Vector used for encryption
        $iv = openssl_random_pseudo_bytes($iv_length);

        // Use openssl_encrypt() function to encrypt the data
        $options = OPENSSL_RAW_DATA;
        $encryption = openssl_encrypt($plaintext, $cipher, $password, $options, $iv);

        // Paygate sends $IV and Encrypted Data separated by "-"
        return bin2hex($iv) . "-" . bin2hex($encryption);
    }

    /**
     * Encrypt the passed text (any encoding)
     *^
     * @param string  $data
     * @param integer $len
     * @param string  $password
     * @return array
     */
    public function ctDecrypt($data, $len, $password = false)
    {
        if ($password === false) {
            $password = $this->getPasswordFromConfig();
        }

        // Define the cipher method
        $cipher = "";
        if (strlen($password) == 16) {
            $cipher = "AES-128-CBC";
        } elseif (strlen($password) == 24) {
            $cipher = "AES-192-CBC";
        } else if (strlen($password) == 32) {
            $cipher = "AES-256-CBC";
        } else {
            throw new \Exception('Password length for ctAESDecrypt must be 16, 24 or 32 Byte.');
        }

        // Use OpenSSl Encryption method to determin IV-length
        $iv_length = openssl_cipher_iv_length($cipher) * 2; // Multiplied by 2 because Data is HEX-encoded, 32 (16 * 2) for AES

        // Extract IV from beginning of Data
        $iv = mb_substr($data, 0, $iv_length);

        // Extract encrypted from Data after IV
        $encrypted = mb_substr($data, $iv_length +1); // IV in front, then "-", then encrypted Data until end of Data

        // Use openssl_decrypt() function to decrypt the data
        $options = OPENSSL_RAW_DATA;
        $decrypted = openssl_decrypt(hex2bin($encrypted), $cipher, $password, $options, hex2bin($iv));
        $decryptedArray = $this->ctSplit($decrypted);
        return $decryptedArray;
    }
}
