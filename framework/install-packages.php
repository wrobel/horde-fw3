#!/usr/bin/php
<?php
/**
 * $Horde: framework/install-packages.php,v 1.14.10.4 2007-12-20 13:48:46 jan Exp $
 *
 * This script iterates each directory and forces an install from the
 * package.xml file for each package.
 *
 * @package Horde_Framework
 */

/* Don't die if time limit exceeded. */
set_time_limit(0);

/* Get any arguments. */
require_once 'Console/Getopt.php';
$args = Console_Getopt::readPHPArgv();
$options = Console_Getopt::getopt($args, 'd:c:p:', array('install-dir=', 'config=', 'packages='));
if (is_a($options, 'PEAR_Error')) {
    echo <<<USAGE
Usage: install-packages [[-d|--install-dir] DIRECTORY]
                        [[-c|--config] CONFIGFILE]
                        [[-p|--packages] PACKAGE1,PACKAGE2[,...]]

USAGE;
    exit;
}

/* Set these options to empty by default. */
$install_dir = '';
$config_file = '';
foreach ($options[0] as $option) {
    switch ($option[0]) {
    case 'd':
    case '--install-dir':
        /* Alternate install directory requested. */
        $install_dir = ' -d php_dir=' . $option[1] .
                       ' -d test_dir=' . $option[1] . '/tests' .
                       ' -d doc_dir=' . $option[1] . '/doc' .
                       ' -d data_dir=' . $option[1] . '/data' .
                       ' -d bin_dir=' . $option[1] . '/bin';
        break;
    case 'c':
    case '--config':
        /* Alternate config file requested. */
        $config_file = ' -c ' . $option[1];
        break;
    case 'p':
    case '--packages':
        /* Only these specific packages will be installed. */
        $packages = explode(',', $option[1]);
    }
}

/* Check for the Horde channel. */
$channel_check = 'pear' . $config_file . ' list-channels';
$channels = shell_exec($channel_check);
if (strpos($channels, 'pear.horde.org') == false) {
    $channel_register = 'pear' . $config_file . ' channel-discover pear.horde.org';
    system($channel_register);

    /* Check again. */
    $channels = shell_exec($channel_check);
    if (strpos($channels, 'pear.horde.org') == false) {
        echo "\nFailed to register pear.horde.org; you must fix this before continuing.\n";
        exit(1);
    }

    echo "\n\n";
}

/* Overwrite old files, ignore dependancies (for ease of ordering),
 * upgrade if already installed, etc. */
$pear = 'pear' . $config_file . $install_dir . ' install --force --nodeps';

$dir = dirname(__FILE__);

if (!empty($packages)) {
    /* Installing only specific packages. */
    foreach ($packages as $entry) {
        $package = $dir . '/' . $entry . '/' . 'package.xml';
        if (file_exists($package)) {
            echo "Installing $entry:\n";
            system("$pear \"$package\"");
            echo "\n\n";
        }
    }
} else {
    /* Installing everything. */
    $dh = opendir($dir);
    while (($entry = readdir($dh)) !== false) {
        if ($entry == '.' || $entry == '..' || !is_dir($dir . '/' . $entry)) {
            continue;
        }

        $package = $dir . '/' . $entry . '/' . 'package.xml';
        if (file_exists($package)) {
            echo "Installing $entry:\n";
            system("$pear \"$package\"");
            echo "\n\n";
        }
    }
    closedir($dh);
}
