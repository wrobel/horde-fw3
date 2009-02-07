<?php
/**
 * Gollem base inclusion file. This file brings in all of the
 * dependencies that every Gollem script will need, and sets up
 * objects that all scripts use.
 *
 * The following variables, defined in the script that calls this one, are
 * used:
 *   $authentication   - The authentication mode to use.
 *   $no_compress      - Controls whether the page should be compressed.
 *   $session_control  - Sets special session control limitations.
 *
 * This file creates the following global variables:
 *   $gollem_backends - A link to the current list of available backends
 *   $gollem_be - A link to the current backend parameters in the session
 *   $gollem_vfs - A link to the current VFS object for the active backend
 *
 * $Horde: gollem/lib/base.php,v 1.60.2.4 2008/10/09 20:54:42 jan Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
if (Util::nonInputVar('session_control') == 'readonly') {
    $registry = &Registry::singleton(HORDE_SESSION_READONLY);
} else {
    $registry = &Registry::singleton();
}

if (is_a(($pushed = $registry->pushApp('gollem', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('GOLLEM_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Gollem.
if (!defined('GOLLEM_BASE')) {
    define('GOLLEM_BASE', dirname(__FILE__) . '/..');
}

// Horde libraries.
require_once 'Horde/Secret.php';
require_once 'VFS.php';

// Gollem libraries.
require_once GOLLEM_BASE . '/lib/Gollem.php';
require_once GOLLEM_BASE . '/lib/Template.php';

// If Gollem isn't responsible for Horde auth, and no one is logged into
// Horde, redirect to the login screen.
if (!(Auth::isAuthenticated() || (Auth::getProvider() == 'gollem'))) {
    Horde::authenticationFailureRedirect();
}

// Start compression.
if (!Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}

// Set the global $gollem_be variable to the current backend's parameters.
if (empty($_SESSION['gollem']['backend_key'])) {
    $GLOBALS['gollem_be'] = null;
} else {
    $GLOBALS['gollem_be'] = &$_SESSION['gollem']['backends'][$_SESSION['gollem']['backend_key']];
}

$authentication = Util::nonInputVar('authentication');
if ($authentication !== 'none') {
    // If we've gotten to this point and have valid login credentials
    // but don't actually have a Gollem session, then we need to go
    // through redirect.php to ensure that everything gets set up
    // properly. Single-signon and transparent authentication setups
    // are likely to trigger this case.
    if (empty($_SESSION['gollem'])) {
        // We need to specifically redirect since we have to call base.php
        // again as all globals have not yet been initialized.
        header('Location: ' . Util::addParameter(Horde::applicationUrl('redirect.php'), 'url', Horde::selfUrl(true)));
        exit;
    }

    // Check authentication and create $GLOBALS['gollem_vfs'] object
    Gollem::checkAuthentication($authentication);
}

// Load the backend list.
Gollem::loadBackendList();
