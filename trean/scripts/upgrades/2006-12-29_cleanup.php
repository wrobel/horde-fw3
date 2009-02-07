#!/usr/bin/php
<?php
/**
 * $Horde: trean/scripts/upgrades/2006-12-29_cleanup.php,v 1.1 2006/12/31 22:34:29 chuck Exp $
 *
 * Trean SQL bookmarks conversion cleanup script.
 */

@define('AUTH_HANDLER', true);
@define('TREAN_BASE', dirname(__FILE__) . '/../..');
@define('HORDE_BASE', TREAN_BASE . '/..');

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

require_once TREAN_BASE . '/lib/base.php';
require_once 'VFS.php';
require_once 'MDB2.php';
$config = Horde::getDriverConfig('datatree', 'sql');
unset($config['charset']);
$mdb2 = &MDB2::factory($config);

// Favicons are handled differently in the new system.
$vfs_params = Horde::getVFSConfig('favicons');
if (!is_a($vfs_params, 'PEAR_Error')) {
    $vfs = &VFS::singleton($vfs_params['type'], $vfs_params['params']);
}

// Delete favicons from VFS
$result = $vfs->emptyFolder('.horde/trean/favicons/');
if (is_a($result, 'PEAR_Error')) {
    echo wordwrap($result->getMessage()) . "\n";
}

// Bookmark folders no longer need a "type" attribute.
$sql = "SELECT distinct d.datatree_id FROM horde_datatree_attributes da LEFT JOIN horde_datatree d ON da.datatree_id = d.datatree_id WHERE da.attribute_name = 'type' AND d.group_uid = 'horde.shares.trean'";
$ids = $mdb2->queryCol($sql);
foreach ($ids as $datatreeId) {
    $mdb2->exec('DELETE FROM horde_datatree_attributes WHERE attribute_name = \'type\' AND datatree_id = ' . (int)$datatreeId);
}

// Bookmarks are no longer in the DataTree at all.
$sql = "SELECT distinct d.datatree_id FROM horde_datatree_attributes da LEFT JOIN horde_datatree d ON da.datatree_id = d.datatree_id WHERE da.attribute_name = 'url' AND d.group_uid = 'horde.shares.trean'";
$ids = $mdb2->queryCol($sql);
foreach ($ids as $datatreeId) {
    $mdb2->exec('DELETE FROM horde_datatree_attributes WHERE datatree_id = ' . (int)$datatreeId);
    $mdb2->exec('DELETE FROM horde_datatree WHERE datatree_id = ' . (int)$datatreeId);
}

echo wordwrap("\nOld data is now gone. Enjoy your shiny new bookmarks!\n");
