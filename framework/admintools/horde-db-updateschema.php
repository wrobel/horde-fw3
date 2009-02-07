#!@php_bin@
<?php
/**
 * Update database definitions from the given .xml schema file.
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

// Get arguments.
array_shift($_SERVER['argv']);
if (!count($_SERVER['argv'])) {
    exit("You must specify the schema file to update.\n");
}
$file = array_shift($_SERVER['argv']);
$debug = count($_SERVER['argv']) && array_shift($_SERVER['argv']) == 'debug';

$result = $manager->updateSchema($file, $debug);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal('Failed to update database definitions: ' . $result->toString());
    exit(1);
} elseif ($debug) {
    echo $result;
} else {
    $cli->message('Successfully updated the database with definitions from "' . $file . '".', 'cli.success');
}
exit(0);
