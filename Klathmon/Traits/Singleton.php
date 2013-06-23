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
            try {
                $reflection     = new \ReflectionClass(__CLASS__);
                self::$instance = $reflection->newInstanceArgs(func_get_args());
            } catch (Exception $e) {
                throw new Exception('Error creating object!');
            }
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