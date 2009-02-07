<?php
/**
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/delete.php,v 1.30 2008/01/02 11:13:16 jan Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

if (!Auth::isAdmin('jonah:admin', PERMS_DELETE)) {
    $notification->push(_("Permission Denied."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

$news = Jonah_News::factory();

/* Set up the form variables and the form. */
$vars = Variables::getDefaultVariables();
$form_submit = $vars->get('submitbutton');
$channel_id = $vars->get('channel_id');

$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    $notification->push(_("Invalid channel specified for deletion."), 'horde.message');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* If not yet submitted set up the form vars from the fetched
 * channel. */
if (empty($form_submit)) {
    $vars = new Variables($channel);
}

$title = sprintf(_("Delete News Channel \"%s\"?"), $vars->get('channel_name'));
$form = new Horde_Form($vars, $title);

$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'channel_id', 'int', true, true);

$msg = _("Really delete this News Channel?");
if ($vars->get('channel_type') == JONAH_INTERNAL_CHANNEL) {
    $msg .= ' ' . _("All stories created in this channel will be lost!");
} else {
    $msg .= ' ' . _("Any cached stories for this channel will be lost!");
}
$form->addVariable($msg, 'confirm', 'description', false);

if ($form_submit == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $news->deleteChannel($info);
        if (is_a($delete, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error deleting the channel: %s"), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("The channel has been deleted."), 'horde.success');
            $url = Horde::applicationUrl('channels/index.php', true);
            header('Location: ' . $url);
            exit;
        }
    }
} elseif (!empty($form_submit)) {
    $notification->push(_("Channel has not been deleted."), 'horde.message');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

$template = new Horde_Template();
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, 'delete.php', 'post'));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
