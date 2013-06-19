<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/19/13
 */

namespace Klathmon\Login;
use \Exception;

/**
 * Class Session
 *
 * A session wrapper. Will validate/check the session for hijackers and other stuff and gives easy full proof functions
 * to create/destroy sessions.
 * Everything is abstracted (to some extent), so the actual session handler can be changed at any time!
 *
 * @package Klathmon\Login
 */
class Session
{
    const HASH_FUNCTION             = 'SHA256';
    const BITS_PER_CHARACTER        = '5';
    const ENTROPY_FILE              = '/dev/urandom';
    const INTERNAL_SESSION_DATA_VAR = 'KlathmonSessionData';

    private $sessionLength;

    /**
     * Starts the Session
     *
     * @param string $sessionName             The name of the cookie stored in the user's browser.
     * @param int    $sessionLength           The time (in seconds) that the session will be valid for.
     * @param string $sessionStorageDirectory The directory that the session data will be stored in.
     * @param int    $entropyLength           The amount of entropy used in creating the cookie's value.
     */
    public function __construct(
        $sessionName = 'SessionID', $sessionLength = 1800,
        $sessionStorageDirectory = '/tmp', $entropyLength = 512
    )
    {

        $this->sessionLength = $sessionLength;

        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.hash_function', self::HASH_FUNCTION);
        ini_set('session.hash_bits_per_character ', self::BITS_PER_CHARACTER);
        ini_set('session.entropy_file', self::ENTROPY_FILE);

        ini_set('session.name', $sessionName);
        ini_set('session.gc_maxlifetime', $sessionLength);
        ini_set('session.cookie_lifetime', $sessionLength);
        ini_set('session.save_path', $sessionStorageDirectory);
        ini_set('session.entropy_length', $entropyLength);

        @session_start();

        $array                 = $this->get(self::INTERNAL_SESSION_DATA_VAR);
        $array['lastActivity'] = time();
        $this->set(self::INTERNAL_SESSION_DATA_VAR, $array);
    }

    /**
     * Will check to see if the session is valid (using various special checks and stuff)
     * Does nothing if session is valid, throws Exception if session is bad
     *
     * @throws \Exception 1: Bad UserAgent 2: Bad Remote Address 3: The session was last used too long ago (Too old)
     */
    public function validateSession()
    {
        $data    = $this->get(self::INTERNAL_SESSION_DATA_VAR);
        $sessLen = $this->sessionLength;

        if ($data['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
            throw new Exception('UserAgent is incorrect!', 1);
        } elseif ($data['remoteAddress'] != $_SERVER['REMOTE_ADDR']) {
            throw new Exception('IP Address of the user has changed!', 2);
        } elseif ($data['lastActivity'] < (new \DateTime('now'))->modify("-$sessLen seconds")->format('U')
        ) {
            throw new Exception('Last Activity was too long ago!', 3);
        }
    }

    /**
     * Starts a completely new session, will clobber anything in the old session (including data stored).
     */
    public function startNewSession()
    {
        $_SESSION = Array();

        $this->regenerateSession();

        $array = [
            'userAgent'     => $_SERVER['HTTP_USER_AGENT'],
            'remoteAddress' => $_SERVER['REMOTE_ADDR'],
            'lastActivity'  => time()
        ];

        $this->set(self::INTERNAL_SESSION_DATA_VAR, $array);
    }

    /**
     * Regenerates the SessionID (or value of the cookie in user's browser)
     */
    public function regenerateSession()
    {
        session_regenerate_id(true);
    }

    /**
     * Completely destroy's the session
     */
    public function destroySession()
    {
        $_SESSION = Array(); //Clear the array

        $parameters = session_get_cookie_params();
        setcookie(
            session_name(), '',
            (new \DateTime('now'))->modify("-1 day")->format('U'),
            $parameters['path'], $parameters['domain'],
            $parameters['secure'], $parameters['httponly']
        ); //Clear the user's cookie

        session_destroy(); //Destroy the session completely.
    }


    /**
     * Gets information from the session.
     *
     * @param mixed $variable
     *
     * @return mixed
     */
    public function get($variable)
    {
        return $_SESSION[$variable];
    }

    /**
     * Stores information in the session.
     *
     * @param $variable
     * @param $value
     */
    public function set($variable, $value)
    {
        $_SESSION[$variable] = $value;
    }
}