#!/usr/bin/php
<?php
/**
 * $Horde: whups/scripts/reminders.php,v 1.16.2.1 2009/01/06 15:28:34 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
@define('WHUPS_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_CLI::init();

// Include needed libraries.
require_once WHUPS_BASE . '/lib/base.php';
require_once WHUPS_BASE . '/lib/Scheduler/whups.php';

// Get an instance of the Whups scheduler.
$reminder = &Horde_Scheduler::unserialize('Horde_Scheduler_whups');

// Check for and send reminders.
$reminder->run();
