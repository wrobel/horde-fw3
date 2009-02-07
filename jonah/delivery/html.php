<?php
/**
 * Script to handle requests for email delivery of stories.
 *
 * $Horde: jonah/delivery/html.php,v 1.19 2008/01/02 11:13:17 jan Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

$session_control = 'readonly';
@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';

// TODO - check if a user, have button to add channel to their
// personal aggregated channel.

$news = Jonah_News::factory();

/* Get the id and format of the channel to display. */
$channel_id = Util::getFormData('channel_id');

/* Get requested channel. */
$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, __FILE__, __LINE__, PEAR_LOG_ERR);
    $notification->push(_("Invalid channel."), 'horde.error');
    $url = Horde::applicationUrl('delivery/index.php', true);
    header('Location: ' . $url);
    exit;
}

$title = sprintf(_("HTML Delivery for \"%s\""), $channel['channel_name']);

require JONAH_BASE . '/config/templates.php';
$channel_format = Util::getFormData('format', key($templates));
$options = array();
foreach ($templates as $key => $info) {
    $options[] = '<option value="' . $key . '"' . ($key == $channel_format ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
}

$template = new Horde_Template();
$template->setOption('gettext', 'true');
$template->set('url', Horde::selfUrl());
$template->set('session', Util::formInput());
$template->set('channel_id', $channel_id);
$template->set('channel_name', $channel['channel_name']);
$template->set('format', $channel_format);
$template->set('options', $options);
$template->set('stories', $news->renderChannel($channel_id, $channel_format));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/delivery/html.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
