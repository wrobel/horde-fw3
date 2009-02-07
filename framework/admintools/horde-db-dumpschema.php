#!@php_bin@
<?php
/**
 * Dump the requested tables (or all) from the Horde database to XML schema
 * format.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  admintools
 */

// Don't prompt for login
define('AUTH_HANDLER', true);

// Use the HORDE_BASE environment variable if it's set.
if ((($base = getenv('HORDE_BASE')) ||
     (!empty($_ENV['HORDE_BASE']) && $base = $_ENV['HORDE_BASE'])) &&
    is_dir($base) && is_readable($base)) {
    define('HORDE_BASE', $base);
} elseif (is_file(getcwd() . '/lib/core.php')) {
    define('HORDE_BASE', getcwd());
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
require_once 'Horde/SQL/Manager.php';

$manager = Horde_SQL_Manager::getInstance();
if (is_a($manager, 'PEAR_Error')) {
    $cli->fatal($manager->toString());
}

// Get rid of the script name
array_shift($_SERVER['argv']);
$tables = array_values($_SERVER['argv']);

$xml = $manager->dumpSchema($tables);
if (is_a($xml, 'PEAR_Error')) {
    $cli->fatal($xml->toString());
}

echo $xml;
exit(0);
