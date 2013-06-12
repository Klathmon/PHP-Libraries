<?php
/**
 * Created by: Gregory Benner.
 * Date: 2/22/2013
 */

namespace Klathmon\Crypt;

/**
 * Class Symmetric
 *
 * My encryption class
 *
 * Main methods:
 * encrypt($data, $cipher, $mode, $rounds, $saltSize, $macHash, $pbkdf2Hash, $randomSource)
 * decrypt($data)
 * static createKeyFile($keyFileLocation, $size, $randomSource)
 * static combineKeys($userKey, $keyFile, $hash)
 *
 * @package Klathmon
 */
class Symmetric
{
    /**
     * @var string the key used for encryption.
     */
    private $key;

    /**
     * Combines a keyFile and the users' key with the provided hashing algorithm.
     *
     * @param string $userKey
     * @param string $keyFile
     * @param string $hash
     *
     * @return string
     */
    public static function combineKeys($userKey, $keyFile, $hash = 'SHA512')
    {
        $serverKey = file_get_contents($keyFile);

        $key = hash_hmac($hash, $userKey, $serverKey, true);

        return $key;
    }

    /**
     * Creates a keyFile at specified location of specified size.
     * *Using MCRYPT_DEV_RANDOM as a source can lead to extremely long wait times for key generation*
     *
     * @param string $keyFileLocation
     * @param int    $size
     * @param int    $randomSource
     */
    public static function createKeyFile($keyFileLocation, $size = 1024, $randomSource = MCRYPT_DEV_URANDOM)
    {
        $keyData = mcrypt_create_iv($size, $randomSource);

        file_put_contents($keyFileLocation, $keyData, LOCK_EX);
    }


    /**
     * Sets up the encrypt() and decrypt() functions to use $key.
     *
     * @param string $key
     *
     * @throws \Exception 1: Key is empty or not a string.
     */
    public function __construct($key)
    {
        if (!isset($key) || !is_string($key)) {
            throw new \Exception('Key is empty or is not a string!', 1);
        }

        $this->key = $key;
    }


    /**
     * Will encrypt data using the values provided, and outputs a single string that is database safe.
     *
     * TODO: add checks to see if given hashing algorithms are supported on this server.
     *
     * @param string $data
     * @param string $cipher
     * @param string $mode
     * @param int    $rounds
     * @param int    $saltSize
     * @param string $macHash
     * @param string $pbkdf2Hash
     * @param int    $randomSource
     *
     * @return string
     * @throws \Exception 1: Data is empty or not a string
     */
    public function encrypt(
        $data, $cipher = MCRYPT_RIJNDAEL_128, $mode = MCRYPT_MODE_CBC, $rounds = 50, $saltSize = 128,
        $macHash = 'sha512', $pbkdf2Hash = 'sha512', $randomSource = MCRYPT_DEV_URANDOM
    )
    {
        if (!isset($data) || !is_string($data)) {
            throw new \Exception('Data is empty or is not a string!', 1);
        }

        $salt = $this->generateSalt($saltSize, $randomSource);
        list ($cipherKey, $macKey, $iv) = $this->stretchKey($this->key, $salt, $cipher, $mode, $rounds, $pbkdf2Hash);

        $decrypted = $this->pad($data, $cipher, $mode);

        $encrypted = mcrypt_encrypt($cipher, $cipherKey, $decrypted, $mode, $iv);

        $mac = $this->getMac($encrypted, $macKey, $macHash, $cipher, $mode);

        $output = $this->encodeCipherText($salt, $encrypted, $mac, $macHash, $pbkdf2Hash, $rounds, $cipher, $mode);

        return $output;
    }

    /**
     * Decrypts the data given, will work on any data that was encrypted with this class, regardless of the orignal
     * settings used, the only thing that needs to be the same is the key.
     *
     * @param string $data
     *
     * @return string
     * @throws \Exception 1: Data is empty or is not a string. 2: MAC Error, data is corrupt or has been tampered with.
     */
    public function decrypt($data)
    {
        if (!isset($data) || !is_string($data)) {
            throw new \Exception('Data is empty or is not a string!', 1);
        }

        list($salt, $encrypted, $mac, $macHash, $pbkdf2Hash, $rounds, $cipher, $mode)
            = $this->decodeCipherText(trim($data));

        list ($cipherKey, $macKey, $iv) = $this->stretchKey($this->key, $salt, $cipher, $mode, $rounds, $pbkdf2Hash);

        if (!$this->macMatches($mac, $encrypted, $macKey, $macHash, $cipher, $mode)) {
            throw new \Exception('MAC does not match data!', 2);
        }

        $decrypted = mcrypt_decrypt($cipher, $cipherKey, $encrypted, $mode, $iv);

        $output = $this->unpad($decrypted, $cipher, $mode);

        return $output;
    }


    /**
     * Encodes the given values into a formatted string that can be stored in a database.
     *
     * Output Format:
     * Base64[saltSize|macSize|rounds|macHashAlgo|pbkdf2HashAlgo|cipher|mode|Encrypted[(plaintext)(cipher)(mode)](mac)(salt)]
     *
     * TODO: add a version number here for backwards compatibility.
     *
     * @param string $salt
     * @param string $encrypted
     * @param string $mac
     * @param string $macHash
     * @param string $pbkdf2Hash
     * @param int    $rounds
     * @param string $cipher
     * @param string $mode
     *
     * @return string
     */
    private function encodeCipherText($salt, $encrypted, $mac, $macHash, $pbkdf2Hash, $rounds, $cipher, $mode)
    {
        $saltSize = strlen($salt);
        $macSize  = strlen($mac);

        $data = $saltSize . '|';
        $data .= $macSize . '|';
        $data .= $rounds . '|';
        $data .= $macHash . '|';
        $data .= $pbkdf2Hash . '|';
        $data .= $cipher . '|';
        $data .= $mode . '|';

        $data .= $encrypted . $mac . $salt;

        return base64_encode($data);
    }

    /**
     * Decodes the given cipherText, and returns an array of values to be used in the class.
     *
     * TODO: add version number parsing.
     *
     * @param string $data
     *
     * @return array
     * @throws \Exception 1: Data string is not correctly formatted.
     */
    private function decodeCipherText($data)
    {
        $data = base64_decode($data);

        list($saltSize, $macSize, $rounds, $macHash, $pbkdf2Hash, $cipher, $mode, $data) = explode('|', $data, 8);

        $encrypted = substr($data, 0, -($macSize + $saltSize));
        $mac       = substr($data, -($macSize + $saltSize), -$saltSize);
        $salt      = substr($data, -$saltSize);


        $return = array($salt, $encrypted, $mac, $macHash, $pbkdf2Hash, $rounds, $cipher, $mode);

        for ($x = 0; $x != 8; $x++) {
            $item = $return[$x];
            if (!isset($item) || !is_string($item)) {
                throw new \Exception('Incorrectly formatted string', 1);
            }
        }

        return $return;
    }

    /**
     * Generates a salt with given size and source.
     *
     * @param int $saltSize
     * @param int $randomSource
     *
     * @return string
     */
    private function generateSalt($saltSize, $randomSource)
    {
        return mcrypt_create_iv($saltSize, $randomSource);
    }

    /**
     * Stretches the key and salt to get cipherKey, macKey and IV.
     *
     * @param string $key
     * @param string $salt
     * @param string $cipher
     * @param string $mode
     * @param int    $rounds
     * @param string $pbkdf2Hash
     *
     * @return string[]
     */
    private function stretchKey($key, $salt, $cipher, $mode, $rounds, $pbkdf2Hash)
    {
        $ivSize  = mcrypt_get_iv_size($cipher, $mode);
        $keySize = mcrypt_get_key_size($cipher, $mode);
        $length  = 2 * $keySize + $ivSize;

        $key = $this->pbkdf2($pbkdf2Hash, $key, $salt, $rounds, $length);

        $cipherKey = substr($key, 0, $keySize);
        $macKey    = substr($key, $keySize, $keySize);
        $iv        = substr($key, 2 * $keySize);

        return array($cipherKey, $macKey, $iv);
    }

    /**
     * Returns the MAC of the encrypted text.
     *
     * @param string $encrypted
     * @param string $key
     * @param string $hash
     * @param string $cipher
     * @param string $mode
     *
     * @return string
     */
    private function getMAC($encrypted, $key, $hash, $cipher, $mode)
    {
        return hash_hmac($hash, $cipher . $mode . $encrypted, $key, true);
    }

    /**
     * A standard PBKDF2 function pulled from PHP 5.5
     *
     * @param string $hash
     * @param string $key
     * @param string $salt
     * @param int    $rounds
     * @param int    $length
     *
     * @return string
     */
    private function pbkdf2($hash, $key, $salt, $rounds, $length)
    {
        $size   = strlen(hash($hash, '', true));
        $len    = ceil($length / $size);
        $result = '';
        for ($i = 1; $i <= $len; $i++) {
            $tmp = hash_hmac($hash, $salt . pack('N', $i), $key, true);
            $res = $tmp;
            for ($j = 1; $j < $rounds; $j++) {
                $tmp = hash_hmac($hash, $tmp, $key, true);
                $res ^= $tmp;
            }
            $result .= $res;
        }

        return substr($result, 0, $length);
    }

    /**
     * Checks if the old MAC and the new data match. This function is secure against timing attacks.
     *
     * @param string $oldMac
     * @param string $encrypted
     * @param string $macKey
     * @param string $macHash
     * @param string $cipher
     * @param string $mode
     *
     * @return bool
     */
    private function macMatches($oldMac, $encrypted, $macKey, $macHash, $cipher, $mode)
    {
        $hash = 'MD5';

        $newMac = $this->getMAC($encrypted, $macKey, $macHash, $cipher, $mode);

        $oldMacMd5 = hash($hash, $oldMac, true);
        $newMacMd5 = hash($hash, $newMac, true);

        return $this->hashCompareSecure($oldMacMd5, $newMacMd5);
    }

    /**
     * A timing-attack proof way of comparing 2 strings.
     *
     * @param string $a
     * @param string $b
     *
     * @return bool
     */
    private function hashCompareSecure($a, $b)
    {
        $lena = strlen($a);
        $lenb = strlen($b);
        if ($lena !== $lenb) {
            return false;
        }
        $match = true;
        for ($i = 0; $i < $lena; $i++) {
            $match = $match && ((ord($a[$i]) ^ ord($b[$i])) === 0);
        }

        return $match;
    }

    /**
     * Pads the data given using the PKCS7 method.
     *
     * @param string $data
     * @param string $cipher
     * @param string $mode
     *
     * @return string
     */
    private function pad($data, $cipher, $mode)
    {
        $length    = mcrypt_get_block_size($cipher, $mode);
        $padAmount = $length - strlen($data) % $length;
        if ($padAmount == 0) {
            $padAmount = $length;
        }

        return $data . str_repeat(chr($padAmount), $padAmount);
    }

    /**
     * Unpads the data using the PKCS7 method.
     *
     * @param string $data
     * @param string $cipher
     * @param string $mode
     *
     * @return string
     * @throws \Exception 1: Error un-padding data.
     */
    private function unpad($data, $cipher, $mode)
    {
        $length = mcrypt_get_block_size($cipher, $mode);
        $last   = ord($data[strlen($data) - 1]);
        if ($last > $length) {
            throw new \Exception('Error Un-Padding data', 1);
        }
        if (substr($data, -1 * $last) !== str_repeat(chr($last), $last)) {
            throw new \Exception('Error Un-Padding data', 1);
        }

        return substr($data, 0, -1 * $last);
    }
}
