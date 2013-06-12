<?php
/**
 * Created by: Gregory Benner.
 * Date: 2/12/2013
 */

namespace Klathmon\Crypt;

/**
 * Class Hash
 *
 * Basic password-hashing class.
 *
 * @package Klathmon
 */
class Hash
{
    /**
     * @var int The random data source location
     */
    private static $randomConstant = MCRYPT_DEV_URANDOM;

    /**
     * @var int The difficulty integer used in hashing
     */
    /**
     * @var string The encryption algorithm
     */
    private $difficulty, $algorithm;

    /**
     * @var string Format of the hash
     */
    /**
     * @var int Length of the salt itself
     */
    /**
     * @var int Maximum length of the resulting salt (all things included)
     */
    private $hashFormat, $saltLength, $saltMaxLength;

    /**
     *
     * @param int    $difficulty The difficulty level (differs for each algorithm)
     * @param string $algorithm  The CRYPT constant that represents the algorithm you want to use (in string form)
     *
     * @throws \Exception 1: Crypt is not available. 2: Algorithm is not supported. 3: Invalid Difficulty. 4: Invalid Algorithm.
     */
    public function __construct($difficulty = 10, $algorithm = 'CRYPT_BLOWFISH')
    {
        $this->difficulty = $difficulty;
        $this->algorithm  = $algorithm;

        if (!function_exists('crypt')) {
            throw new \Exception('Crypt must be loaded for ' . __CLASS__ . '.', 1);
        } elseif (constant($this->algorithm) != 1) {
            throw new \Exception('Algorithm (' . $this->algorithm . ') Not Supported!', 2);
        }

        switch ($this->algorithm) {

        case 'CRYPT_STD_DES':

            $this->hashFormat    = '';
            $this->saltLength    = 2;
            $this->saltMaxLength = 2;
            break;

        case 'CRYPT_EXT_DES':

            if (($this->difficulty < 1) || ($this->difficulty > 16777215)) {
                throw new \Exception('Invalid Difficulty! Acceptable range is between 1 and 16,777,215', 3);
            }
            $encodedIterations   = $this->base64_int_encode($this->difficulty);
            $this->hashFormat    = '_' . $encodedIterations;
            $this->saltLength    = 4;
            $this->saltMaxLength = 9;
            break;

        case 'CRYPT_MD5':

            $this->hashFormat    = '$1$';
            $this->saltLength    = 10;
            $this->saltMaxLength = 12;
            break;

        case 'CRYPT_BLOWFISH':

            if (($this->difficulty < 4) || ($this->difficulty > 31)) {
                throw new \Exception('Invalid Difficulty! Acceptable range is between 4 and 31.', 3);
            }
            $this->hashFormat    = sprintf("$2y$%02d$", $this->difficulty);
            $this->saltLength    = 22;
            $this->saltMaxLength = 30;
            break;

        case 'CRYPT_SHA256':
        case 'CRYPT_SHA512':

            if (($this->difficulty < 1000) || ($this->difficulty > 999999999)) {
                throw new \Exception('Invalid Difficulty! Acceptable range is between 1000 and 999,999,999.', 3);
            }
            $hashNumber          = ($this->algorithm == 'CRYPT_SHA256' ? 5 : 6);
            $this->hashFormat    = '$' . $hashNumber . '$rounds=' . (string)$this->difficulty . '$';
            $this->saltLength    = 16;
            $this->saltMaxLength = 16 + strlen($this->hashFormat);
            break;

        default:
            throw new \Exception('Invalid Algorithm!', 4);

        }
    }

    /**
     * Provide the password, and save the string, nothing else is needed.
     *
     * @param string $password The password you want to safely hash.
     *
     * @return string The full string you should store of the hashed password.
     * @throws \Exception 1: $password was blank.
     */
    public function hash($password)
    {

        if (!isset($password)) {
            throw new \Exception('$password must be set!', 1);
        }
        $rawSalt  = mcrypt_create_iv($this->saltLength, self::$randomConstant);
        $longSalt = $this->hashFormat . str_replace('+', '.', base64_encode($rawSalt)) . '$';
        $salt     = substr($longSalt, 0, $this->saltMaxLength);

        return crypt($password, $salt);
    }

    /**
     * Run this to verify if a give password matches a given hash.
     *
     * @param string $password The password you want to check against.
     * @param string $hash     The hash you want to check against.
     *
     * @return bool TRUE if it matches, FALSE otherwise.
     * @throws \Exception 1: $password empty. 2: $hash empty.
     */
    public function verify($password, $hash)
    {
        if (!isset($password)) {
            throw new \Exception('$password must be set!', 1);
        } elseif (!isset($hash)) {
            throw new \Exception('$hash must be set!', 2);
        }


        $check = crypt($password, $hash);

        //TODO: Possible timing attack here! FIX IT NOW!
        if ($check == $hash) {
            $verified = true;
        } else {
            $verified = false;
        }

        return $verified;
    }

    /**
     * Run this function to get a TRUE/FALSE on if the password is hashed with the correct algorithm and level of difficulty.
     *
     * @param string $hash The hash to check
     *
     * @return bool TRUE if it needs rehash, FALSE otherwise.
     */
    public function needsRehash($hash)
    {
        $hashAlgorithm  = self::getAlgorithm($hash);
        $hashDifficulty = self::getDifficulty($hash);

        if ($hashAlgorithm != $this->algorithm) {
            $rehash = true;
        } elseif (($hashDifficulty != false) && ($hashDifficulty < $this->difficulty)) {
            $rehash = true;
        } else {
            $rehash = false;
        }

        return $rehash;
    }

    /**
     * Returns the algorithm used to hash the password (as a string of the constant)
     *
     * @param string $hash The hash to check
     *
     * @return string The string of the constant of the algorithm used.
     */
    public static function getAlgorithm($hash)
    {
        $id = self::getIdString($hash);
        switch ($id) {
        case false;
            $algorithm = 'CRYPT_STD_DES';
            break;
        case '_';
            $algorithm = 'CRYPT_EXT_DES';
            break;
        case '$1$':
            $algorithm = 'CRYPT_MD5';
            break;
        case '$2a$':
        case '$2x$':
        case '$2y$':
            $algorithm = 'CRYPT_BLOWFISH';
            break;
        case '$5$':
            $algorithm = 'CRYPT_SHA256';
            break;
        case '$6$':
            $algorithm = 'CRYPT_SHA512';
        }

        return $algorithm;
    }

    /**
     * Retrieve the salt from the hash.
     *
     * @param string $hash The hash to retrieve the salt from.
     *
     * @return string The salt
     */
    public static function getSalt($hash)
    {
        $algorithm = self::getAlgorithm($hash);

        switch ($algorithm) {
        case 'CRYPT_STD_DES':
            $strLength = 2;
            break;
        case 'CRYPT_EXT_DES':
            $strLength = 9;
            break;
        case 'CRYPT_MD5':
            //$1$rasmusle$
            $strLength = 12;
            break;
        case 'CRYPT_BLOWFISH':
            //$2a$07$usesomesillystringforsalt$
            $strLength = 30;
            break;
        case 'CRYPT_SHA256':
        case 'CRYPT_SHA512':
            //$5$rounds=5000$usesomesillystringforsalt$
            $strLength = strrpos($hash, '$');
        }
        $hash = substr($hash, 0, $strLength);

        return $hash;
    }

    /**
     * Returns the integer of the difficulty used to hash the password (returns false if no difficulty can be used in that algorithm)
     *
     * @param string $hash The hash to get the difficulty from.
     *
     * @return bool|int The difficulty (FALSE if no difficulty can be used in that algorithm)
     */
    public static function getDifficulty($hash)
    {
        $algorithm = self::getAlgorithm($hash);

        switch ($algorithm) {
        case 'CRYPT_STD_DES':
        case 'CRYPT_EXT_DES':
        case 'CRYPT_MD5':
            $difficulty = false;
            break;
        case 'CRYPT_BLOWFISH':
            $salt       = self::getSalt($hash);
            $difficulty = (int)substr($salt, 4, 2);
            break;
        case 'CRYPT_SHA256':
        case 'CRYPT_SHA512':
            $salt      = self::getSalt($hash);
            $roundsPos = strpos($salt, 'rounds=');
            $SPos      = strpos($salt, '$', $roundsPos);

            $difficulty = (int)substr($salt, ($roundsPos + 7), $SPos);
        }

        return $difficulty;
    }

    /**
     * Returns the ID string of the salt of the hash of the password. EX: "$2y$" for CRYPT_BLOWFISH
     *
     * @param string $hash The hash to get the IdString from
     *
     * @return bool|string False if there is none (CRYPT_STD_DES) or the IdString otherwise.
     */
    public static function getIdString($hash)
    {

        $firstPos = strpos($hash, '$');
        if ($firstPos !== false) {
            $secondPos = strpos($hash, '$', $firstPos + 1) + 1;
            $idLength  = $secondPos - $firstPos;
            $id        = substr($hash, $firstPos, $idLength);
        } elseif (substr($hash, 0, 1) == '_') {
            $id = '_';
        } else {
            $id = false;
        }

        return $id;
    }

    /**
     * Base64 encode an integer for use in the CRYPT_EXT_DES iterations. Takes an integer and returns a 4 character string.
     * THIS DOES NO VALIDATION/CHECKING OF THE INT OR RETURNED STRING!
     *
     * @param int $num The number to convert
     *
     * @return string The base64'd string (4 characters)
     */
    private function base64_int_encode($num)
    {
        $alphabet_raw = "./0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $alphabet     = str_split($alphabet_raw);
        $arr          = array();
        $base         = sizeof($alphabet);
        while ($num) {
            $rem   = $num % $base;
            $num   = (int)($num / $base);
            $arr[] = $alphabet[$rem];
        }

        $arr    = array_reverse($arr);
        $string = implode($arr);

        return str_pad($string, 4, '.', STR_PAD_LEFT);
    }
}