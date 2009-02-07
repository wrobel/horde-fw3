<?php
/**
 * $Horde: hermes/lib/base.php,v 1.36 2008/01/02 16:15:09 chuck Exp $
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * Hermes base inclusion file.
 *
 * This file brings in all of the dependencies that every Hermes script
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
$registry = &Registry::singleton();
if (is_a(($pushed = $registry->pushApp('hermes', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('HERMES_TEMPLATES', $registry->get('templates'));
$print_link = null;

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Hermes.
if (!defined('HERMES_BASE')) {
    define('HERMES_BASE', dirname(__FILE__) . '/..');
}

// Hermes base libraries.
require_once HERMES_BASE . '/lib/Hermes.php';
$GLOBALS['hermes'] = &Hermes::getDriver();

// Horde libraries.
require_once 'Horde/Help.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Template.php';

// Start compression.
Horde::compressOutput();
