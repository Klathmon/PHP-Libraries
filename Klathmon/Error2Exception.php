<?php
/**
 * Created by: Gregory Benner.
 * Date: 7/8/13
 */

namespace Klathmon;

/**
 * Class Error2Exception
 *
 * Converts PHP Errors to Exceptions for all code in between the ::start() and ::stop() functions' use.
 *
 * @package Klathmon
 */
class Error2Exception extends \Exception
{
    protected $types
        = array(
            E_ERROR             => 'ERROR',
            E_WARNING           => 'WARNING',
            E_PARSE             => 'PARSING ERROR',
            E_NOTICE            => 'NOTICE',
            E_CORE_ERROR        => 'CORE ERROR',
            E_CORE_WARNING      => 'CORE WARNING',
            E_COMPILE_ERROR     => 'COMPILE ERROR',
            E_COMPILE_WARNING   => 'COMPILE WARNING',
            E_USER_ERROR        => 'USER ERROR',
            E_USER_WARNING      => 'USER WARNING',
            E_USER_NOTICE       => 'USER NOTICE',
            E_STRICT            => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
        );

    private $errNum, $errStr, $errFile, $errLine, $errContext;


    /**
     * Any code after this is called will throw exceptions instead of errors.
     */
    public static function start()
    {
        set_error_handler(array(__CLASS__, 'handler'), E_ALL);
    }

    /**
     * This stops the conversion and returns the error handler back to the previous one.
     */
    public static function stop()
    {
        restore_error_handler();
    }

    /**
     * Gets a nicely formatted Error string (similar to the string output to the browser when an error occurs)
     *
     * @return string
     */
    public function getFormattedErrorMessage()
    {
        $message = $this->getErrorType() . ': ';
        $message .= $this->getErrorString() . ' ';
        $message .= 'in ' . $this->getErrorFile() . ' ';
        $message .= 'on line ' . $this->getErrorLine() . ' ';

        return $message;
    }


    public function __construct($errNum, $errStr, $errFile, $errLine, $errContext)
    {
        parent::__construct($errStr, $errNum);

        $this->errNum     = $errNum;
        $this->errStr     = $errStr;
        $this->errFile    = $errFile;
        $this->errLine    = $errLine;
        $this->errContext = $errContext;
    }

    public function getErrorType()
    {
        return $this->types[$this->errNum];
    }

    public function getErrorNumber()
    {
        return $this->errNum;
    }

    public function getErrorString()
    {
        return $this->errStr;
    }

    public function getErrorFile()
    {
        return $this->errFile;
    }

    public function getErrorLine()
    {
        return $this->errLine;
    }

    public function getErrorContext()
    {
        return $this->errContext;
    }

    public static function handler($errNum, $errStr, $errFile, $errLine, $errContext)
    {
        throw new Error2Exception($errNum, $errStr, $errFile, $errLine, $errContext);
    }
}