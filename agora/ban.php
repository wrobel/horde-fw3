<?php
/**
 * The Agora script ban users from a specific forum.
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: agora/ban.php,v 1.5.2.2 2009/01/06 15:22:12 jan Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once 'Horde/Variables.php';
require_once 'Horde/Form.php';

/* Make sure we have a forum id. */
list($forum_id, , $scope) = Agora::getAgoraId();
$forums = &Agora_Messages::singleton($scope, $forum_id);
if (is_a($forums, 'PEAR_Error')) {
    $notification->push($forums->message, 'horde.error');
    header('Location: ' . Horde::applicationUrl('forums.php'));
    exit;
}

/* Check permissions */
if (!$forums->hasPermission(PERMS_DELETE)) {
    $notification->push(sprintf(_("You don't have permissions to ban users from forum %s."), $forum_id), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('forums.php'));
    exit;
}

/* Ban action */
if (($action = Util::getFormData('action')) !== null) {
    $user = Util::getFormData('user');
    $result = $forums->updateBan($user, $forum_id, $action);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage(), 'horde.error');
    }

    $url = Agora::setAgoraId($forum_id, null, Horde::applicationUrl('ban.php'), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the list of banned users. */
$delete = Util::addParameter(Horde::applicationUrl('ban.php'),
                            array('action' => 'delete',
                                  'scope' => $scope,
                                  'forum_id' => $forum_id));
$banned = $forums->getBanned();
foreach ($banned as $user => $level) {
    $banned[$user] = Horde::link(Util::addParameter($delete, 'user', $user), _("Delete")) . $user . '</a>';
}

$title = _("Ban");
$vars = Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->addHidden('', 'scope', 'text', false);
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'action', 'text', false);
$vars->set('action', 'add');
$form->addVariable(_("User"), 'user', 'text', true);

$template = new Agora_Template();
$template->set('menu', Agora::getMenu('string'));
$template->set('formbox', Util::bufferOutput(array($form, 'renderActive'), null, null, 'ban.php', 'post'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('banned', $banned);
$template->set('forum', $forums->getForum());

require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/forums/ban.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
