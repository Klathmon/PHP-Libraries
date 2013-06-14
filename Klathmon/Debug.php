<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

abstract class Debug
{
    public static function dump()
    {
        $args = func_get_args();

        echo "\n<pre style=\"border:1px solid #ccc;padding:10px;margin:10px;font:14px courier;background:whitesmoke;display:block;border-radius:4px;\">\n";

        $trace  = debug_backtrace(false);
        $offset = (@$trace[2]['function'] === 'dump_d') ? 2 : 0;

        echo "<span style=\"color:red\">" . @$trace[1 + $offset]['class'] . "</span>:" .
                "<span style=\"color:blue;\">" . @$trace[1 + $offset]['function'] . "</span>:" .
                    @$trace[0 + $offset]['line'] . " " .
                        "<span style=\"color:green;\">" . @$trace[0 + $offset]['file'] . "</span>\n";

        if (!empty($args)) {
            echo "\n";
            call_user_func_array('var_dump', $args);
        }

        echo "</pre>\n";
    }

    public static function get($var)
    {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        return $result;
    }
}