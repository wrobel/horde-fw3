<?php
/**
 * $Horde: jonah/delivery/rss.php,v 1.32 2008/01/02 11:13:17 jan Exp $
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

$session_control = 'readonly';
@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/version.php';

$news = Jonah_News::factory();

/* Fetch the channel info and the story list and check they are both valid.
 * Do a simple exit in case of errors. */
$channel_id = Util::getFormData('channel_id');
$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, __FILE__, __LINE__, PEAR_LOG_ERR);
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested feed (' . $channel_id . ') was not found on this server.</p>
</body></html>';
    exit;
}

/* Check for a tag search. */
if ($tag_id = Util::getFormData('tag_id')) {
    $tag_name = array_shift($news->getTagNames(array($tag_id)));
    $stories = $news->searchTagsById(array($tag_id), 10, 0, array($channel_id));
} else {
    $stories = $news->getStories($channel_id, 10, 0, false, time());
}
if (is_a($stories, 'PEAR_Error')) {
    Horde::logMessage($stories, __FILE__, __LINE__, PEAR_LOG_ERR);
    $stories = array();
}

$feed_type = basename(Util::getFormData('type', 'rss2'));

$template = new Horde_Template();
$template->set('charset', NLS::getCharset());
$template->set('jonah', 'Jonah ' . JONAH_VERSION . ' (http://www.horde.org/jonah/)');
$template->set('xsl', $registry->get('themesuri') . '/feed-rss.xsl');
if ($tag_id) {
    $template->set('channel_name', sprintf(_("Stories tagged with %s in %s"), $tag_name, @htmlspecialchars($channel['channel_name'], ENT_COMPAT, NLS::getCharset())));
} else {
    $template->set('channel_name', @htmlspecialchars($channel['channel_name'], ENT_COMPAT, NLS::getCharset()));
}
$template->set('channel_desc', @htmlspecialchars($channel['channel_desc'], ENT_COMPAT, NLS::getCharset()));
$template->set('channel_updated', htmlspecialchars(date('r', $channel['channel_updated'])));
$template->set('channel_official', htmlspecialchars($channel['channel_official']));
$template->set('channel_rss', Util::addParameter(Horde::applicationUrl('delivery/rss.php', true, -1), array('type' => 'rss', 'channel_id' => $channel['channel_id'])));
$template->set('channel_rss2', Util::addParameter(Horde::applicationUrl('delivery/rss.php', true, -1), array('type' => 'rss2', 'channel_id' => $channel['channel_id'])));
foreach ($stories as $key => $story) {
    $stories[$key]['story_title'] = @htmlspecialchars($story['story_title'], ENT_COMPAT, NLS::getCharset());
    $stories[$key]['story_desc'] = @htmlspecialchars($story['story_desc'], ENT_COMPAT, NLS::getCharset());
    $stories[$key]['story_link'] = htmlspecialchars($story['story_link']);
    $stories[$key]['story_permalink'] = (isset($story['story_permalink']) ? htmlspecialchars($story['story_permalink']) : '');
    $stories[$key]['story_published'] = htmlspecialchars(date('r', $story['story_published']));
}
$template->set('stories', $stories);

$browser->downloadHeaders($channel['channel_name'] . '.rss', 'text/xml', true);
echo $template->fetch(JONAH_TEMPLATES . '/delivery/' . $feed_type . '.xml');
