<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/19/13
 */


namespace Klathmon\Login;
use \Klathmon\Database, \Klathmon\Crypt\Hash, \Exception;

/**
 * Class Login
 *
 * This is a basic Login-System class. It was built to be extended for each individual purpose.
 *
 * To customize the class, extend only the following functions:
 * ->checkCredentials();
 * ->displayLoginScreen();
 *
 * @package Klathmon\Login
 */
class Login
{
    protected $dbHost, $dbDatabase, $dbUsername, $dbPassword, $session;

    /**
     * Creates a Login class.
     *
     * @param string  $host     The database location
     * @param string  $database The database name
     * @param string  $username Database login Name
     * @param string  $password Database login Password
     * @param Session $session  A valid \Klathmon\Session class
     */
    final public function __construct($host, $database, $username, $password, Session &$session)
    {
        $this->dbHost     = $host;
        $this->dbDatabase = $database;
        $this->dbUsername = $username;
        $this->dbPassword = $password;

        $this->session = & $session;
    }

    /**
     * Authenticates the user, if they are Authe'd then it does nothing.
     * If they are not Authe'd then it calls ->displayLoginScreen();
     *
     * @param bool $regenerate Set to TRUE to regenerate the SessionID each page load.
     */
    final public function authenticate($regenerate = false)
    {
        $this->checkCredentials();

        try {
            $this->session->validateSession();
        } catch (Exception $e) {
            switch ($e->getCode()) {
            case 1:
            case 2:
            case 3:
                $this->displayLoginScreen();
                break;
            }
        }

        if ($regenerate) {
            $this->session->regenerateSession();
        }
    }

    /**
     * Will destroy the session and redirect to $url
     *
     * @param string $url
     */
    final public function logout($url)
    {
        $this->session->destroySession();
        header("Location: $url");
        die();
    }

    /**
     * This function should:
     * 1) Get the username/password via GET/POST directives.
     * 2) Check the username/password against the database to see if it is valid.
     * 3) Create a new session and fill it with any and all required data.
     * 4) Anything else that you need to happen on the first page-load after the user logs in.
     */
    protected function checkCredentials()
    {
        if ($_POST['logMeIn'] == 'Y' && isset($_POST['username']) && isset($_POST['password'])) {
            $username = strtolower($_POST['username']);

            $db   = Database::getInstance($this->dbHost, $this->dbDatabase, $this->dbUsername, $this->dbPassword);
            $hash = new Hash();

            $statement = $db->prepare('SELECT * FROM `users` WHERE `username` = ? LIMIT 1');
            $statement->execute([$username]);
            $row = $statement->fetchAll()[0];

            if (!$hash->verify($_POST['password'], $row['password'])) {
                $this->displayLoginScreen();
            }

            $this->session->startNewSession();
            $this->session->set('username', $username);
        }
    }

    /**
     * Display the login screen. This may redirect to another page, as long as that page POSTs or GETs to a page that
     * the ->authenticate() function first.
     */
    protected function displayLoginScreen()
    {
        ?>
        <style>
            DIV#Login {
                width: 800px;
                height: 400px;
            }
        </style>
        <div id="Login">
            <form id="LoginForm" method="POST">
                <input type="hidden" id="logMeIn" name="logMeIn" value="Y">
                <label for="username">Username: </label><input type="text" id="username" name="username" required>
                <label for="password">Password: </label><input type="password" id="password" name="password" required>
                <input type="submit" value="Submit" id="submit">
            </form>
        </div>
        <?php
        die();
    }

}