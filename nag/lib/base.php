<?php
/**
 * Nag base inclusion file.
 *
 * $Horde: nag/lib/base.php,v 1.75.10.7 2008/01/02 16:50:50 chuck Exp $
 *
 * This file brings in all of the dependencies that every Nag
 * script will need and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
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

if (is_a(($pushed = $registry->pushApp('nag', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('NAG_TEMPLATES', $registry->get('templates'));

// Find the base file path of Nag.
if (!defined('NAG_BASE')) {
    define('NAG_BASE', dirname(__FILE__) . '/..');
}

// Notification system.
require_once NAG_BASE . '/lib/Notification/Listener/status.php';
$notification = &Notification::singleton();
$notification->attach('status', null, 'Notification_Listener_status_nag');

// Nag base libraries.
require_once NAG_BASE . '/lib/Nag.php';
require_once NAG_BASE . '/lib/Driver.php';

// Horde libraries.
require_once 'Horde/History.php';

// Start compression.
Horde::compressOutput();

// Set the timezone variable.
NLS::setTimeZone();

// Create a share instance.
require_once 'Horde/Share.php';
$GLOBALS['nag_shares'] = &Horde_Share::singleton($registry->getApp());

Nag::initialize();
