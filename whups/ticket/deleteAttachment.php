<?php
/**
 * Displays and handles the form to delete an attachment from the ticket.
 *
 * $Horde: whups/ticket/deleteAttachment.php,v 1.6.2.1 2009/01/06 15:28:39 jan Exp $
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('WHUPS_BASE', dirname(__FILE__) . '/..');
require_once WHUPS_BASE . '/lib/base.php';
require_once WHUPS_BASE . '/lib/Ticket.php';
require_once 'Horde/Variables.php';

$ticket = Whups::getCurrentTicket();
if (!Whups::hasPermission($ticket->get('queue'), 'queue', PERMS_DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    header('Location: ' . Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true));
    exit;
}

$file = basename(Util::getFormData('file'));
$ticket->change('delete-attachment', $file);
$result = $ticket->commit();
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result, 'horde.error');
} else {
    $notification->push(sprintf(_("Attachment %s deleted."), $file), 'horde.success');
}

if ($url = Util::getFormData('url')) {
    header('Location: ' . $url);
} else {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true));
}