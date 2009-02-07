<?php
/**
 * Login screen for Gollem.
 *
 * $Horde: gollem/login.php,v 1.94.2.16 2009/01/27 19:17:43 slusarz Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

function _notificationOutput(&$t)
{
    ob_start();
    $GLOBALS['notification']->notify(array('listeners' => 'status'));
    $t->set('notification_output', ob_get_contents());
    ob_end_clean();
}

@define('AUTH_HANDLER', true);
@define('GOLLEM_BASE', dirname(__FILE__));
$authentication = 'none';
require_once GOLLEM_BASE . '/lib/base.php';

/* Get an Auth object. */
$gollem_auth = (Auth::getProvider() == 'gollem');
$auth = &Auth::singleton($conf['auth']['driver']);
$autologin = Util::getFormData('autologin', false);
$autologin_fail = Util::getFormData('autologin_fail', false);

$actionID = Util::getFormData('actionID');
$backend_key = Util::getFormData('backend_key');
$logout_reason = $auth->getLogoutReason();
$url_param = Util::getFormData('url');

/* Handle cases when we already have a session. */
if (!empty($_SESSION['gollem']) && is_array($_SESSION['gollem'])) {
    if ($logout_reason) {
        /* Log logout requests now. */
        if ($logout_reason == AUTH_REASON_LOGOUT) {
            $entry = sprintf('Logout for %s [%s]',
                             $GLOBALS['gollem_be']['params']['username'],
                             $_SERVER['REMOTE_ADDR']);
        } else {
            $entry = $_SERVER['REMOTE_ADDR'] . ' ' . $auth->getLogoutReasonString();
        }
        Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);

        $language = (isset($prefs)) ? $prefs->getValue('language') : NLS::select();

        unset($_SESSION['gollem']);

        if (isset($prefs)) {
            $prefs->cleanup($gollem_auth);
        }
        if ($gollem_auth) {
            Auth::clearAuth();
            @session_destroy();
            Horde::setupSessionHandler();
            @session_start();
        }

        NLS::setLang($language);

        /* Hook to preselect the correct language in the widget. */
        $_GET['new_lang'] = $language;

        $registry->loadPrefs('horde');
        $registry->loadPrefs();
    } elseif (!$autologin_fail) {
        /* Check to see if we have logged in to the backend yet. */
        if (isset($_SESSION['gollem']['backends'][$backend_key])) {
            require_once GOLLEM_BASE . '/lib/Session.php';
            Gollem_Session::changeBackend($backend_key);

            $url = $url_param;
            if (empty($url)) {
                /* If there is an existing session, redirect the user to the
                 * file manager. */
                $url = Horde::applicationUrl('manager.php', true);
                if ($actionID == 'login') {
                    $url = Util::addParameter($url, 'actionID', 'login', false);
                }
            }
            header('Location: ' . $url);
            exit;
        }
    }
}

/* Log session timeouts. */
if ($logout_reason == AUTH_REASON_SESSION) {
    $entry = sprintf('Session timeout for client [%s]', $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);

    /* Make sure everything is really cleared. */
    Auth::clearAuth();
    unset($_SESSION['gollem']);
}

/* Redirect the user on logout if redirection is enabled. */
if (($logout_reason == AUTH_REASON_LOGOUT) &&
    ($conf['user']['redirect_on_logout'] ||
     !empty($conf['auth']['redirect_on_logout']))) {
    $url = Auth::addLogoutParameters((!empty($conf['auth']['redirect_on_logout'])) ? $conf['auth']['redirect_on_logout'] : $conf['user']['redirect_on_logout'], AUTH_REASON_LOGOUT);
    if (!isset($_COOKIE[session_name()])) {
        $url = Util::addParameter($url, session_name(), session_id());
    }
    header('Location: ' . $url);
    exit;
}

/* Redirect the user if an alternate login page has been specified. */
if (!empty($conf['auth']['alternate_login']) ||
    !empty($conf['user']['alternate_login'])) {
    $url = Auth::addLogoutParameters(!empty($conf['auth']['alternate_login']) ? $conf['auth']['alternate_login'] : $conf['user']['alternate_login']);
    if (!isset($_COOKIE[session_name()])) {
        $url = Util::addParameter($url, session_name(), session_id());
    }
    if ($url_param) {
        $url = Util::addParameter($url, 'url', $url_param, false);
    }
    header('Location: ' . $url);
    exit;
}

/* Initialize the password key(s). If we are doing Horde auth as well,
 * make sure that the Horde auth key gets set. */
Secret::setKey('gollem');
if ($gollem_auth) {
    Secret::setKey('auth');
}
/* Prepare the login template. */
$t = new Gollem_Template();
$t->setOption('gettext', true);

require_once 'Horde/Menu.php';
$menu = new Menu(HORDE_MENU_MASK_NONE);
$t->set('menu', $menu->render(), true);
$t->set('title', sprintf(_("%s Login"), $registry->get('name')));

if (!$backend_key) {
    $backend_key = Gollem::getPreferredBackend();
    /* If there is no backend key defined, there is no available backends for
     * the current user. */
    if ($backend_key === null) {
        $notification->push(_("There are no backends available for the current user."), 'horde.error');
        require GOLLEM_TEMPLATES . '/common-header.inc';
        _notificationOutput($t);
        $t->set('allowlogin', false, true);
        echo $t->fetch(GOLLEM_TEMPLATES . '/login/login.html');
        if (is_callable(array('Horde', 'loadConfiguration'))) {
            Horde::loadConfiguration('motd.php', null, null, true);
        } else {
            if (is_readable(GOLLEM_BASE . '/config/motd.php')) {
                require GOLLEM_BASE . '/config/motd.php';
            }
        }
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }
}

$login_vfs = array();
foreach ($GLOBALS['gollem_backends'] as $key => $curBackend) {
    $login_vfs[$key] = &Gollem::getVFSOb($key, array_merge($curBackend['params'], array('user' => Auth::getAuth())));
    if (is_a($login_vfs[$key], 'PEAR_Error')) {
        Horde::fatal($login_vfs[$key]);
    }
}
// Make the VFS global
$GLOBALS['gollem_vfs'] = $login_vfs[$backend_key];

/* If we only have one backend, and it doesn't require authentication, skip
 * all further steps and login. */
$redirect_params = array();
if (!$logout_reason && !$autologin_fail) {
    if (count($login_vfs) == 1) {
        if (!$GLOBALS['gollem_vfs']->getRequiredCredentials()) {
            $redirect_params['nocredentials'] = 1;
        } elseif (Gollem::canAutoLogin($backend_key, $autologin)) {
            $redirect_params['autologin'] = 1;
        }
    } elseif (Util::getFormData('change_backend') &&
              Gollem::canAutoLogin($backend_key, true)) {
        $redirect_params['autologin'] = 1;
    }
}

if (!empty($redirect_params)) {
    $redirect_params += array('actionID' => 'login', 'backend_key' => $backend_key);
    $url = Util::addParameter(Horde::applicationUrl('redirect.php', true), $redirect_params, null, false);
    header('Location: ' . $url);
    exit;
}

$title = sprintf(_("Welcome to %s"), $registry->get('name', ($gollem_auth) ? 'horde' : null));

if ($logout_reason && $gollem_auth && $conf['menu']['always']) {
    $notification->push('setFocus();if (window.parent.frames.horde_menu) window.parent.frames.horde_menu.location.reload();', 'javascript');
} else {
    $notification->push('setFocus()', 'javascript');
}

if ($reason = $auth->getLogoutReasonString()) {
    $notification->push(str_replace('<br />', ' ', $reason), 'horde.message');
}

/* Do we need to do IE version detection? */
if (!Auth::getAuth() &&
    ($browser->getBrowser() == 'msie') &&
    ($browser->getMajor() >= 5)) {
    $ie_clientcaps = true;
}

$choose_language = ($gollem_auth && !$prefs->isLocked('language'));
$lang_url = null;
if ($choose_language) {
    $lang_url = (!empty($url_param)) ? urlencode($url_param) : null;
}

$tabindex = 0;
$t->set('allowlogin', true, true);

$t->set('action', Horde::url('redirect.php', false, -1, true));
$t->set('gollem_auth', $gollem_auth, true);
$t->set('form_input', Util::formInput());
$t->set('actionid', $actionID);
$t->set('load_frameset', intval($gollem_auth));
$t->set('url', htmlspecialchars($url_param));
$t->set('autologin', ($autologin || Gollem::canAutoLogin($backend_key, true)) ? 1 : 0);
$t->set('clientcaps', !empty($ie_clientcaps), true);
$t->set('anchor_string', htmlspecialchars(Util::getFormData('anchor_string')));
$t->set('backend_key', ($conf['backend']['backend_list'] != 'shown') ? $backend_key : null, true);

_notificationOutput($t);

$t->set('backend_shown', ($conf['backend']['backend_list'] == 'shown'), true);
if ($t->get('backend_shown')) {
    $t->set('shown_tabindex', ++$tabindex);
    $shown_array = array();
    foreach ($GLOBALS['gollem_backends'] as $key => $curBackend) {
        $shown_array[] = array(
            'sel' => ($key == $backend_key),
            'name' => $curBackend['name'],
            'val' => $key
        );
    }
    $t->set('shown_array', $shown_array, true);
}

$t->set('backend_hidden', ($conf['backend']['backend_list'] == 'hidden'), true);
if ($t->get('backend_hidden')) {
    $t->set('hidden_text', sprintf(_("Connect to: %s"), $GLOBALS['gollem_backends'][$backend_key]['name']));
}

$loginparams = array();
if (!empty($GLOBALS['gollem_backends'][$backend_key]['loginparams'])) {
    foreach ($GLOBALS['gollem_backends'][$backend_key]['loginparams'] as $key => $val) {
        $loginparams[] = array(
            'label' => $val,
            'tabindex' => ++$tabindex,
            'name' => $key,
            'val' => $GLOBALS['gollem_backends'][$backend_key]['params'][$key]
        );
    }
}
$t->set('loginparams', $loginparams);

$log_creds = array();
require GOLLEM_BASE . '/config/credentials.php';
foreach ($login_vfs[$backend_key]->getRequiredCredentials() as $credential) {
    if ((($credential == 'username') ||
         ($credential == 'password')) &&
        empty($GLOBALS['gollem_backends'][$backend_key]['hordeauth'])) {
        $log_creds[] = array(
            'label' => $credentials[$credential]['desc'],
            'tabindex' => ++$tabindex,
            'type' => $credentials[$credential]['type'],
            'name' => $credential,
            'val' => htmlspecialchars(Util::getFormData($credential))
        );
    }
}
$t->set('log_creds', $log_creds);

$t->set('choose_language', $choose_language, true);
if ($choose_language) {
    $t->set('langs_tabindex', ++$tabindex);
    $_SESSION['horde_language'] = NLS::select();
    $langs = array();
    foreach ($nls['languages'] as $key => $val) {
        $langs[] = array(
            'sel' => ($key == $_SESSION['horde_language']),
            'val' => $key,
            'name' => $val
        );
    }
    $t->set('langs', $langs, true);
}

$t->set('login_tabindex', ++$tabindex);
$t->set('login', _("Login"));

$js_code = array(
    'var ie_clientcaps = ' . intval($t->get('clientcaps')),
    'var gollem_auth = ' . intval($gollem_auth),
    'var lang_url = ' . (is_null($lang_url) ? 'null' : '\'' . $lang_url . '\''),
    'var nomenu = ' . intval(empty($conf['menu']['always'])),
    'var reload_url = \'' . Util::addParameter(Horde::selfUrl(false, true, true), array('backend_key' => null), null, false) . '\'',
);

Horde::addScriptFile('prototype.js', 'gollem', true);
Horde::addScriptFile('login.js', 'gollem', true);
require GOLLEM_TEMPLATES . '/common-header.inc';
Gollem::addInlineScript(implode(';', $js_code));
echo $t->fetch(GOLLEM_TEMPLATES . '/login/login.html');

if (is_callable(array('Horde', 'loadConfiguration'))) {
    Horde::loadConfiguration('motd.php', null, null, true);
} else {
    if (is_readable(GOLLEM_BASE . '/config/motd.php')) {
        require GOLLEM_BASE . '/config/motd.php';
    }
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
