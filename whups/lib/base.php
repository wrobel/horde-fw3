<?php
/**
 * Whups base inclusion file.
 *
 * This file brings in all of the dependencies that every Whups script will
 * need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 *   $no_compress  -  Controls whether the page should be compressed
 *
 * $Horde: whups/lib/base.php,v 1.75.2.2 2009/08/12 22:28:14 jan Exp $
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = &Registry::singleton();
if (is_a($pushed = $registry->pushApp('whups', !defined('AUTH_HANDLER')), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('WHUPS_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Whups.
if (!defined('WHUPS_BASE')) {
    define('WHUPS_BASE', dirname(__FILE__) . '/..');
}

// Whups base libraries.
require_once WHUPS_BASE . '/lib/Whups.php';
require_once WHUPS_BASE . '/lib/Driver.php';

// Horde libraries.
require_once 'Horde/Help.php';
require_once 'Horde/Group.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// UI classes.
require_once 'Horde/UI/Tabs.php';

// Start output compression.
if (!Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}

// Whups backend.
$GLOBALS['whups_driver'] = Whups_Driver::factory();
$GLOBALS['whups_driver']->initialise();
