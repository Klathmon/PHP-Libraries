<?php
/**
 * Created by Gregory Benner
 * Date: 6/15/2012
 */

namespace Klathmon;

/**
 * Class Sanitize
 *
 * This is a sensitization class, it can be used 2 ways:
 * 1. Create an instance of the class with a string of characters in the constructor.
 *      Then call removeCharacters() or removeAllExceptCharacters() to return the respective values
 * 2. Call a predefined static class to clean/sanitize data according to those.
 *
 * @package Klathmon
 */
class Sanitize
{
    /**
     * @var string[]
     */
    private $characterList;

    /**
     * This is a string of characters to be used for non-static functions.
     *
     * @param string $characters
     *
     * @return \Klathmon\Sanitize
     */
    public function __construct($characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $this->setCharacterList($characters);
    }

    /**
     * Will remove all of the characters in $this->characterList
     *
     * @param string $data
     *
     * @return string
     */
    public function removeCharacters($data)
    {
        self::checkIfString($data);

        $output = str_replace($this->characterList, '', $data);

        return $output;
    }

    /**
     * Will remove all characters, except the ones in $this->characterList
     *
     * @param string $data
     *
     * @return string
     */
    public function removeAllExceptCharacters($data)
    {
        self::checkIfString($data);

        $output = '';
        foreach (str_split($data) as $character) {
            if (in_array($character, $this->characterList)) {
                $output .= $character;
            }
        }

        return $output;
    }

    /**
     * Will add the characters in $characters to $this->characterList
     *
     * @param string $characters
     */
    public function addToCharacterList($characters)
    {
        self::checkIfString($characters);

        $characterList = implode($this->characterList);
        $characterList .= $characters;
        $this->setCharacterList($characterList);
    }

    /**
     * Sets $this->characterList to the string provided (removes all pre-existing characters in the list
     *
     * @param string $characters
     */
    public function setCharacterList($characters)
    {
        self::checkIfString($characters);

        $this->characterList = str_split($characters);
    }


    /**
     * Adds slashes to all dangerous characters.
     *
     * @param string $sql
     *
     * @return string
     */
    public static function addSlashes($sql)
    {
        self::checkIfString($sql);

        $output = addslashes($sql);

        return $output;
    }

    /**
     * Removes all slashes that were added with self::addSlashes()
     *
     * @param string $sql
     *
     * @return string
     */
    public static function stripSlashes($sql)
    {
        self::checkIfString($sql);

        $output = stripslashes($sql);

        return $output;
    }

    /**
     * Encodes the input with htmlentities.
     *
     * @param string $html
     *
     * @return string
     */
    public static function htmlEncode($html)
    {
        self::checkIfString($html);

        $output = htmlentities($html);

        return $output;
    }

    /**
     * Decodes the input (resores all html characters)
     *
     * @param string $html
     *
     * @return string
     */
    public static function htmlDecode($html)
    {
        self::checkIfString($html);

        $output = html_entity_decode($html);

        return $output;
    }

    /**
     * Attempts to convert a string to an integer. Please note that unexpected results may come from trying to convert
     * decimals and other strings with numbers, letters, and alphabetical characters dispersed throughout.
     *
     * @param mixed $integer
     *
     * @return int
     */
    public static function cleanInteger($integer)
    {
        $output = intval($integer);

        return $output;
    }

    /**
     * Attempts to convert a string to a float. Please note that unexpected results may come from trying to convert
     * strings with numbers, letters, and alphabetical characters dispersed throughout.
     *
     * @param mixed $float
     *
     * @return float
     */
    public static function cleanFloat($float)
    {
        $output = floatval($float);

        return $output;
    }

    /**
     * Returns the input with all non-alphanumeric characters removed, spaces are not removed.
     *
     * @param string $string
     *
     * @return string
     */
    public static function cleanAlphaNumeric($string)
    {
        self::checkIfString((string)$string);

        $output = preg_replace("/[^A-Za-z0-9 ]/", '', $string);

        return $output;
    }

    /**
     * Returns the string with all non-alphanumeric characters removed, except for '-', '_', and spaces ' '.
     *
     * @param string $string
     *
     * @return string
     */
    public static function cleanAlphaNumericPlus($string)
    {
        self::checkIfString((string)$string);

        $output = preg_replace("/[^A-Za-z0-9 -_]/", '', $string);

        return $output;
    }


    /**
     * Will throw an exception if the data given is not a string
     *
     * @param string $data
     *
     * @throws \Exception
     */
    private static function checkIfString($data)
    {
        if (!is_string($data)) {
            throw new \Exception('Input needs to be a string!');
        }
    }

}