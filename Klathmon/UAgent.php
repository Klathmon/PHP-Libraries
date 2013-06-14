<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/14/13
 */

namespace Klathmon;

/**
 * Class UAgent
 *
 * An extension of UAParser which makes the whole process easier for me.
 *
 * @package Klathmon
 */
class UAgent extends \UAParser
{
    /**
     * @var \UAParser
     */
    private static $instance;

    /**
     * Return the UAParser class;
     *
     * @return \UAParser
     */
    public static function get()
    {
        if (!isset($instance)) {
            self::$instance = new \UAParser;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        return self::$instance->parse($userAgent);
    }
}