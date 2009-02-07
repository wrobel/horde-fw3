<?php
/**
 * Thread display script
 *
 * $Horde: agora/messages/index.php,v 1.50.2.2 2009/01/06 15:22:15 jan Exp $
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
require_once 'Horde/Identity.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';
require_once 'Horde/UI/Pager.php';

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

/* Check if we must show bodies */
if (($view_bodies = Util::getGet('bodies')) !== null) {
    $prefs->setValue('thread_view_bodies', $view_bodies);
} else {
    $view_bodies = $prefs->getValue('thread_view_bodies');
}

/* Get view settings. */
$sort_by = ($view_bodies == 1)  ? 'message_thread' : Agora::getSortBy('thread');
$sort_dir = Agora::getSortDir('thread');
$forum = $messages->getForum();
$title = $forum['forum_name'] . ' :: ' . $message['message_subject'];
$thread_page = Util::getFormData('thread_page');

/* Count = replies + opening thread */
$thread_count = $messages->countThreads($message['message_thread']) + 1;

/* Log thread views. */
$seen = $messages->logView($message['message_thread']);

/* Set thread page views */
if ($view_bodies == 2) {
    if ($thread_page === null && !$seen) {
        /* Jump to the last page, if we already seen the thread */
        $thread_page = max(ceil($thread_count / $prefs->getValue('thread_per_page')) - 1, 0);
    }
    $thread_per_page = $prefs->getValue('thread_per_page');
    $thread_start = $thread_page * $thread_per_page;
} else {
    $thread_page = 0;
    $thread_per_page = 0;
    $thread_start = 0;
}

/* Set up template */
$template = new Agora_Template();
$template->setOption('gettext', true);

if (!$view_bodies) {

    /* Get the author's avatar. */
    if ($conf['avatar']['allow_avatars']) {
        $identity = &Identity::singleton('none', $message['message_author']);
        $avatar_path = $identity->getValue('avatar_path');
        $message_author_avatar = Agora::validateAvatar($avatar_path) ? Agora::getAvatarUrl($avatar_path) : false;
        $template->set('message_author_avatar', $message_author_avatar);
    }

    $template->set('message_id', $message['message_id']);
    $template->set('message_author', sprintf(_("Posted by %s on %s"), htmlspecialchars($message['message_author']), $messages->dateFormat($message['message_timestamp'])));
    if (isset($message['message_author_moderator'])) {
        $template->set('message_author_moderator', 1);
    }
    $template->set('message_subject', $message['message_subject']);
    $template->set('message_body', Agora_Messages::formatBody($message['body']));
    $template->set('message_attachment', $messages->getAttachmentLink($message_id));

    $template_file = AGORA_TEMPLATES . '/messages/message.html';

} else {

    $template_file = AGORA_TEMPLATES . '/messages/index.html';

}

/* Actions. */
$actions = array();

/* Check if the thread allows replies. */
if (!$message['locked']) {
    $url = Agora::setAgoraId($forum_id, null, Horde::applicationUrl('messages/edit.php'));
    $url = Util::addParameter($url, 'message_parent_id', $message_id);
    $actions[] = Horde::link($url, _("Reply")) . _("Reply") . '</a>';
}

/* Add admin permissons */
if ($messages->hasPermission(PERMS_DELETE)) {
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/edit.php'));
    $actions[] = Horde::link($url, _("Edit")) . _("Edit") . '</a>';

    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/delete.php'));
    $actions[] = Horde::link($url, _("Delete")) . _("Delete") . '</a>';

    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/lock.php'));
    $label = ($message['locked']) ? _("Unlock thread") : _("Lock thread");
    $actions[] = Horde::link($url, $label) . $label . '</a>';
}

/* Get the message array and the sorted thread list. */
$threads_list = $messages->getThreads($message['message_thread'], true, $sort_by, $sort_dir, ($view_bodies ? 1 : 0), '', null, $thread_start, $thread_per_page);
if (is_a($threads_list, 'PEAR_Error')) {
    $notification->push($threads_list->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
}

/* Set up pager. */
if ($thread_count > $thread_per_page && $view_bodies == 2) {
    $vars = new Variables(array('thread_page' => $thread_page));
    $pager_ob = new Horde_UI_Pager('thread_page', $vars, array('num' => $thread_count, 'url' => 'messages/index.php', 'perpage' => $thread_per_page));
    $pager_ob->preserve('agora', Util::getFormData('agora'));
    $template->set('pager_link', $pager_ob->render());
}

/* Set up the column headers. */
$col_headers = array(array('message_thread' => _("Thread"), 'message_subject' => _("Subject")), 'message_author' => _("Posted by"), 'message_timestamp' => _("Date"));
$col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'thread');

/* Actions. */
$url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php'));

/* Get the thread table. */
switch ($view_bodies) {

    case '2':
        $threads_template = AGORA_TEMPLATES . '/messages/flat.html';
        if (!$prefs->isLocked('thread_view_bodies')) {
            $actions[] = Horde::link(Util::addParameter($url, 'bodies', 0), _("Hide bodies")) . _("Hide bodies") . '</a>';
            $actions[] = Horde::link(Util::addParameter($url, 'bodies', 1), _("Thread")) . _("Thread") . '</a>';
        }
        $threads = $messages->getThreadsUI($threads_list, $col_headers, $view_bodies, $threads_template);
        break;

    case '1':
        $threads_template = AGORA_TEMPLATES . '/messages/flat_thread.html';
        if (!$prefs->isLocked('thread_view_bodies')) {
            $actions[] = Horde::link(Util::addParameter($url, 'bodies', 0), _("Hide bodies")) . _("Hide bodies") . '</a>';
            $actions[] = Horde::link(Util::addParameter($url, 'bodies', 2), _("Flat")) . _("Flat") . '</a>';
        }

        /* Resort messages by thread */
        require_once 'Horde/Tree.php';
        require_once AGORA_BASE  . '/lib/Tree/flat.php';
        $tree = new Horde_Tree_agoraflat('flatthread', array());
        foreach ($threads_list as $node) {
            $tree->addNode($node['message_id'], $node['parent'], $node['body'], $node['indent'], true, array(), $node);
        }

        $threads = $tree->getTree();
        break;

    default:
        $threads_template = false;
        if (!$prefs->isLocked('thread_view_bodies')) {
            $actions[] = Horde::link(Util::addParameter($url, 'bodies', 1), _("View bodies")) . _("View bodies") . '</a>';
        }
        $threads = $messages->getThreadsUI($threads_list, $col_headers, $view_bodies, $threads_template);
        break;
}

/* Set up the main template tags. */
$template->set('menu', Agora::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('actions', $actions);
$template->set('threads', $threads);

/* Display an edit-dialogue if the thread is not locked and we can edit messages in them. */
if (!$messages->hasPermission(PERMS_EDIT)) {
    $message = sprintf(_("You don't have permission to post messages in forum %s."), $forum['forum_name']);
    if (!empty($conf['hooks']['permsdenied'])) {
        $message = Horde::callHook('_perms_hook_denied', array('agora'), 'horde', $message);
    }
    $template->set('form', $message);
} elseif ($message['locked']) {
    $template->set('form', _("Thread locked."));
} else {
    $reply = $messages->replyMessage($message);
    $vars = Variables::getDefaultVariables();
    $vars->set('forum_id', $forum_id);
    $vars->set('message_parent_id', $message_id);
    $vars->set('message_subject', $reply['message_subject']);
    $vars->set('message_body_old', $reply['body']);
    $form = $messages->getForm($vars, sprintf(_("Post a Reply to \"%s\""), $reply['message_subject']));
    $template->set('form', Util::bufferOutput(array($form, 'renderActive'), null, null, 'edit.php', 'post', null, false));
}

Horde::addScriptFile('stripe.js', 'horde', true);
require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch($template_file);
require $registry->get('templates', 'horde') . '/common-footer.inc';
