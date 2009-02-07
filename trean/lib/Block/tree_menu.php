<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: trean/lib/Block/tree_menu.php,v 1.7 2007/01/10 23:19:49 chuck Exp $
 */
class Horde_Block_trean_tree_menu extends Horde_Block {

    var $_app = 'trean';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        define('TREAN_BASE', dirname(__FILE__) . '/../..');
        require_once TREAN_BASE . '/lib/base.php';

        $browse = Horde::applicationUrl('browse.php');

        $tree->addNode($parent . '__new',
                       $parent,
                       _("Add"),
                       $indent + 1,
                       false,
                       array('icon' => 'add.png',
                             'icondir' => $registry->getImageDir(),
                             'url' => Horde::applicationUrl('add.php')));

        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'icondir' => $registry->getImageDir('horde'),
                             'url' => Horde::applicationUrl('search.php')));

        $folders = Trean::listFolders();
        if (!is_a($folders, 'PEAR_Error')) {
            foreach ($folders as $folder) {
                $parent_id = $folder->getParent();
                $tree->addNode($parent . $folder->getId(),
                               $parent . $parent_id,
                               $folder->get('name'),
                               $indent + substr_count($folder->getName(), ':') + 1,
                               false,
                               array('icon' => 'folder.png',
                                     'icondir' => $registry->getImageDir('horde') . '/tree',
                                     'url' => Util::addParameter($browse, 'f', $folder->getId())));
            }
        }
    }

}
