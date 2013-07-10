<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/20/13
 */

namespace Klathmon\Traits;

use \Exception;

trait Singleton
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $reflection     = new \ReflectionClass(__CLASS__);
            self::$instance = $reflection->newInstanceArgs(func_get_args());
        }

        return self::$instance;
    }

    public static function killInstance()
    {
        self::$instance = null;
    }

    final private function __clone()
    {
    }

    final private function __wakeup()
    {
    }
}