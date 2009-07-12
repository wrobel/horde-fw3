#!/usr/bin/env php
<?php
/**
 * Correct geolocation data
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../../../');
@define('ANSEL_BASE', HORDE_BASE . '/ansel');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';
// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_CLI::init();
$cli = &Horde_CLI::singleton();

require_once ANSEL_BASE . '/lib/base.php';

$sql = 'SELECT image_id, image_latitude, image_longitude FROM ansel_images_geolocation;';
$results = $ansel_db->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
$sql = $ansel_db->prepare('UPDATE ansel_images SET image_latitude = ?, image_longitude = ? WHERE image_id = ?');
foreach ($results as $image) {
    $cli->message(sprintf("Image %d updated. %s - %s", $image['image_id'], $image['image_latitude'], $image['image_longitude']), 'cli.message');
    $sql->execute(array($image['image_latitude'], $image['image_longitude'], $image['image_id']));
}

$cli->message('Done.', 'cli.success');
