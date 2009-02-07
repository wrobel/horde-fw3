<?php
/**
 * Script to add or edit recipients for a list.
 *
 * Copyright 2004-2007 Roel Gloudemans <roel@gloudemans.info>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * $Horde: jonah/lists/delete.php,v 1.8 2008/05/31 21:15:00 chuck Exp $
 */

@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';
require_once 'Horde/Variables.php';

$news = Jonah_News::factory();

$recipient['channel_id'] = Util::getFormData('channel_id');
$recipient['recipient']  = Util::getFormData('recipient');
$delivery_type           = Util::getFormData('driver');

if (!isset($recipient['recipient']) || !isset($delivery_type) || !isset($recipient['channel_id'])) {
  $notification->push(_("Invalid request to delete subscriber."), 'horde.error');
  $url = Horde::applicationUrl('delivery/index.php', true);
  header('Location: ' . $url);
  exit;
}

/* Get requested channel. */
$channel = $news->getChannel($recipient['channel_id']);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, __FILE__, __LINE__, PEAR_LOG_ERR);
    $notification->push(_("Invalid channel."), 'horde.error');
    $url = Horde::applicationUrl('delivery/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check if allowed */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), PERMS_EDIT, $recipient['channel_id'])) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

$delivery = &Jonah_Delivery::singleton($delivery_type);

if ($delivery->removeRecipient($recipient)) {
    $notification->push(_("Recipient successfully unsubscribed."), 'horde.success');
} else {
    $notification->push(_("Unsubscribe failed."), 'horde.error');
}

$url = Util::addParameter('lists/index.php', 'channel_id', $recipient['channel_id']);
$url = Horde::applicationUrl($url, true);
header('Location: ' . $url);
