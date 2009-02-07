#!/usr/bin/php
<?php
/**
 * Upgrades counters
 *
 * TODO Get rid of moderation
 *
 * $Horde: agora/scripts/upgrades/2006-10-28_update_counts.php,v 1.7 2007/01/13 10:21:11 duck Exp $
 *
 */

// No need for auth.
@define('AUTH_HANDLER', true);

// Find the base file paths.
@define('AGORA_BASE', dirname(dirname(dirname(__FILE__))));
@define('HORDE_BASE', dirname(AGORA_BASE));

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

require_once 'DB.php';
require_once HORDE_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';

/* Open the database. */
$db = &DB::connect($conf['sql']);
if (is_a($db, 'PEAR_Error')) {
    var_dump($db);
    exit;
}
$db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

/* Get threads. */
$sql = 'SELECT message_id, forum_id FROM agora_messages WHERE message_thread = ?';
$threads = $db->getAssoc($sql, false, array(0));
if (is_a($threads, 'PEAR_Error')) {
    var_dump($threads);
    exit;
}

/* Reset message count */
$db->query('UPDATE agora_messages SET message_seq = 0');
echo 'Processing ' . count($threads) . ' threads' . "\n";

$sql = 'SELECT message_thread, COUNT(*) FROM agora_messages WHERE message_thread > ? GROUP BY message_thread';
$counts = $db->getAssoc($sql, false, array(0));
if (is_a($counts, 'PEAR_Error')) {
    var_dump($counts);
    exit;
}

/* Update the number of messages in thread */
$forums = array();
foreach ($threads as $message_id => $forum_id) {
    if (!isset($counts[$message_id])) {
        continue;
    }
    $count = $counts[$message_id];
    $db->query('UPDATE agora_messages SET message_seq = ? WHERE message_id = ?', array($count, $message_id));

    if (!isset($forums[$forum_id])) {
        $forums[$forum_id] = array('threads' => 0,
                                   'messages' => 0,
                                   'forum_id' => $forum_id);
    }

    $forums[$forum_id]['threads'] += 1;
    $forums[$forum_id]['messages'] += ($count + 1);
}

echo "Update forums \n";

/* Update thread and message count for forums */
$db->query('UPDATE agora_forums SET thread_count = 0, message_count = 0');
$sth = $db->prepare('UPDATE agora_forums SET thread_count = ?, message_count = ? WHERE forum_id = ?');
$result = $db->executeMultiple($sth, $forums);
if (is_a($result, 'PEAR_Error')) {
    var_dump($result);
    exit;
}

echo "Clean cache \n";

/* Clean cache */
$forums = Agora_Messages::singleton('agora');
foreach ($forums->getForums(0, false) as $forum_id) {
    @$forums->cleanCache($forum_id);
}

echo "done\n";
