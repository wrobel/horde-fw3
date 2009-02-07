<?php
/**
 * List management script.
 *
 * $Horde: jonah/lists/index.php,v 1.25 2008/05/31 21:15:00 chuck Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';
require_once 'Horde/Array.php';

$news = Jonah_News::factory();

/* Redirect to the news index if no channel_id is specified. */
$channel_id = Util::getFormData('channel_id');
if (empty($channel_id)) {
    $notification->push(_("No channel requested."), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

$channel = $news->getChannel($channel_id);
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

$have_news = Jonah_News::getAvailableTypes();
if (empty($have_news)) {
    $notification->push(_("News is not enabled."), 'horde.warning');
    $url = Horde::applicationUrl('index.php', true);
    header('Location: ' . $url);
    exit;
}

$num_recipients = 0;
$delivery_drivers = Jonah_Delivery::getDrivers();
foreach ($delivery_drivers as $driver => $desc) {
    $delivery = &Jonah_Delivery::singleton($driver);
    if ($list = $delivery->getRecipients($channel_id)) {
        /* Sort list, case insensitive by recipient. */
        $helper = new Horde_Array_Sort_Helper;
        uksort($list, array($helper, 'compareKeys'));
        $recipients[$driver] = $list;
        $num_recipients += count($list);
    }
}

/* Do paging of list. */
require_once 'Horde/UI/Pager.php';

/* Get current page. */
$page = Util::getFormData('page', 0);

$per_page = 40;
$min = $page * $per_page;
while ($min > $num_recipients) {
    $page--;
    $min = $page * $per_page;
}
$max = $min + $per_page;

$start = ($page * $per_page) + 1;
$end = min($num_recipients, $start + $per_page - 1);

/* Prepare list for display. */
$list = array();
if (empty($recipients)) {
    $notification->push(_("No recipients."), 'horde.warning');
} else {
    $delete_url_base = Horde::applicationUrl('lists/delete.php');
    $delete_img = Horde::img('delete.png', _("Unsubscribe recipient"), null, $registry->getImageDir('horde'));
    /* Build recipient specific fields, loop through drivers first then
     * recipients. TODO: Some sorting? */
    $r_count = 0;
    foreach ($recipients as $driver => $recp) {
        foreach ($recp as $key => $value) {
            /* Get only the section required for the current page. */
            if ($r_count++ < $min || $r_count > $max) {
                continue;
            }
            $delete_url = Util::addParameter($delete_url_base,
                                             array('driver' => $driver,
                                                   'recipient' => $key,
                                                   'channel_id' => $channel_id));
            $list[] = array('delete_link' => Horde::link($delete_url) . $delete_img . '</a>',
                            'recipient' => $key,
                            'driver'    => $delivery_drivers[$driver],
                            'name'      => $value['name']);
        }
    }
}

/* Set up the template action links. */
$actions = array();
$actions[_("New subscriber")] = Util::addParameter(Horde::applicationUrl('lists/edit.php'), 'channel_id', $channel_id);

$template = new Horde_Template();
$template->set('actions', Jonah::setupActions($actions));
$template->set('header', sprintf(_("Current subscribers to %s"), htmlspecialchars($channel['channel_name'])));

/* Paging details. */
$page = '';
$pager = '';
if ($num_recipients) {
    require_once 'Horde/Variables.php';
    $url = Util::addParameter('lists/index.php', 'channel_id', $channel_id);
    $vars = Variables::getDefaultVariables();
    $pager = new Horde_UI_Pager('page', $vars, array('num' => $r_count, 'url' => $url, 'page_count' => 10, 'perpage' => $per_page));
    $pager = $pager->render($page, $r_count, $url);
    $page = sprintf(_("%s to %s of %s"), $start, $end, $num_recipients);
}
$template->set('page', $page);
$template->set('pager', $pager);
$template->set('listheaders', array(_("Recipient"), _("Method"), _("Name")));
$template->set('recipients', $list, true);
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('stories_link', Util::addParameter(Horde::applicationUrl('stories/'), array('channel_id' => $channel_id)));

$title = $template->get('header');
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/lists/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
