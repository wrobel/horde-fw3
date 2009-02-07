<?php
/**
 * The Agora script to notify moderators of a abuse
 *
 * Copyright 2006-2007 duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: agora/messages/abuse.php,v 1.7.2.1 2008/01/02 04:10:59 chuck Exp $
 */

@define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Form.php';
require_once 'Horde/Variables.php';
require_once 'Horde/Form/Renderer.php';

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

/* We have any moderators? */
$forum = $messages->getForum();
if (!isset($forum['moderators'])) {
    $notification->push(_("No moderators are associated with this forum."), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the form object. */
$vars = Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Report as abuse"));
$form->setButtons(array(_("Report as abuse"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

if ($form->validate()) {

    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);

    if ($vars->get('submitbutton') == _("Cancel")) {
        header('Location: ' . $url);
        exit;
    }

    /* Collect moderators emails, and send them the notify */
    require_once 'Horde/Identity.php';
    $emails = array();
    foreach ($forum['moderators'] as $moderator) {
        $identity = &Identity::singleton('none', $moderator);
        $address = $identity->getValue('from_addr');
        if (!empty($address)) {
            $emails[] = $address;
        }
    }

    if (empty($emails)) {
        header('Location: ' . $url);
        exit;
    }

    require_once 'Horde/MIME/Mail.php';
    $mail = new MIME_Mail(sprintf(_("Message %s reported as abuse"),
                                  $message_id),
                          $url . "\n\n" . Auth::getAuth() . "\n\n" . $_SERVER["REMOTE_ADDR"],
                          $emails, $emails[0], NLS::getCharset());
    $mail->send($conf['mailer']['type'], $conf['mailer']['params']);

    $notification->push($subject, 'horde.success');
    header('Location: ' . $url);
    exit;
}

/* Set up template data. */
$template = new Agora_Template();
$template->set('menu', Agora::getMenu('string'));
$template->set('formbox', Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'abuse.php', 'post'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('message_subject', $message['message_subject']);
$template->set('message_author', $message['message_author']);
$template->set('message_date', strftime($prefs->getValue('date_format'), $message['message_timestamp']));
$template->set('message_body', Agora_Messages::formatBody($message['body']));

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/messages/form.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
