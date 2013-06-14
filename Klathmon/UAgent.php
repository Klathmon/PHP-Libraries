<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

class UAgent extends \UAParser
{
    private static $instance;

    public static function get()
    {
        if (!isset($instance)) {
            self::$instance = new \UAParser;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        return self::$instance->parse($userAgent);
    }
}