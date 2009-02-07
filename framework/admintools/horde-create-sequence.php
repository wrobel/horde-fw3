#!@php_bin@
<?php
/**
 * $Horde: framework/admintools/horde-create-sequence.php,v 1.1.2.5 2009/01/06 15:23:51 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package admintools
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Don't prompt for login
define('AUTH_HANDLER', true);

// Use the HORDE_BASE environment variable if it's set.
if ((($base = getenv('HORDE_BASE')) ||
     (!empty($_ENV['HORDE_BASE']) && $base = $_ENV['HORDE_BASE'])) &&
    is_dir($base) && is_readable($base)) {
    define('HORDE_BASE', $base);
} else {
    define('HORDE_BASE', dirname(dirname(dirname(__FILE__))));
}

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
$cli = Horde_CLI::singleton();
$cli->init();

// Include needed libraries.
require_once HORDE_BASE . '/lib/base.php';

$db_lib = 'DB';
$sequence = null;
if (isset($_SERVER['argv']) && count($_SERVER['argv']) >= 2) {
    array_shift($_SERVER['argv']);
    while ($arg = array_shift($_SERVER['argv'])) {
        if ($arg == '--mdb2') {
            $db_lib = 'MDB2';
        } else {
            $sequence = $arg;
        }
    }
}
if (is_null($sequence)) {
    $sequence = $cli->prompt(_("What sequence do you want to create (_seq will be added automatically)?"));
}

switch ($db_lib) {
case 'DB':
    require_once 'DB.php';
    $dbh = DB::connect($conf['sql']);
    break;

case 'MDB2':
    require_once 'MDB2.php';
    $params = $conf['sql'];
    unset($params['charset']);
    $dbh = MDB2::factory($params);
    break;

default:
    Horde::fatal('Unknown database abstraction library');
}
if (is_a($dbh, 'PEAR_Error')) {
    Horde::fatal($dbh, __FILE__, __LINE__);
}

if (!preg_match('/^\w+$/', $sequence)) {
    $cli->fatal('Invalid sequence name');
}

switch ($db_lib) {
case 'DB':
    $result = $dbh->createSequence($sequence);
    break;

case 'MDB2':
    $dbh->loadModule('Manager', null, true);
    $result = $dbh->manager->createSequence($sequence);
    break;
}
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->getMessage());
}

$cli->green('Sequence created.');
exit(0);
