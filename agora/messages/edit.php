<?php
/**
 * The Agora script to post a new message, edit an existing message, or reply
 * to a message.
 *
 * $Horde: agora/messages/edit.php,v 1.76.2.2 2009/01/06 15:22:15 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$message_parent_id = Util::getFormData('message_parent_id');

$vars = &Variables::getDefaultVariables();
$vars->set('scope', $scope);
$formname = $vars->get('formname');

/* Set up the messages control object. */
$messages = &Agora_Messages::singleton($scope, $forum_id);
if (is_a($messages, 'PEAR_Error')) {
    $notification->push(_("Could not post the message: ") . $messages->getMessage(), 'horde.warning');
    $url = Horde::applicationUrl('forums.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check edit permissions */
if (!$messages->hasPermission(PERMS_EDIT)) {
    $notification->push(sprintf(_("You don't have permission to post messages in forum %s."), $forum_id), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Check if a message is being edited. */
if ($message_id) {
    $message = $messages->getMessage($message_id);
    if (!$formname) {
        $vars = new Variables($message);
        $vars->set('message_subject', $message['message_subject']);
        $vars->set('message_body', $message['body']);
    }
    $attachment_link = $messages->getAttachmentLink($message_id);
    if ($attachment_link) {
        $vars->set('attachment_preview', $attachment_link);
    }
} else {
    $vars->set('forum_id', $forum_id);
    $vars->set('message_id', $message_id);
}

/* Get the forum details. */
$forum_name = $messages->_forum['forum_name'];

/* Set the title. */
$title = $message_parent_id ?
    sprintf(_("Post a Reply to \"%s\""), $forum_name) :
    ($message_id ? sprintf(_("Edit Message in \"%s\""), $forum_name) :
                   sprintf(_("Post a New Message to \"%s\""), $forum_name));

/* Get the form object. */
$form = $messages->getForm($vars, $title, $message_id);

/* Validate the form. */
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    /* Try and store this message and get back a new message_id */
    $message_id = $messages->saveMessage($info);
    if (is_a($message_id, 'PEAR_Error')) {
        $notification->push(_("Could not post the message: ") . $message_id->getDebugInfo(), 'horde.error');
    } else {
        $notification->push(_("Message posted."), 'horde.success');
        if (!empty($info['url'])) {
            $url = Horde::url($info['url'], true);
        } else {
            $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
        }
        header('Location: ' . $url);
        exit;
    }
}

/* Set up template */
$template = new Agora_Template();
$template->setOption('gettext', true);

/* Check if a parent message exists and set up tags accordingly. */
if ($message_parent_id) {
    $message = $messages->replyMessage($message_parent_id);
    if (!is_a($message, 'PEAR_Error')) {
        $vars->set('message_subject', $message['message_subject']);
        $vars->set('message_body_old', $message['body']);
        $template->set('message_subject', $message['message_subject']);
        $template->set('message_author', $message['message_author']);
        $template->set('message_body', $message['body']);
    } else {
        /* Bad parent message id, offer to do a regular post. */
        $message_parent_id = null;
        $vars->set('message_parent_id', '');
        $notification->push(_("Invalid parent message, you will be posting this message as a new thread."), 'horde.warning');
    }
}

$template->set('replying', $message_parent_id);
$template->set('menu', Agora::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('formbox', Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'edit.php', 'post'));

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/messages/edit.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
