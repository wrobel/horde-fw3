#!/usr/bin/php
<?php
/**
 * $Horde: trean/scripts/upgrades/2006-12-29_sql_bookmarks.php,v 1.3 2007/01/10 18:09:49 chuck Exp $
 *
 * This script converts bookmarks to the new Trean SQL structure. It
 * is entirely non-destructive and can be tested safely after creating
 * the new tables.
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
require_once 'MDB2.php';
$config = Horde::getDriverConfig('datatree', 'sql');
unset($config['charset']);
$mdb2 = &MDB2::factory($config);

// Prepare statements.
$trean_bookmarks = $mdb2->prepare('INSERT INTO trean_bookmarks (bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_rating, bookmark_http_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                                  array('integer', 'integer', 'text', 'text', 'text', 'integer', 'integer', 'text'));
if (is_a($trean_bookmarks, 'PEAR_Error')) {
    var_dump($trean_bookmarks);
    exit;
}

// Get list of bookmarks.
$sql = "SELECT distinct d.datatree_id FROM horde_datatree_attributes da LEFT JOIN horde_datatree d ON da.datatree_id = d.datatree_id WHERE da.attribute_name = 'url' AND d.group_uid = 'horde.shares.trean'";
$ids = $mdb2->queryCol($sql);

$copied = 0;
$skipped_err = 0;
$skipped_fid = 0;
$skipped_vfs = 0;
foreach ($ids as $datatreeId) {
    $all = $mdb2->queryAll('SELECT attribute_name, attribute_value FROM horde_datatree_attributes WHERE datatree_id = ' . (int)$datatreeId, null, MDB2_FETCHMODE_ASSOC, true);

    $b_url = '';
    $b_title = '';
    $b_description = '';
    $b_clicks = null;
    $b_rating = array();
    $b_http_status = null;
    $folder_id = null;

    foreach ($all as $name => $value) {
        switch ($name) {
        // base bookmark
        case 'url':
            $b_url = $value;
            break;
        case 'title':
            $b_title = $value;
            break;
        case 'description':
            $b_description = $value;
            break;
        case 'clicks':
            $b_clicks = $value;
            break;
        case 'rating':
            $b_rating = $value;
            break;
        case 'http-status':
            $b_http_status = $value;
            break;
        }
    }

    // Find the folder id.
    $parents = $mdb2->queryOne('SELECT datatree_parents FROM horde_datatree WHERE datatree_id = ' . (int)$datatreeId);
    if (is_a($parents, 'PEAR_Error') || is_null($parents)) {
        ++$skipped_fid;
        continue;
    }
    $parents = explode(':', $parents);
    if (count($parents)) {
        $folder_id = $parents[count($parents) - 1];
    }
    if (is_null($folder_id)) {
        ++$skipped_fid;
        continue;
    }

    // Run inserts.
    $bookmark_id = $mdb2->nextId('trean_bookmarks');
    if (is_a($bookmark_id, 'PEAR_Error')) {
        echo 'DEBUG (nextId): ' . $bookmark_id->getMessage() . "\n";
        ++$skipped_err;
        continue;
    }
    $result = $trean_bookmarks->execute(array($bookmark_id, $folder_id, $b_url, $b_title, $b_description, $b_clicks, $b_rating, $b_http_status));
    if (is_a($result, 'PEAR_Error')) {
        echo 'DEBUG (trean_bookmarks): ' . $result->getMessage() . "\n";
        var_dump($result); exit;
        ++$skipped_err;
        continue;
    }

    ++$copied;
}

echo wordwrap("\nCopied $copied bookmarks, skipped $skipped_fid for missing folder ids, and $skipped_err for errors.\n\nThis script has not deleted or modified any data, only created the new structure. You should now run 2006-12-29_cleanup.php to remove the old bookmarks and other deprecated data, but we strongly recommend you back up your data and test the new system before doing so.\n");
