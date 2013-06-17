<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/17/13
 */

namespace Klathmon;

use PDO, Exception;

/**
 * Class Database
 *
 * A simple wrapper for PDO.
 * It adds a singleton to the class so i don't have to worry about passing database connection information or attempting
 * re-connections all the time. It also sets up the use of Exceptions instead of Errors.
 *
 * @package Klathmon
 */
class Database extends PDO
{
    /**
     * @var PDO
     */
    private static $instance;

    /**
     * Get/Create an instance of PDO. If the connection has already been made once, the parameters are not needed.
     * This also sets up PDO to use Exceptions for errors.
     *
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     *
     * @return PDO
     * @throws \Exception
     */
    public static function getInstance($host = null, $database = null, $username = null, $password = null)
    {
        if (!isset($instance)) {
            if ($host != null || $database != null || $username != null || $password != null) {
                try {
                    self::$instance = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (Exception $e) {
                    throw new Exception('Could not connect to database!', 1);
                }
            } else {
                throw new Exception('Hostname, Database, Username, and Password must all be set!', 2);
            }
        }

        return self::$instance;
    }

    /**
     * Kill(disconnect) the instance of PDO
     */
    public static function killInstance()
    {
        self::$instance = null;
    }
}