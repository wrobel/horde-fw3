<?php
/**
 * $Horde: trean/lib/base.php,v 1.41 2008/06/14 22:47:39 mrubinsk Exp $
 *
 * Trean base inclusion file.
 *
 * This file brings in all of the dependencies that every Trean script will
 * need and sets up objects that all scripts use.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$session_control = Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = &Registry::singleton(HORDE_SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = &Registry::singleton(HORDE_SESSION_READONLY);
} else {
    $registry = &Registry::singleton();
}

if (is_a(($pushed = $registry->pushApp('trean', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('TREAN_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Trean.
if (!defined('TREAN_BASE')) {
    define('TREAN_BASE', dirname(__FILE__) . '/..');
}

// Trean base libraries.
require_once TREAN_BASE . '/lib/Trean.php';
require_once TREAN_BASE . '/lib/Bookmarks.php';

// Horde libraries.
require_once 'Horde/Text.php';
require_once 'Horde/Help.php';

// Create db and share instances.
$GLOBALS['trean_db'] = Trean::getDb();
if (is_a($GLOBALS['trean_db'], 'PEAR_Error')) {
    Horde::fatal($GLOBALS['trean_db'], __FILE__, __LINE__, false);
}
$GLOBALS['trean_shares'] = new Trean_Bookmarks();

// Make sure "My Bookmarks" folder exists
if (Auth::getAuth() && !$GLOBALS['trean_shares']->exists(Auth::getAuth())) {
    require_once 'Horde/Identity.php';
    $identity = &Identity::singleton();
    $name = $identity->getValue('fullname');
    if (trim($name) == '') {
        $name = Auth::removeHook(Auth::getAuth());
    }
    $folder = &$GLOBALS['trean_shares']->newFolder(Auth::getAuth(), array('name' => sprintf(_("%s's Bookmarks"), $name)));
    $result = $GLOBALS['trean_shares']->addFolder($folder);
    if (is_a($result, 'PEAR_Error')) {
        Horde::fatal($result, __FILE__, __LINE__);
    }
}
