<?php
/**
 * Script to load recipients for a distribution list.
 *
 * $Horde: jonah/scripts/load_delivery_recipients.php,v 1.9 2008/01/02 11:13:19 jan Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Jonah
 */

/* Channel id for which to load the distribution list. */
$channel_id = '1';

/* Which delivery driver? */
$delivery_driver = 'email';

/* File containing the recipients.
 * Format: <name>\t<recipient>\n */
$file = 'recipients.csv';

/* Really do this? */
$live = false;

/* The main stuff. */
@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';
require_once 'Horde/CLI.php';

if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

/* Load the CLI environment - make sure there's no time limit, init
 * some variables, etc. */
Horde_CLI::init();

/* Make sure there's no compression. */
@ob_end_clean();

$news = Jonah_News::factory();

$data = file_get_contents($file);
$data = str_replace("\r\n", "\n", $data);
$data = explode("\n", $data);
$recipients = array();
foreach ($data as $line) {
    $parts = explode("\t", $line);
    if (empty($parts[1])) {
        continue;
    }
    $recipients[] = array('name'      => $parts[0],
                          'recipient' => $parts[1]);
}
$delivery = &Jonah_Delivery::singleton($delivery_driver);
$i = array('channel_id' => $channel_id);
$rMax = count($recipients);
for ($r = 0; $r < $rMax; $r++) {
    $i['name']      = $recipients[$r]['name'];
    $i['recipient'] = $recipients[$r]['recipient'];
    if ($live) {
        $delivery->saveRecipient($i);
    }
    printf("%s: %s [%s]\n",
           $r, $recipients[$r]['recipient'], $recipients[$r]['name']);
}
