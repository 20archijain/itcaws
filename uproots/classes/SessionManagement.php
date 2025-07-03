<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class SessionManagement
{
    final public function startSession()
    {
        // is SSL set
        $secure = isset($_SERVER['HTTPS']);

        // Set the session cookie parameters
        session_set_cookie_params(0, '/', constant('DOMAIN_NAME'), $secure, true);

        session_start();
    }

    final public function setSessionKey($keyName, $value = "")
    {
        $_SESSION[$keyName] = $value;
    }

    final public function getSessionKey($keyName)
    {
        return isset($_SESSION[$keyName]) ? $_SESSION[$keyName] : null;
    }

    final public function getSessionId()
    {
        return session_id();
    }

    final public function destroySession()
    {
        // empty session
        $_SESSION = array();

        // remove all session variables
        session_unset();

        // destroy the session
        session_destroy();
    }

    final public function regenerateSession()
    {
        $this->startSession();
        session_regenerate_id(true);
    }

    private function getSessioName()
    {
        $keys = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

        $start = rand(0, strlen($keys) - 2);

        return str_shuffle(substr($keys, $start));
    }
}
