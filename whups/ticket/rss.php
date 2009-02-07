<?php
/**
 * $Horde: whups/ticket/rss.php,v 1.12.2.1 2009/01/06 15:28:39 jan Exp $
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('WHUPS_BASE', dirname(__FILE__) . '/..');
require_once WHUPS_BASE . '/lib/base.php';
require_once 'Horde/Template.php';

$ticket = Util::getFormData('id');
$ticket = preg_replace('|\D|', '', $ticket);
if (!$ticket) {
    exit;
}

// Get the ticket details first.
$details = $whups_driver->getTicketDetails($ticket);
if (is_a($details, 'PEAR_Error')) {
    exit;
}

// Check permissions on this ticket.
if (!count(Whups::permissionsFilter(array($details['queue'] => ''), 'queue', PERMS_READ))) {
    exit;
}

$history = Whups::permissionsFilter($whups_driver->getHistory($ticket),
                                    'comment', PERMS_READ);
$items = array();
$self = Whups::urlFor('ticket', $ticket, true, -1);
foreach (array_keys($history) as $i) {
    if (!isset($history[$i]['comment_text'])) {
        continue;
    }
    $items[$i]['title'] = htmlspecialchars(substr($history[$i]['comment_text'], 0, 60));
    $items[$i]['description'] = htmlspecialchars($history[$i]['comment_text']);
    $items[$i]['pubDate'] = htmlspecialchars(date('r', $history[$i]['timestamp']));
    $items[$i]['url'] = $self . '#t' . $i;
}

$template = new Horde_Template();
$template->set('charset', NLS::getCharset());
$template->set('xsl', $registry->get('themesuri') . '/feed-rss.xsl');
$template->set('pubDate', htmlspecialchars(date('r')));
$template->set('title', htmlspecialchars($details['summary']));
$template->set('items', $items, true);
$template->set('url', Whups::urlFor('ticket', $ticket, true));
$template->set('rss_url', Whups::urlFor('ticket_rss', $ticket, true));
$template->set('description', htmlspecialchars($details['summary']));

$browser->downloadHeaders($details['summary'] . '.rss',
                          'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
