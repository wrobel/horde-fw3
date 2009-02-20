<?php
/**
 * The Agora script merge two threads.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: agora/messages/merge.php,v 1.10.2.1 2008/01/02 04:10:59 chuck Exp $
 */

@define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';
require_once 'Horde/Form/Action.php';

/* Set up the messages object. */
list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$messages = &Agora_Messages::singleton($scope, $forum_id);
if (is_a($messages, 'PEAR_Error')) {
    $notification->push($messages->getMessage(), 'horde.warning');
    $url = Horde::applicationUrl('forums.php', true);
    header('Location: ' . $url);
    exit;
}

/* Get requested message, if fail then back to forums list. */
$message = $messages->getMessage($message_id);
if (is_a($message, 'PEAR_Error')) {
    $notification->push(sprintf(_("Could not open the message. %s"), $message->getMessage()), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
}

/* Check delete permissions */
if (!$messages->hasPermission(PERMS_DELETE)) {
    $notification->push(sprintf(_("You don't have permission to delete messages in forum %s."), $forum_id), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the form object. */
$vars = &Variables::getDefaultVariables();
$form = new Horde_Form($vars, sprintf(_("Merge \"%s\" with another thread"), $message['message_subject']));
$form->setButtons(array(_("Merge"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

$action_submit = Horde_Form_Action::factory('submit');
$threads_list = array();
foreach ($messages->getThreads(0, false, 'message_subject', 0) as $id => $thread) {
    $threads_list[$id] = $thread['message_subject'];
}

$v = &$form->addVariable(_("With Thread: "), 'new_thread_id', 'enum', true, false, null, array($threads_list));
$v->setAction($action_submit);
$v->setOption('trackchange', true);

if ($vars->get('new_thread_id')) {
    $message_list = array();
    foreach ($messages->getThreads($vars->get('new_thread_id'), true, 'message_timestamp') as $id => $thread) {
        $message_list[$id] = $thread['message_subject'] . ' (' . $thread['message_author'] . ' ' . $thread['message_date'] . ')';
    }
    $form->addVariable(_("After Message: "), 'after_message_id', 'enum', true, false, null, array($message_list));
}

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo($vars, $info);

    if ($vars->get('submitbutton') == _("Merge")) {
        $merge = $messages->mergeThread($message_id, $info['after_message_id']);
        if (is_a($merge, 'PEAR_Error')) {
            $notification->push($merge->getMessage(), 'horde.error');
        } else {
            $notification->push(sprintf(_("Thread %s merged with thread %s after message %s."), $message_id, $info['new_thread_id'], $info['after_message_id']), 'horde.error');
            header('Location: ' . Agora::setAgoraId($forum_id, $info['new_thread_id'], Horde::applicationUrl('messages/index.php', true), $scope));
            exit;
        }
    }
}

/* Template object. */
$template = new Agora_Template();
$template->set('menu', Agora::getMenu('string'));
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'merge.php', 'post'));
$template->set('message_subject', $message['message_subject']);
$template->set('message_author', $message['message_author']);
$template->set('message_body', Agora_Messages::formatBody($message['body']));

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';