<?php
/**
 * Jonah external API interface.
 *
 * $Horde: jonah/lib/api.php,v 1.71.2.2 2009/01/09 21:51:56 mrubinsk Exp $
 *
 * This file defines Jonah's external API interface. Other
 * applications can interact with Jonah through this API.
 *
 * @package Jonah
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['stories'] = array(
    'args' => array('channel' => 'int',
                    'max_stories' => 'int',
                    'start_at' => 'int',
                    'order' => 'int'),
    'type' => '{urn:horde}stringArray'
);

$_services['listFeeds'] = array(
    'args' => array('type' => 'int'),
    'type' => '{urn:horde}stringArray'
);

$_services['story'] = array(
    'args' => array('channel' => 'int',
                    'story' => 'int',
                    'read' => 'bool'),
    'type' => '{urn:horde}stringArray',
);

$_services['commentCallback'] = array(
    'args' => array('story_id' => 'string'),
    'type' => 'string'
);

$_services['hasComments'] = array(
    'args' => array(),
    'type' => 'boolean'
);

$_services['listTagInfo'] = array(
    'args' => array('tags' => '{urn:horde}stringArray',
                    'channel_id' => 'int'),
    'type' => '{urn:horde}stringArray'
);

$_services['getTagIds'] = array(
    'args' => array('names' => '{urn:horde}stringArray'),
    'type' => '{urn:horde}stringArray'
);

$_services['searchTags'] = array(
    'args' => array('names' => '{urn:horde}stringArray',
                    'max' => 'int',
                    'from' => 'int',
                    'channel_id' => '{urn:horde}stringArray',
                    'order' => 'int',
                    'raw' => 'bool'),
    'type' => '{urn:horde}stringArray'
);

$_services['storyCount'] = array(
    'args' => array('channel_id' => 'int'),
    'type' => 'int'
);

/**
 * Returns a list of available permissions.
 *
 * @return array  An array describing all available permissions.
 */
function _jonah_perms()
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);

    /* Loop through internal channels and add their ids to the
     * perms. */
    $perms = array();
    foreach ($channels as $channel) {
        $perms['tree']['jonah']['news']['internal_channels'][$channel['channel_id']] = false;
    }

    /* Human names. */
    $perms['title']['jonah:news'] = _("News");
    $perms['title']['jonah:news:internal_channels'] = _("Internal Channels");

    /* Loop through internal channels and add them to the perms
     * titles. */
    foreach ($channels as $channel) {
        $perms['title']['jonah:news:internal_channels:' . $channel['channel_id']] = $channel['channel_name'];
    }

    $channels = $news->getChannels(JONAH_EXTERNAL_CHANNEL);

    /* Loop through external channels and add their ids to the
     * perms. */
    foreach ($channels as $channel) {
        $perms['tree']['jonah']['news']['external_channels'][$channel['channel_id']] = false;
    }

    /* Human names. */
    $perms['title']['jonah:news'] = _("News");
    $perms['title']['jonah:news:external_channels'] = _("External Channels");

    /* Loop through external channels and add them to the perms
     * titles. */
    foreach ($channels as $channel) {
        $perms['title']['jonah:news:external_channels:' . $channel['channel_id']] = $channel['channel_name'];
    }

    $perms['tree']['jonah']['admin'] = array();
    $perms['title']['jonah:admin'] = _("Administrator");

    return $perms;
}

/**
 * Get a list of stored channels.
 *
 * @param integer $type  The type of channel to filter for. Possible
 *                       values are either JONAH_INTERNAL_CHANNEL
 *                       to fetch only a list of internal channels,
 *                       or JONAH_EXTERNAL_CHANNEL for only external.
 *                       If null both channel types are returned.
 *
 * @return mixed         An array of channels or PEAR_Error on error.
 */
function _jonah_listFeeds($type = null)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    $channels = $news->getChannels($type);

    return $channels;
}

/**
 * Return the requested stories
 *
 * @param int $channel_id   The channel to get the stories from.
 * @param int $max_stories  The maximum number of stories to get.
 * @param int $start_at     The story number to start retrieving.
 * @param int $order        How to order the results.
 *
 * @return An array of story information | PEAR_Error
 */
function _jonah_stories($channel_id, $max_stories = 10, $start_at = 0,
                        $order = 0)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';
    $news = Jonah_News::factory();
    $stories = $news->getStories($channel_id, $max_stories, $start_at, false,
                                 time(), false, $order);

    foreach (array_keys($stories) as $s) {
        if (empty($stories[$s]['story_body_type']) || $stories[$s]['story_body_type'] == 'text') {
            require_once 'Horde/Text/Filter.php';
            $stories[$s]['story_body_html'] = Text_Filter::filter($stories[$s]['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
        } else {
            $stories[$s]['story_body_html'] = $stories[$s]['story_body'];
        }
    }

    return $stories;
}

/**
 * Fetches a story from a requested channel.
 *
 * @param integer $channel_id  The channel id to fetch.
 * @param integer $story_id    The story id to fetch.
 * @param boolean $read        Whether to update the read count.
 */
function _jonah_story($channel_id, $story_id, $read = true)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    $story = $news->getStory($channel_id, $story_id, $read);
    if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
        require_once 'Horde/Text/Filter.php';
        $story['story_body_html'] = Text_Filter::filter($story['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
    } else {
        $story['story_body_html'] = $story['story_body'];
    }

    return $story;
}

/**
 * Callback for comment API
 *
 * @param integer $id  Internal data identifier
 *
 * @return mixed  Name of object on success | false on failure
 */
function _jonah_commentCallback($story_id)
{
    if (!$GLOBALS['conf']['comments']['allow']) {
        return false;
    }

    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    $story = $news->getStory(null, $story_id);
    if (is_a($story, 'PEAR_Error')) {
        return false;
    }

    return $story['story_title'];
}

/**
 * Check if comments are allowed.
 *
 * @return boolean
 */
function _jonah_hasComments()
{
    return $GLOBALS['conf']['comments']['allow'];
}

/**
 * Retrieve the list of used tag_names, tag_ids and the total number
 * of resources that are linked to that tag.
 *
 * @param array $tags  An optional array of tag_ids. If omitted, all tags
 *                     will be included.
 *
 * @param array $channel_id  An optional array of channel_ids.
 *
 * @return mixed  An array containing tag_name, and total | PEAR_Error
 */
function _jonah_listTagInfo($tags = array(), $channel_id = null)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    return $news->listTagInfo($tags, $channel_id);
}

/**
 * Return a set of tag_ids, given the tag name
 *
 * @param array $names  An array of names to search for
 *
 * @return mixed  An array of tag_name => tag_ids | PEAR_Error
 */
function _jonah_getTagIds($names)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    return $news->getTagIds($names);
}

/**
 * Searches internal channels for stories tagged with all requested tags.
 * Returns an application-agnostic array (useful for when doing a tag search
 * across multiple applications) containing the following keys:
 * <pre>
 *  'title'    - The title for this resource.
 *  'desc'     - A terse description of this resource.
 *  'view_url' - The URL to view this resource.
 *  'app'      - The Horde application this resource belongs to.
 * </pre>
 *
 * The 'raw' story array can be returned instead by setting $raw = true.
 *
 * @param array $names       An array of tag_names to search for (AND'd together).
 * @param integer $max       The maximum number of stories to return.
 * @param integer $from      The number of the story to start with.
 * @param array $channel_id  An array of channel_ids to limit the search to.
 * @param integer $order     How to order the results (a JONAH_ORDER_* constant)
 * @param boolean $raw       Return the raw story data?
 *
 * @return mixed  An array of results | PEAR_Error
 */
function _jonah_searchTags($names, $max = 10, $from = 0, $channel_id = array(),
                           $order = 0, $raw = false)
{
    global $registry;

    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory();
    $results = $news->searchTags($names, $max, $from, $channel_id, $order);
    if (is_a($results, 'PEAR_Error')) {
        return $results;
    }
    $return = array();
    if ($raw) {
        // Requesting the raw story information as returned from searchTags,
        // but add some additional information that external apps might
        // find useful.
        $comments = $GLOBALS['conf']['comments']['allow'] && $registry->hasMethod('forums/numMessages');
        foreach ($results as $story) {
            if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
                require_once 'Horde/Text/Filter.php';
                $story['story_body_html'] = Text_Filter::filter($story['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
            } else {
                $story['story_body_html'] = $story['story_body'];
            }

            if ($comments) {
                $story['num_comments'] = $registry->call('forums/numMessages',
                                                         array($story['story_id'],
                                                               $registry->getApp()));
            }

            $return[$story['story_id']] = $story;
        }
    } else {
        foreach($results as $story) {
            if (!empty($story)) {
                $return[] = array('title' => $story['story_title'],
                                                    'desc' => $story['story_desc'],
                                                    'view_url' => $story['story_link'],
                                                    'app' => 'jonah');
            }
        }
    }

    return $return;
}

/**
 * Get the count of stories in the specified channel
 *
 * @param int $channel_id
 * @return mixed  The story count
 */
function _jonah_storyCount($channel_id)
{
    global $registry;

    require_once dirname(__FILE__) . '/base.php';
    require_once JONAH_BASE . '/lib/News.php';

    $news = Jonah_News::factory('sql');
    $results = $news->getStoryCount($channel_id);
    if (is_a($results, 'PEAR_Error')) {
        return 0;
    }
    return $results;
}

