<?php
/**
 * The Agora script to create or edit a forum.
 *
 * $Horde: agora/editforum.php,v 1.44.2.2 2009/01/06 15:22:12 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Forms/Forum.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

/* Set up the forums object. */
$forums = &Agora_Messages::singleton();

list($forum_id) = Agora::getAgoraId();
$title = $forum_id ? _("Edit Forum") : _("New Forum");
$vars = Variables::getDefaultVariables();
$vars->set('forum_id', $forum_id);

/* Check permissions */
if ($forum_id && !$forums->hasPermission(PERMS_DELETE)) {
    $notification->push(sprintf(_("You don't have permissions to edit forum %s"), $registry->get('name', $scope)), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
} elseif (!$forums->hasPermission(PERMS_DELETE)) {
    $notification->push(sprintf(_("You don't have permissions to create a new forum in %s"), $registry->get('name', $scope)), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
}

$form = new ForumForm($vars, $title);
if ($form->validate()) {
    $forum_id = $form->execute($vars);
    if (is_a($forum_id, 'PEAR_Error')) {
        $notification->push(sprintf(_("Could not create the forum. %s"), $forum_id->message), 'horde.error');
        header('Location: ' . Horde::applicationUrl('forums.php', true));
    } else {
        $notification->push($vars->get('forum_id') ? _("Forum Modified") : _("Forum created."), 'horde.success');
        header('Location: ' . Agora::setAgoraId($forum_id, null, Horde::applicationUrl('threads.php', true)));
    }
    exit;
}

/* Check if a forum is being edited. */
if ($forum_id) {
    $vars = new Variables($forums->getForum($forum_id));
}

/* Set up template variables. */
$template = new Agora_Template();
$template->set('menu', Agora::getMenu('string'));
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), null, null, 'editforum.php', 'post'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
