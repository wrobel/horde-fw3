<?php
/**
 * Script to handle requests for email delivery of stories.
 *
 * $Horde: jonah/delivery/email.php,v 1.26 2008/01/02 11:13:17 jan Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Action.php';
require_once 'Horde/Variables.php';

if (Auth::getAuth()) {
    require_once 'Horde/Identity.php';
    $identity = &Identity::singleton();
}

$news = Jonah_News::factory();
$delivery = &Jonah_Delivery::singleton('email');

/* Set up the form variables. */
$vars = Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');
$confirm = $vars->get('confirm');

/* Check if this is just a confirmation of a previous action. */
if (!empty($confirm)) {
    $result = $delivery->confirmRequest($confirm, $vars->get('to'));
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Unable to confirm request."), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(_("Request confirmed."), 'horde.success');
    }
    $url = Horde::applicationUrl('delivery/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Get requested channel. */
$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, __FILE__, __LINE__, PEAR_LOG_ERR);
    $notification->push(_("Invalid channel."), 'horde.error');
    $url = Horde::applicationUrl('delivery/index.php', true);
    header('Location: ' . $url);
    exit;
}

$form = new Horde_Form($vars);

/* Set up the form. */
$title = sprintf(_("Email Delivery for \"%s\""), $channel['channel_name']);
$form->setTitle($title);
$form->setButtons(_("Save"), true);
$v = &$form->addVariable(_("What do you want to do?"), 'action', 'enum', true,
                         false, null,
                         array(array('join'  => _("Join this channel"),
                                     'leave' => _("Leave this channel"))));
$v->setDefault('join');
$v->setAction(Horde_Form_Action::factory('submit'));
$external = Util::getFormData('external');
$v->setOption('trackchange', empty($external));

$form->addHidden('', 'url', 'text', false);
$form->addHidden('', 'channel_id', 'int', false);
$v = &$form->addVariable(_("Email address"), 'email', 'email', true);
if (Auth::getAuth()) {
    $v->setDefault($identity->getValue('from_addr'));
}
if ($vars->get('action') != 'leave') {
    $v = &$form->addVariable(_("Name"), 'name', 'text', false);
    if (Auth::getAuth()) {
        $v->setDefault($identity->getValue('fullname'));
    }
}

/* Work around nasty trackchange bug in Horde_Form. */
if (!$form->isSubmitted()) {
    $vars->set('action', 'join');
}

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $delivery->processRequest($info);
    $notification->push(sprintf(_("A confirmation message has been sent to %s. Click on the link in that message to finally subscribe to channel \"%s\"."), $info['email'], $channel['channel_name']), 'horde.message');
    if (empty($info['url'])) {
        $info['url'] = Horde::applicationUrl('delivery/index.php', true);
    }
    header('Location: ' . $info['url']);
    exit;
}

/* Render the form. */
$template = new Horde_Template();
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, 'email.php', 'post'));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
