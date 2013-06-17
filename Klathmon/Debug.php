<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

//TODO: chokes on recursion!
abstract class Debug
{
    private static $emailAddress;


    public static function dump(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        echo "\n<div style=\"border:1px solid #ccc;padding:10px;margin:10px;font:14px courier;background:whitesmoke;display:block;border-radius:4px;\">\n";

        $trace = debug_backtrace(false);
        $line  = @$trace[0]['line'];
        $file  = @$trace[0]['file'];

        echo <<<HTML
Line: <span style="color:green;">{$line}</span> &nbsp; File:<span style="color:green;"> "{$file}"</span><br/>
HTML;

        //Can't use func_get_args() because it messes up my ability to get the variable name, so i have to fallback to this...
        if (!empty($var1)) {
            echo self::singleDump($var1) . "<br/>";
        }
        if (!empty($var2)) {
            echo self::singleDump($var2) . "<br/>";
        }
        if (!empty($var3)) {
            echo self::singleDump($var3) . "<br/>";
        }
        if (!empty($var4)) {
            echo self::singleDump($var4) . "<br/>";
        }
        if (!empty($var5)) {
            echo self::singleDump($var5) . "<br/>";
        }

        echo "</div>\n";
    }


    public static function get(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        $trace = debug_backtrace(false);
        $line  = @$trace[0]['line'];
        $file  = @$trace[0]['file'];

        $output = "Line: {$line} File: \"{$file}\"\n";

        if (!empty($var1)) {
            $output .= self::singleDump($var1, false) . "\n";
        }
        if (!empty($var2)) {
            $output .= self::singleDump($var2, false) . "\n";
        }
        if (!empty($var3)) {
            $output .= self::singleDump($var3, false) . "\n";
        }
        if (!empty($var4)) {
            $output .= self::singleDump($var4, false) . "\n";
        }
        if (!empty($var5)) {
            $output .= self::singleDump($var5, false) . "\n";
        }

        return $output;
    }

    public static function email(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        if (isset(self::$emailAddress)) {
            ob_start();
            self::dump($var1, $var2, $var3, $var4, $var5);
            $output = ob_get_clean();

            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; chrset=UTF-8' . "\r\n";

            mail(self::$emailAddress, 'Email Dump', $output, $headers);
        } else {
            throw new \Exception('No Email Address Set! Set an email address with ' . __CLASS__
            . '::setEmail($address);');
        }
    }

    public static function setEmail($address)
    {
        self::$emailAddress = $address;
    }


    private static function singleDump(&$var, $html = true, $varNameSet = false, $indent = 1)
    {
        $green  = 'green';
        $red    = 'red';
        $gray   = '#a2a2a2';
        $purple = '#92008d';
        $blue   = '#0099c5';

        $tab = ($html ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ');
        $lb  = ($html ? '<br/>' : "\n");

        $type        = self::getType($var);
        $length      = self::getLength($var);
        $varName     = ($varNameSet === false ? self::getVarName($var) : $varNameSet);
        $doubleQuote = (is_string($var) ? '"' : '');
        $singleQuote = ($varNameSet !== false ? '\'' : '');
        $dollarSign  = ($varNameSet === false ? '$' : '');
        $varColor    = ($dollarSign == '$' ? $red : $purple);

        $var = ($var === true ? 'TRUE' : $var);
        $var = ($var === false ? 'FALSE' : $var);

        if ($html) {
            $output
                = <<<HTML
[<span style="color: $varColor;">$dollarSign{$singleQuote}$varName{$singleQuote}</span>] = 
<span style="color: $gray;">$type(</span><span style="color: $blue;">$length</span><span style="color: $gray;">)</span> 
HTML;
        } else {
            $output = "[$dollarSign{$singleQuote}$varName{$singleQuote}] = $type($length) ";
        }

        if (is_array($var) || is_object($var)) {
            $output .= "{{$lb}";
            foreach ($var as $name => $item) {
                $output .= str_repeat($tab, $indent);
                $temp = & $item;
                $output .= call_user_func(__METHOD__, $temp, $html, $name, $indent + 1);
            }
            $output .= str_repeat($tab, $indent - 1) . "}{$lb}";
        } else {
            if ($html) {
                $output .= "<span style=\"color: $green;\">{$doubleQuote}$var{$doubleQuote}</span>$lb";
            } else {
                $output .= "{$doubleQuote}$var{$doubleQuote}$lb";
            }
        }

        return $output;
    }

    private static function getLength(&$var)
    {
        if (is_array($var)) {
            $length = count($var);
        } elseif (is_object($var)) {
            $length = 0;
            foreach ($var as $thing) {
                $length++;
            }
        } else {
            $length = strlen($var);
        }

        return $length;
    }

    private static function getType(&$var)
    {
        if (is_array($var)) {
            $type = 'Array';
        } elseif (is_object($var)) {
            $type = 'Object';
        } else {
            $type = ucfirst(gettype($var));
        }

        return $type;
    }

    private static function getVarName(&$var, $scope = false, $prefix = 'UNIQUE', $suffix = 'VARIABLE')
    {
        if ($scope) {
            $vals = $scope;
        } else {
            $vals = $GLOBALS;
        }
        $old   = $var;
        $var   = $new = $prefix . rand() . $suffix;
        $vname = false;
        foreach ($vals as $key => $val) {
            if ($val === $new) {
                $vname = $key;
            }
        }
        $var = $old;

        return $vname;
    }
}