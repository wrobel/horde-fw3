<?php
/**
 * The Auth_gollem:: class provides a Gollem implementation of the Horde
 * authentication system.
 *
 * Required parameters:<pre>
 *   None.</pre>
 *
 * Optional parameters:<pre>
 *   None.</pre>
 *
 * $Horde: gollem/lib/Auth/gollem.php,v 1.18.2.7 2009/01/06 15:23:54 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_Auth
 */
class Auth_gollem extends Auth {

    /**
     * Find out if a set of login credentials are valid, and if
     * requested, mark the user as logged in in the current session.
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  The credentials to check.
     * @param boolean $login      Whether to log the user in. If false, we'll
     *                            only test the credentials and won't modify
     *                            the current session.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    function authenticate($userID = null, $credentials = array(),
                          $login = false)
    {
        // Check for for hordeauth.
        if (empty($_SESSION['gollem']['backend_key'])) {
            if (Gollem::canAutoLogin()) {
                $backend_key = Gollem::getPreferredBackend();

                $ptr = &$GLOBALS['gollem_backends'][$backend_key];
                if (!empty($ptr['hordeauth'])) {
                    $user = Gollem::getAutologinID($backend_key);
                    $pass = Auth::getCredential('password');

                    require_once GOLLEM_BASE . '/lib/Session.php';

                    if (Gollem_Session::createSession($backend_key, $user, $pass)) {
                        $entry = sprintf('Login success for %s [%s] to {%s}',
                                         $user, $_SERVER['REMOTE_ADDR'],
                                         $backend_key);
                        Horde::logMessage($entry, __FILE__, __LINE__,
                                          PEAR_LOG_NOTICE);
                        return true;
                    }
                }
            }
        }

        if (empty($userID) &&
            !empty($GLOBALS['gollem_be']['params']['username'])) {
            $userID = $GLOBALS['gollem_be']['params']['username'];
        }

        if (empty($credentials) &&
            !empty($GLOBALS['gollem_be']['params']['password'])) {
            $credentials = array('password' => Secret::read(Secret::getKey('gollem'), $GLOBALS['gollem_be']['params']['password']));
        }

        $login = ($login && ($this->getProvider() == 'gollem'));

        return parent::authenticate($userID, $credentials, $login);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @access private
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    function _authenticate($userID, $credentials)
    {
        if (!(isset($_SESSION['gollem']) && is_array($_SESSION['gollem']))) {
            if (isset($GLOBALS['prefs'])) {
                $GLOBALS['prefs']->cleanup(true);
            }
            $this->_setAuthError(AUTH_REASON_SESSION);
            return false;
        }

        // Allocate a global VFS object
        $GLOBALS['gollem_vfs'] = &Gollem::getVFSOb($_SESSION['gollem']['backend_key']);
        if (is_a($GLOBALS['gollem_vfs'], 'PEAR_Error')) {
            Horde::fatal($GLOBALS['gollem_vfs']);
        }

        $valid = $GLOBALS['gollem_vfs']->checkCredentials();
        if (is_a($valid, 'PEAR_Error')) {
            $msg = $valid->getMessage();
            if (empty($msg)) {
                $this->_setAuthError(AUTH_REASON_FAILED);
            } else {
                $this->_setAuthError(AUTH_REASON_MESSAGE, $msg);
            }
            return false;
        }

        return true;
    }

    /**
     * Somewhat of a hack to allow Gollem to set an authentication error
     * message that may occur outside of this file.
     *
     * @param string $msg  The error message to set.
     */
    function gollemSetAuthErrorMsg($msg)
    {
        $this->_setAuthError(AUTH_REASON_MESSAGE, $msg);
    }

}
