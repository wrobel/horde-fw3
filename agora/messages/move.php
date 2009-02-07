<?php
/**
 * The Agora script move thread another forum.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: agora/messages/move.php,v 1.11.2.1 2008/01/02 04:10:59 chuck Exp $
 */

@define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

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
$form = new Horde_Form($vars, sprintf(_("Move \"%s\" to another forum"), $message['message_subject']));
$form->setButtons(array(_("Move"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

$forums_list = Agora::formatCategoryTree($messages->getForums(0, false));
$v = &$form->addVariable(_("Forum"), 'new_forum_id', 'enum', true, false, null, array($forums_list));
$v->setDefault($forum_id);

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo($vars, $info);

    if ($vars->get('submitbutton') == _("Move")) {
        $move = $messages->moveThread($message_id, $info['new_forum_id']);
        if (is_a($move, 'PEAR_Error')) {
            $notification->push($move->getMessage(), 'horde.error');
        } else {
            $notification->push(sprintf(_("Thread %s moved to from forum %s to %s."), $message_id, $forum_id, $info['new_forum_id']), 'horde.success');
            header('Location: ' . Agora::setAgoraId($info['new_forum_id'], $message_id, Horde::applicationUrl('messages/index.php', true), $scope));
            exit;
        }
    }
}

/* Template object. */
$template = new Agora_Template();
$template->set('menu', Agora::getMenu('string'));
$template->set('formbox', Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'move.php', 'post'));
$template->set('message_subject', $message['message_subject']);
$template->set('message_author', $message['message_author']);
$template->set('message_body', Agora_Messages::formatBody($message['body']));

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/messages/edit.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
