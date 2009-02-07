<?php
/**
 * Mnemo base inclusion file.
 *
 * This file brings in all of the dependencies that every Mnemo
 * script will need and sets up objects that all scripts use.
 *
 * $Horde: mnemo/lib/base.php,v 1.46.10.13 2009/01/06 15:24:58 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @since   Mnemo 1.0
 * @package Mnemo
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
if (is_a(($pushed = $registry->pushApp('mnemo', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('MNEMO_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Mnemo.
if (!defined('MNEMO_BASE')) {
    define('MNEMO_BASE', dirname(__FILE__) . '/..');
}

// Mnemo libraries.
require_once MNEMO_BASE . '/lib/Mnemo.php';
require_once MNEMO_BASE . '/lib/Driver.php';

// Horde libraries.
require_once 'Horde/Text/Filter.php';
require_once 'Horde/History.php';

// Start compression, if requested.
Horde::compressOutput();

// Create a share instance.
require_once 'Horde/Share.php';
$GLOBALS['mnemo_shares'] = &Horde_Share::singleton($registry->getApp());

Mnemo::initialize();
