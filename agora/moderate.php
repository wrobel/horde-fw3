<?php
/**
 * The Agora script to moderate any outstanding messages requiring moderation.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: agora/moderate.php,v 1.18.2.2 2009/01/06 15:22:12 jan Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Variables.php';
require_once 'Horde/UI/Pager.php';

/* Set up the messages object. */
$scope = Util::getGet('scope', 'agora');
$messages = &Agora_Messages::singleton($scope);

/* Which page are we on? Default to page 0. */
$messages_page = Util::getFormData('page', 0);
$messages_per_page = $prefs->getValue('threads_per_page');
$messages_start = $messages_page * $messages_per_page;

/* Get the sorting. */
$sort_by = Agora::getSortBy('moderate');
$sort_dir = Agora::getSortDir('moderate');

/* Get a list of messages still to moderate. Error will occur if you don't have the right premissions */
$messages_list = $messages->getModerateList($sort_by, $sort_dir);
if (is_a($messages_list, 'PEAR_Error')) {
    $notification->push($messages_list->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
} elseif (empty($messages_list)) {
    $messages_count = 0;
    $notification->push(_("No messages are waiting for moderation."), 'horde.message');
} else {
    $messages_count = count($messages_list);
    $messages_list = array_slice($messages_list, $messages_start, $messages_per_page);
}

/* Check for any actions. */
switch (Util::getFormData('action')) {
case _("Approve"):
    $message_ids = Util::getFormData('message_ids');
    $messages->moderate('approve', $message_ids);
    $notification->push(sprintf(_("%d messages was approved."), count($message_ids)), 'horde.success');
    break;

case _("Delete"):
    $message_ids = Util::getFormData('message_ids');
    $messages->moderate('delete', $message_ids);
    $notification->push(sprintf(_("%d messages was deleted."), count($message_ids)), 'horde.success');
    break;
}

/* Set up the column headers. */
$col_headers = array('forum_id' => _("Forum"), 'message_subject' => _("Subject"), 'message_author' => _("Posted by"), 'body' => _("Body"), 'message_timestamp' => _("Date"));
$col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'moderate');

/* Set up the template tags. */
$template = new Agora_Template();
$template->setOption('gettext', true);
$template->set('col_headers', $col_headers);
$template->set('messages', $messages_list);
$template->set('buttons', array(_("Approve"), _("Delete")));
$template->set('menu', Agora::getMenu('string'));
$template->set('session_tag', Util::formInput());
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

/* Set up pager. */
$vars = &Variables::getDefaultVariables();
$url = Util::addParameter(Horde::applicationUrl('moderate.php', true), 'scope', $scope);
$pager_ob = &new Horde_UI_Pager('moderate_page', $vars, array('num' => $messages_count, 'url' => $url, 'perpage' => $messages_per_page));
$pager_ob->preserve('agora', Util::getFormData('agora'));
$template->set('pager_link', $pager_ob->render(), true);

$title = _("Messages Awaiting Moderation");
require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/moderate/moderate.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
