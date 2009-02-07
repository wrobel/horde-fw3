<?php
/**
 * $Horde: gollem/redirect.php,v 1.55.2.9 2009/01/06 15:23:53 jan Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/* Add anchor to outgoing URL. */
function _addAnchor($url, $type)
{
    switch ($type) {
    case 'param':
        if (!empty($GLOBALS['url_anchor'])) {
            $url .= '#' . $GLOBALS['url_anchor'];
        }
        break;

    case 'url':
        $anchor = Util::getFormData('anchor_string');
        if (!empty($anchor)) {
            $url .= '#' . $anchor;
        } else {
            return _addAnchor($url, 'param');
        }
        break;
    }

    return $url;
}

@define('AUTH_HANDLER', true);
@define('GOLLEM_BASE', dirname(__FILE__));
$authentication = 'none';
require_once GOLLEM_BASE . '/lib/base.php';
require GOLLEM_BASE . '/config/credentials.php';

$actionID = Util::getFormData('actionID');
$backend_key = Util::getFormData('backend_key');

if ($backend_key === null) {
    $autologin = Util::getFormData('autologin', false);
} else {
    $autologin = Util::getFormData('autologin', Gollem::canAutoLogin($backend_key, true));
}

$user = (empty($autologin)) ? Util::getPost('username') : Gollem::getAutologinID($backend_key);
$pass = (empty($autologin)) ? Util::getPost('password') : Auth::getCredential('password');

/* Get URL/Anchor strings now. */
$url_anchor = null;
$url_in = $url_form = Util::getFormData('url');
if (($pos = strrpos($url_in, '#')) !== false) {
    $url_anchor = substr($url_in, $pos + 1);
    $url_in = substr($url_in, 0, $pos);
}

/* If we already have a session. */
if (isset($_SESSION['gollem']) &&
    is_array($_SESSION['gollem']) &&
    ($_SESSION['gollem']['backend_key'] == $backend_key)) {
    /* Make sure that if a username was specified, it is the current
     * username. */
    if ((($user === null) ||
         ($user == $GLOBALS['gollem_be']['params']['username'])) &&
        (($pass === null) ||
         ($pass == Secret::read(Secret::getKey('gollem'), $GLOBALS['gollem_be']['params']['password'])))) {
        $url = $url_in;
        if (empty($url)) {
            $url = Horde::applicationUrl('manager.php', true);
        } elseif (!empty($actionID)) {
            $url = Util::addParameter($url, 'actionID', $actionID);
        }

        if (Util::getFormData('load_frameset')) {
            $full_url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
            $url = Util::addParameter($full_url, 'url', _addAnchor($url, 'param'), false);
        }

        header('Refresh: 0; URL=' . _addAnchor($url, 'url'));
        exit;
    } else {
        /* Disable the old session. */
        unset($_SESSION['gollem']);
        header('Location: ' . Auth::addLogoutParameters(Gollem::logoutUrl(), AUTH_REASON_FAILED));
        exit;
    }
}

/* Create a new session if we're given the proper parameters. */
if (Util::getFormData('gollem_loginform') ||
    Util::getFormData('nocredentials') ||
    $autologin) {
    if (Auth::getProvider() == 'gollem') {
        /* Destroy any existing session on login and make sure to use
         * a new session ID, to avoid session fixation issues. */
        Horde::getCleanSession();
    }

    /* Get the required parameters from the form data. */
    $args = array();
    if (isset($GLOBALS['gollem_backends'][$backend_key]['loginparams'])) {
        $postdata = array_keys($GLOBALS['gollem_backends'][$backend_key]['loginparams']);
    } else {
        $postdata = array();
    }
    if (empty($autologin)) {
        // Allocate a global VFS object
        $GLOBALS['gollem_vfs'] = &Gollem::getVFSOb($backend_key, array());
        if (is_a($GLOBALS['gollem_vfs'], 'PEAR_Error')) {
            Horde::fatal($GLOBALS['gollem_vfs']);
        }

        $postdata = array_merge($postdata, $GLOBALS['gollem_vfs']->getRequiredCredentials());
    } else {
        /* We are attempting autologin.  If hordeauth is off, we need to make
         * sure we are not trying to use horde auth info to login. */
        if (empty($GLOBALS['gollem_backends'][$backend_key]['hordeauth'])) {
            $pass = Util::getPost('password');
        }
    }

    foreach ($postdata as $val) {
        $args[$val] = Util::getPost($val);
    }

    require_once GOLLEM_BASE . '/lib/Session.php';
    if (Gollem_Session::createSession($backend_key, $user, $pass, $args)) {
        $entry = sprintf('Login success for User %s [%s] using backend %s.', Auth::getAuth(), $_SERVER['REMOTE_ADDR'], $backend_key);
        Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);

        $ie_version = Util::getFormData('ie_version');
        if ($ie_version) {
            $browser->setIEVersion($ie_version);
        }

        if (($horde_language = Util::getFormData('new_lang'))) {
            $_SESSION['horde_language'] = $horde_language;
        }

        if (!empty($url_in)) {
            $url = Horde::url(Util::removeParameter($url_in, session_name()), true);
            if ($actionID) {
                $url = Util::addParameter($url, 'actionID', $actionID, false);
            }
        } elseif (Auth::getProvider() == 'gollem') {
            $url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
        } else {
            $url = Horde::applicationUrl('manager.php', true);
        }
    } else {
        $url = Util::addParameter(Auth::addLogoutParameters(Gollem::logoutUrl()), 'backend_key', $backend_key, false);
        if (!empty($autologin)) {
            $url = Util::addParameter($url, 'autologin_fail', '1', false);
        }
    }

    if (Util::getFormData('load_frameset')) {
        $full_url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
        $url = Util::addParameter($full_url, 'url', _addAnchor($url, 'param'), false);
    }

    header('Refresh: 0; URL=' . _addAnchor($url, 'url'));
    exit;
}

/* No session, and no login attempt. Just go to the login page. */
require GOLLEM_BASE . '/login.php';
