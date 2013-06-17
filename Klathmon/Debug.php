<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

//TODO: chokes on recursion!
//TODO: add exception handler class
abstract class Debug
{
    private static $emailAddress;


    public static function dump(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        echo "\n<div style=\"border:1px solid #ccc;padding:10px;margin:10px;font:14px courier;background:whitesmoke;display:block;border-radius:4px;font-family:monospace;color:#727272\">\n";

        $trace = debug_backtrace(false);
        $line  = @$trace[0]['line'];
        $file  = @$trace[0]['file'];

        echo "Line: <span style=\"color:0099c5;\">{$line}</span> &nbsp; File:<span style=\"color:green;\"> \"{$file}\"</span><br/><br/>";

        //Can't use func_get_args() because it messes up my ability to get the variable name, so i have to fallback to this...
        if (!empty($var1)) {
            echo self::dumpSingle($var1) . "<br/>";
        }
        if (!empty($var2)) {
            echo self::dumpSingle($var2) . "<br/>";
        }
        if (!empty($var3)) {
            echo self::dumpSingle($var3) . "<br/>";
        }
        if (!empty($var4)) {
            echo self::dumpSingle($var4) . "<br/>";
        }
        if (!empty($var5)) {
            echo self::dumpSingle($var5) . "<br/>";
        }

        echo "</div>\n";
    }


    public static function get(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        ob_start();
        self::dump($var1, $var2, $var3, $var4, $var5);
        $htmlOutput = ob_get_clean();

        $noNbsp = str_replace('&nbsp;', ' ', $htmlOutput);
        $noBr   = str_replace('<br/>', "\n", $noNbsp);
        $noTags = strip_tags($noBr);

        return $noTags;
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

    private static function dumpSingle(&$var, $varName = null, $indent = 0, $forObject = false)
    {
        $type       = self::getType($var);
        $parameters = array(&$var, $varName, $indent, $forObject);

        return call_user_func_array("self::dump$type", $parameters); //Need to use array to pass $var by reference
    }

    private static function dumpInteger(&$var, $varName, $indent, $forObject)
    {
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = Integer <span style='color: #0099c5'>$var</span><br/>";

        return $output;
    }

    private static function dumpFloat(&$var, $varName, $indent, $forObject)
    {
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = Float <span style='color: #0099c5'>$var</span><br/>";

        return $output;
    }

    private static function dumpDouble(&$var, $varName, $indent, $forObject)
    {
        return self::dumpFloat($var, $varName, $indent, $forObject);
    }

    private static function dumpBoolean(&$var, $varName, $indent, $forObject)
    {
        $var = ($var === true ? 'TRUE' : 'FALSE');
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = Boolean <span style='color: #92008d'>$var</span><br/>";

        return $output;
    }

    private static function dumpNULL(&$var, $varName, $indent, $forObject)
    {
        $var = 'NULL';
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = NULL <span style='color: #92008d'>$var</span><br/>";

        return $output;
    }

    private static function dumpString(&$var, $varName, $indent, $forObject)
    {
        $length  = strlen($var);
        $dispVar = htmlentities($var);

        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = String(<span style='color: #0099c5;'>$length</span>) <span style='color: green'>\"$dispVar\"</span><br/>";

        return $output;
    }

    private static function dumpArray(&$var, $varName, $indent, $forObject)
    {

        $varName = self::getFormattedVarName($var, $varName);
        $number  = count($var);

        $output = self::getIndent($indent) . "$varName = Array(<span style='color: #0099c5;'>$number</span>) {<br/>";

        foreach ($var as $name => &$item) {
            $output .= self::dumpSingle($item, $name, $indent + 1);
        }

        $output .= self::getIndent($indent) . '}<br/>';

        return $output;
    }

    private static function dumpObject(&$var, $varName, $indent, $forObject)
    {
        $varName = self::getFormattedVarName($var, $varName);
        $reflect = new \ReflectionClass($var);


        $output = self::getIndent($indent)
            . "$varName = Object(<span style='color: #0099c5;'>{$reflect->name}</span>) {<br/>";

        if (count($reflect->getConstants()) != 0) {
            //Constants
            foreach ($reflect->getConstants() as $constName => $value) {
                $nameFormat = "Constant[<span style=\"color: black;\">$constName</style>]";
                $output .= self::dumpSingle($value, $nameFormat, $indent + 1, true);
            }
        }

        if (count($reflect->getProperties()) != 0) {
            //Properties
            foreach ($reflect->getProperties() as $property) {

                $view = ($property->isPrivate() ? 'Private' : ($property->isProtected() ? 'Protected' : 'Public'));

                $property->setAccessible(true);

                $static        = ($property->isStatic() ? ' Static' : '');
                $propertyName  = $property->getName();
                $propertyValue = $property->getValue($var);

                $nameFormat = $view . $static . " <span style=\"color: red;\">$propertyName</span>";

                $output .= self::dumpSingle($propertyValue, $nameFormat, $indent + 1, true);
            }
        } else {
            foreach ($var as $name => $value) {
                $oldErrorHandler = set_error_handler(__NAMESPACE__ . '\specialObjectErrorHandler');
                try {
                    $realValue = (string)$var->{$name};
                } catch (\Exception $e) {
                    $realValue = $value;
                }

                set_error_handler($oldErrorHandler);

                $output .= self::dumpSingle($realValue, $name, $indent + 1, false);
            }
        }

        $output .= self::getIndent($indent) . '}<br/>';

        return $output;
    }

    private static function getIndent($indentNumber = 1)
    {
        $indentText = '&nbsp; &nbsp; &nbsp; &nbsp; ';

        return str_repeat($indentText, $indentNumber);
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

    private static function getFormattedVarName(&$var, $varName)
    {
        if ($varName === null) {
            $output = '<span style="color: red;">$' . self::getVarName($var) . '</span>';
        } else {
            $output = '[\'<span style="color: green;">' . $varName . '</span>\']';
        }

        return $output;
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

function specialObjectErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (E_RECOVERABLE_ERROR === $errno) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
}