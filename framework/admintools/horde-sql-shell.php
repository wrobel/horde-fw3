#!@php_bin@
<?php
/**
 * $Horde: framework/admintools/horde-sql-shell.php,v 1.1.2.5 2009/01/06 15:23:51 jan Exp $
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

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
Horde_CLI::init();

// Include needed libraries.
require_once HORDE_BASE . '/lib/base.php';
require_once 'DB.php';

$dbh = &DB::connect($conf['sql']);
if (is_a($dbh, 'PEAR_Error')) {
    Horde::fatal($dbh, __FILE__, __LINE__);
}
$dbh->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

// list databases command
// $result = $dbh->getListOf('databases');

// list tables command
// $result = $dbh->getListOf('tables');

// read sql file for statements
$statements = array();
$current_stmt = '';
$fp = fopen($_SERVER['argv'][1], 'r');
while ($line = fgets($fp, 8192)) {
    $line = rtrim(preg_replace('/^(.*)--.*$/s', '\1', $line));
    if (!$line) {
        continue;
    }

    $current_stmt .= $line;

    if (substr($line, -1) == ';') {
        // leave off the ending ;
        $statements[] = substr($current_stmt, 0, -1);
        $current_stmt = '';
    }
}

// run statements
foreach ($statements as $stmt) {
    echo "Running:\n  " . preg_replace('/\s+/', ' ', $stmt) . "\n";
    $result = $dbh->query($stmt);
    if (is_a($result, 'PEAR_Error')) {
        var_dump($result);
        exit;
    }

    echo "  ...done\n\n";
}
