<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

/**
 * Class Debug
 *
 * A nicer debugging class.
 *
 * ***DO NOT USE THIS IN PRODUCTION CODE FOR ANY REASON! IT IS MOST LIKELY FULL OF SPEED/SECURITY ISSUES!***
 *
 * Usage:
 * Debug::dump($var); //echos the information.
 * Debug::setEmail('LarryPage@google.com'); //Set the email address for ::email()
 * Debug::email(); //Send the data in an email
 * Debug::get(); //Returns a plain-text version of the data (no html)
 *
 * @package Klathmon
 */
abstract class Debug
{
    private static $emailBacktrace = false;

    /**
     * @var string
     */
    private static $emailAddress;


    /**
     * Output the dump. (limit 5 variables because of limitation in func_get_args())
     *
     * @param mixed $var1
     * @param mixed $var2
     * @param mixed $var3
     * @param mixed $var4
     * @param mixed $var5
     */
    public static function dump(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        echo "\n<div style=\"border:1px solid #ccc;padding:10px;margin:10px;font:14px courier;background:whitesmoke;display:block;border-radius:4px;font-family:monospace;color:#727272\">\n";

        $trace = debug_backtrace(false);
        if (self::$emailBacktrace) {
            $line = @$trace[1]['line'];
            $file = @$trace[1]['file'];
        } else {
            $line = @$trace[0]['line'];
            $file = @$trace[0]['file'];
        }

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

    /**
     * Return the dump (unformatted). (limit 5 variables because of limitation in func_get_args())
     *
     * @param mixed $var1
     * @param mixed $var2
     * @param mixed $var3
     * @param mixed $var4
     * @param mixed $var5
     *
     * @return string
     */
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

    /**
     * Email the dump to the address set with ::setEmail(). (limit 5 variables because of limitation in func_get_args())
     *
     * @param mixed $var1
     * @param mixed $var2
     * @param mixed $var3
     * @param mixed $var4
     * @param mixed $var5
     *
     * @throws \Exception
     */
    public static function email(&$var1, &$var2 = '', &$var3 = '', &$var4 = '', &$var5 = '')
    {
        if (isset(self::$emailAddress)) {
            self::$emailBacktrace = true;
            ob_start();
            self::dump($var1, $var2, $var3, $var4, $var5);
            $output               = ob_get_clean();
            self::$emailBacktrace = false;

            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; chrset=UTF-8' . "\r\n";

            mail(self::$emailAddress, 'Email Dump', $output, $headers);
        } else {
            throw new \Exception('No Email Address Set! Set an email address with ' . __CLASS__
            . '::setEmail($address);');
        }
    }

    /**
     * Sets the email address for ::email()
     *
     * @param string $address
     */
    public static function setEmail($address)
    {
        self::$emailAddress = $address;
    }


    /**
     * Takes any variable and calls the correct dump* function to format it correctly.
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return mixed
     */
    private static function dumpSingle(&$var, $varName = null, $indent = 0, $forObject = false)
    {
        $type       = self::getType($var);
        $parameters = array(&$var, $varName, $indent, $forObject);

        return call_user_func_array("self::dump$type", $parameters); //Need to use array to pass $var by reference
    }

    /**
     * Formats an Integer
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
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

    /**
     * Formats a Float
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
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

    /**
     * Formats a Double (just calls dumpFloat)
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
    private static function dumpDouble(&$var, $varName, $indent, $forObject)
    {
        return self::dumpFloat($var, $varName, $indent, $forObject);
    }

    /**
     * Formats a Boolean (outputs it as human readable TRUE or FALSE)
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
    private static function dumpBoolean(&$var, $varName, $indent, $forObject)
    {
        $dispVar = ($var === true ? 'TRUE' : 'FALSE');
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = Boolean <span style='color: #92008d'>$dispVar</span><br/>";

        return $output;
    }

    /**
     * Formats a NULL value
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
    private static function dumpNULL(&$var, $varName, $indent, $forObject)
    {
        $dispVar = 'NULL';
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = NULL <span style='color: #92008d'>$dispVar</span><br/>";

        return $output;
    }

    /**
     * Formats a Resource
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
    private static function dumpResource(&$var, $varName, $indent, $forObject)
    {
        $dispVar = get_resource_type($var);
        if ($forObject) {
            $output = self::getIndent($indent) . $varName;
        } else {
            $varName = self::getFormattedVarName($var, $varName);
            $output  = self::getIndent($indent) . $varName;
        }

        $output .= " = Resource <span style='color: #92008d'>$dispVar</span><br/>";

        return $output;
    }

    /**
     * Formats a String
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
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

    private static function dumpRecursion(&$var, $varName, $indent, $forObject = false)
    {
        $output = self::getIndent($indent + 1) . self::getFormattedVarName($var, $varName);
        $output .= " = <span style=\"color: red;\">*RECURSION*</span></br>";

        return $output;
    }

    /**
     * Formats an Array (and all elements in the array)
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject If this is TRUE, $varName will be output directly (and not re-formatted at all). For use
     *                         in formatting objects' things.
     *
     * @return string
     */
    private static function dumpArray(&$var, $varName, $indent, $forObject)
    {
        $varName = self::getFormattedVarName($var, $varName);
        $number  = count($var);

        $output = self::getIndent($indent) . "$varName = Array(<span style='color: #0099c5;'>$number</span>) {<br/>";

        foreach ($var as $name => &$item) {
            if (is_array($item) && self::isRecursiveArray($item)) {
                $output .= self::dumpRecursion($item, $name, $indent + 1);
            } else {
                $output .= self::dumpSingle($item, $name, $indent + 1);
            }
        }

        $output .= self::getIndent($indent) . '}<br/>';

        return $output;
    }

    /**
     * Formats an Object
     * This one has a ton of custom code and relies heavily on Reflection.
     *
     * TODO: Recursion fucks shit up!
     *
     * @param mixed $var       The variable to be dumped
     * @param null  $varName   The name of the variable, if empty, it will attempt to get it automatically.
     * @param int   $indent    The indent level put before the output.
     * @param bool  $forObject Not used here.
     *
     * @return string
     */
    private static function dumpObject(&$var, $varName, $indent, $forObject)
    {
        $varName = self::getFormattedVarName($var, $varName);
        $reflect = new \ReflectionClass($var);


        $output = self::getIndent($indent)
            . "$varName = Object(<span style='color: #0099c5;'>{$reflect->name}</span>) {<br/>";

        if (count($reflect->getConstants()) != 0) {
            //Get all of the Constants in the object
            $output .= '<br/>';
            foreach ($reflect->getConstants() as $constName => $value) {
                $nameFormat = "Constant[<span style='color: black;'>{$constName}</span>]";
                $output .= self::dumpSingle($value, $nameFormat, $indent + 1, true);
            }
        }

        if (count($reflect->getProperties()) != 0) {
            //Get all of the Properties in the object
            $output .= '<br/>';
            foreach ($reflect->getProperties() as $property) {
                $view = ($property->isPrivate() ? 'Private' : ($property->isProtected() ? 'Protected' : 'Public'));

                $property->setAccessible(true);

                $static        = ($property->isStatic() ? ' Static' : '');
                $propertyName  = $property->getName();
                $propertyValue = $property->getValue($var);

                $nameFormat = $view . $static . " <span style=\"color: red;\">$$propertyName</span>";

                $output .= self::dumpSingle($propertyValue, $nameFormat, $indent + 1, true);
            }
        } else {
            //If there are no properties, try iterating through it with a foreach()
            //This handles areas where the object is highly dynamic (ex. SimpleXMLElement)
            //Not really sure why/how this works, but it does.
            foreach ($var as $name => $value) {
                //Convert my errors to exceptions so i can handle them dynamically
                $oldErrorHandler = set_error_handler(__NAMESPACE__ . '\specialObjectErrorHandler');
                try {
                    //In SimpleXMLElement (and maybe others) this was the only way to get the variable value
                    $realValue = (string)$var->{$name};
                } catch (\Exception $e) {
                    //If it don't work, just set the value to value
                    $realValue = $value;
                }

                //Replace the old error handler.
                set_error_handler($oldErrorHandler);

                $output .= self::dumpSingle($realValue, $name, $indent + 1, false);
            }
        }

        if (count($reflect->getMethods()) != 0) {
            //Get all of the Methods in the object
            $output .= '<br/>';
            foreach ($reflect->getMethods() as $method) {
                $view   = ($method->isPrivate() ? 'Private' : ($method->isProtected() ? 'Protected' : 'Public'));
                $static = ($method->isStatic() ? ' Static' : '');

                $output .= self::getIndent($indent + 1) . $view . $static . ' <span style="color: #0099c5">'
                    . $method->getName() . '</span>(';

                //Get the parameters.
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {
                    if ($parameter != reset($parameters)) {
                        $output .= ', ';
                    }
                    $output .= '<span style="color: red;">$' . $parameter->getName() . '</span>';

                    try {
                        //And get default values if they exist
                        $defaultValue = $parameter->getDefaultValue();
                        if (is_string($defaultValue) || $defaultValue == '') {
                            $output .= ' = <span style="color: green;">"' . $defaultValue . '"</span>';
                        } elseif (is_bool($defaultValue)) {
                            $output
                                .= ' = <span style="color: #92008d;">' . ($defaultValue ? 'TRUE' : 'FALSE') . '</span>';
                        } else {
                            $output .= ' = <span style="color: #0099c5;">' . $defaultValue . '</span>';
                        }
                    } catch (\Exception $e) {

                    }
                }

                $output .= ');<br/>';
            }
        }

        $output .= self::getIndent($indent) . '}<br/>';

        return $output;
    }

    /**
     * Returns the indent text from the $indentNumber.
     *
     * @param int $indentNumber
     *
     * @return string
     */
    private static function getIndent($indentNumber = 1)
    {
        $indentText = '&nbsp; &nbsp; &nbsp; &nbsp; ';

        return str_repeat($indentText, $indentNumber);
    }

    /**
     * Gets the type of the variable (Uppercase first letter, NULL is in all caps)
     *
     * @param mixed $var
     *
     * @return string
     */
    private static function getType(&$var)
    {
        $type = ucfirst(gettype($var));

        return $type;
    }

    /**
     * Gets the formatted version of the variable name. ($varName if its a variable, ['varName'] if its in an array/object)
     *
     * @param mixed  $var
     * @param string $varName
     *
     * @return string
     */
    private static function getFormattedVarName(&$var, $varName)
    {
        if ($varName === null) {
            $output = '<span style="color: red;">$' . self::getVarName($var) . '</span>';
        } else {
            $output = '[\'<span style="color: green;">' . $varName . '</span>\']';
        }

        return $output;
    }

    /**
     * Uses some really cool trickery to get the name of the variable
     * Stolen from this StackOverflow answer(http://stackoverflow.com/a/4034225/1724045)
     *
     * @param mixed  $var
     * @param bool   $scope
     * @param string $prefix
     * @param string $suffix
     *
     * @return bool|int|string
     */
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

    private static function removeLastElementIfSame(array & $array, $reference)
    {
        if (end($array) === $reference) {
            unset($array[key($array)]);
        }
    }

    private static function isRecursiveArrayIteration(array & $array, $reference)
    {
        $last_element = end($array);
        if ($reference === $last_element) {
            return true;
        }
        $array[] = $reference;

        foreach ($array as &$element) {
            if (is_array($element)) {
                $functionName = __FUNCTION__;
                if (self::$functionName($element, $reference)) {
                    self::removeLastElementIfSame($array, $reference);

                    return true;
                }
            }
        }

        self::removeLastElementIfSame($array, $reference);

        return false;
    }

    public static function isRecursiveArray(array $array)
    {
        $some_reference = new \stdclass();

        return self::isRecursiveArrayIteration($array, $some_reference);
    }
}

/**
 * Used to 'convert' an error to an exception when trying to type-Juggle in the dumpObject function.
 *
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 *
 * @throws \ErrorException
 */
function specialObjectErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (E_RECOVERABLE_ERROR === $errno) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
}