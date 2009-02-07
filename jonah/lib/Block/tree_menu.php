<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: jonah/lib/Block/tree_menu.php,v 1.3 2008/01/02 16:48:35 chuck Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_jonah_tree_menu extends Horde_Block {

    var $_app = 'jonah';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/News.php';

        if (!Jonah::checkPermissions('jonah:news', PERMS_EDIT) ||
            !in_array('internal', $conf['news']['enable'])) {
            return;
        }

        $url = Horde::applicationUrl('stories/');
        $icondir = $registry->getImageDir();
        $news = Jonah_News::factory();
        $channels = $news->getChannels('internal');
        if (is_a($channels, 'PEAR_Error')) {
            return;
        }

        foreach ($channels as $channel) {
            $tree->addNode($parent . $channel['channel_id'],
                           $parent,
                           $channel['channel_name'],
                           $indent + 1,
                           false,
                           array('icon' => 'editstory.png',
                                 'icondir' => $icondir,
                                 'url' => Util::addParameter($url, array('channel_id' => $channel['channel_id']))));
        }
    }

}
