<?php
/**
 * Jonah base inclusion file.
 *
 * $Horde: jonah/lib/base.php,v 1.52.2.1 2009/10/19 23:34:20 jan Exp $
 *
 * This file brings in all of the dependencies that every Jonah script
 * will need, and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
if (Util::nonInputVar('session_control') == 'none') {
    $registry = &Registry::singleton(HORDE_SESSION_NONE);
} elseif (Util::nonInputVar('session_control') == 'readonly') {
    $registry = &Registry::singleton(HORDE_SESSION_READONLY);
} else {
    $registry = &Registry::singleton();
}
if (is_a(($pushed = $registry->pushApp('jonah', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('JONAH_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = &Notification::singleton();
$notification->attach('status');

/* Find the base file path of Jonah. */
if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', dirname(__FILE__) . '/..');
}

/* Jonah base library. */
require_once JONAH_BASE . '/lib/Jonah.php';

/* Horde libraries. */
require_once 'Horde/Help.php';
require_once 'Horde/Template.php';

// Start compression.
if (!Util::nonInputVar('no_compress')) {
     Horde::compressOutput();
}
