<?php
/**
 * The Agora base inclusion library.
 *
 * $Horde: agora/lib/base.php,v 1.48.2.2 2009/01/06 15:22:13 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

/* Set up the registry. */
$registry = &Registry::singleton();
if (is_a(($pushed = $registry->pushApp('agora', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('AGORA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

/* Horde base libraries. */
require_once 'Horde/Help.php';

/* Agora base library. */
@define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/Agora.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once AGORA_BASE . '/lib/Template.php';
