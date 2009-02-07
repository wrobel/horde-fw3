<?php
/**
 * $Horde: whups/ticket/comment.php,v 1.27.2.1 2009/01/06 15:28:39 jan Exp $
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('WHUPS_BASE', dirname(__FILE__) . '/..');
require_once WHUPS_BASE . '/lib/base.php';
require_once WHUPS_BASE . '/lib/Ticket.php';
require_once WHUPS_BASE . '/lib/Ticket.php';
require_once WHUPS_BASE . '/lib/Forms/AddComment.php';
require_once 'Horde/Variables.php';
require_once 'Text/Flowed.php';

$ticket = Whups::getCurrentTicket();
$vars = Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}
if ($tid = $vars->get('transaction')) {
    $history = Whups::permissionsFilter($whups_driver->getHistory($ticket->getId()),
                                        'comment', PERMS_READ);
    if (!empty($history[$tid]['comment'])) {
        $flowed = new Text_Flowed(preg_replace("/\s*\n/U", "\n", $history[$tid]['comment']));
        $vars->set('newcomment', $flowed->toFlowed(true));
    }
}

$title = sprintf(_("Comment on %s"), '[#' . $id . '] ' . $ticket->get('summary'));
$commentForm = new AddCommentForm($vars, $title);
if ($vars->get('formname') == 'addcommentform' && $commentForm->validate($vars)) {
    $commentForm->getInfo($vars, $info);

    // Add comment.
    if (!empty($info['newcomment'])) {
        $ticket->change('comment', $info['newcomment']);
    }

    if (!empty($info['user_email'])) {
        $ticket->change('comment-email', $info['user_email']);
    }

    // Add attachment if one was uploaded.
    if (!empty($info['newattachment']['name'])) {
        $ticket->change('attachment', array('name' => $info['newattachment']['name'],
                                            'tmp_name' => $info['newattachment']['tmp_name']));
    }

    // Add watch
    if (!empty($info['add_watch'])) {
        $whups_driver->addListener($ticket->getId(), '**' . $info['user_email']);
    }

    // If there was a new comment and permissions were specified on
    // it, set them.
    if (!empty($info['group'])) {
        $ticket->change('comment-perms', $info['group']);
    }

    $result = $ticket->commit();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(_("Comment added"), 'horde.success');
        $ticket->show();
    }
}

require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('comment');

$commentForm->renderActive(new Horde_Form_Renderer(), $vars, 'comment.php', 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
