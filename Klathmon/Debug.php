<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

abstract class Debug
{
    private static $emailAddress;

    public static function dump()
    {
        $args = func_get_args();

        echo "\n<div style=\"border:1px solid #ccc;padding:10px;margin:10px;font:14px courier;background:whitesmoke;display:block;border-radius:4px;\">\n";

        $trace = debug_backtrace(false);

        $line = @$trace[0]['line'];
        $file = @$trace[0]['file'];

        echo <<<HTML
Line: <span style="color:green;">{$line}</span> &nbsp; File:<span style="color:green;"> {$file}</span><br/>
HTML;

        if (!empty($args)) {
            foreach ($args as $var) {
                self::singleDump($var);
                echo "<br/>";
            }
        }

        echo "</div>\n";
    }


    public static function get($var)
    {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        return $result;
    }

    public static function setEmail($address)
    {
        self::$emailAddress = $address;
    }

    public static function email()
    {
        if (isset(self::$emailAddress)) {
            $message = call_user_func_array('self::get', func_get_args());

            mail(self::$emailAddress, 'Email Dump', $message);
        } else {
            throw new \Exception('No Email Address Set! Set an email address with ' . __CLASS__
            . '::setEmail($address);');
        }
    }

    private static function singleDump($var, $var_name = null, $indent = null, $refrence = null)
    {
        $do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
        $reference      = $refrence . $var_name;
        $keyvar         = 'the_do_dump_recursion_protection_scheme';
        $keyname        = 'referenced_object_name';

        if (is_array($var) && isset($var[$keyvar])) {
            $real_var  = & $var[$keyvar];
            $real_name = & $var[$keyname];
            $type      = ucfirst(gettype($real_var));
            echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
        } else {
            $var  = array($keyvar => $var, $keyname => $reference);
            $avar = & $var[$keyvar];

            $type = ucfirst(gettype($avar));
            if ($type == "String") $type_color = "<span style='color:green'>";
            elseif ($type == "Integer") {
                $type_color = "<span style='color:red'>";
            } elseif ($type == "Double") {
                $type_color = "<span style='color:#0099c5'>";
                $type       = "Float";
            } elseif ($type == "Boolean") $type_color = "<span style='color:#92008d'>"; elseif ($type == "NULL")
                $type_color = "<span style='color:black'>";

            if (is_array($avar)) {
                $count = count($avar);
                echo "$indent" . ($var_name ? "$var_name => " : "")
                    . "<span style='color:#a2a2a2'>$type ($count)</span> $indent{<br>";
                $keys = array_keys($avar);
                foreach ($keys as $name) {
                    $value = & $avar[$name];
                    call_user_func(__METHOD__, $value, "['$name']", $indent . $do_dump_indent, $reference);
                }
                echo "$indent}<br>";
            } elseif (is_object($avar)) {
                echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> $indent{<br>";
                foreach ($avar as $name => $value)
                    call_user_func(__METHOD__, $value, "$name", $indent . $do_dump_indent, $reference);
                echo "$indent}<br>";
            } elseif (is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar)
                . ")</span> $type_color$avar</span><br>"; elseif (is_string($avar)) echo
                "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar)
                . ")</span> $type_color\"$avar\"</span><br>"; elseif (is_float($avar)) echo
                "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar)
                . ")</span> $type_color$avar</span><br>"; elseif (is_bool($avar)) echo
                "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $type_color"
                . ($avar == 1 ? "TRUE" : "FALSE") . "</span><br>"; elseif (is_null($avar)) echo
                "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar)
                . ")</span> {$type_color}NULL</span><br>"; else echo
                "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $avar<br>";

            $var = $var[$keyvar];
        }
    }
}