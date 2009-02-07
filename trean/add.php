<?php
/**
 * $Horde: trean/add.php,v 1.44 2008/07/30 15:57:38 chuck Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';

/* Deal with any action task. */
$actionID = Util::getFormData('actionID');
switch ($actionID) {
case 'add_bookmark':
    /* Check permissions. */
    if (Trean::hasPermission('max_bookmarks') !== true &&
        Trean::hasPermission('max_bookmarks') <= $trean_shares->countBookmarks()) {
        $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d bookmarks."), Trean::hasPermission('max_bookmarks')), ENT_COMPAT, NLS::getCharset());
        if (!empty($conf['hooks']['permsdenied'])) {
            $message = Horde::callHook('_perms_hook_denied', array('trean:max_bookmarks'), 'horde', $message);
        }
        $notification->push($message, 'horde.error', array('content.raw'));
        header('Location: ' . Horde::applicationUrl('browse.php', true));
        exit;
    }

    $folderId = Util::getFormData('f');
    $new_folder = Util::getFormData('newFolder');

    /* Create a new folder if requested */
    if ($new_folder) {
        $properties = array();
        $properties['name'] = $new_folder;

        $parent_id = $trean_shares->getId(Auth::getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder($properties);

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $folderId = $result;
        }
    }

    /* Create a new bookmark. */
    $properties = array(
        'bookmark_url' => Util::getFormData('url'),
        'bookmark_title' => Util::getFormData('title'),
        'bookmark_description' => Util::getFormData('description'),
    );

    $folder = &$trean_shares->getFolder($folderId);
    $result = $folder->addBookmark($properties);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error adding the bookmark: %s"), $result->getMessage()), 'horde.error');
    } else {
        if (Util::getFormData('popup')) {
            Util::closeWindowJS();
        } elseif (Util::getFormData('iframe')) {
            $notification->push(_("Bookmark Added"), 'horde.success');
            require TREAN_TEMPLATES . '/common-header.inc';
            $notification->notify();
            exit;
        } else {
            header('Location: ' . Horde::applicationUrl(Util::addParameter('browse.php', 'f', $folderId), true));
        }
        exit;
    }
    break;

case 'add_folder':
    $parent_id = Util::getFormData('f');
    if (is_null($parent_id)) {
        $parent_id = $trean_shares->getId(Auth::getAuth());
    }

    /* Check permissions. */
    if (Trean::hasPermission('max_folders') !== true &&
        Trean::hasPermission('max_folders') <= Trean::countFolders()) {
        $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d folders."), Trean::hasPermission('max_folders')), ENT_COMPAT, NLS::getCharset());
        if (!empty($conf['hooks']['permsdenied'])) {
            $message = Horde::callHook('_perms_hook_denied', array('trean:max_folders'), 'horde', $message);
        }
        $notification->push($message, 'horde.error', array('content.raw'));
        header('Location: ' . Horde::applicationUrl(Util::addParameter('browse.php', 'f', $parent_id), true));
        exit;
    }

    $parent = &$trean_shares->getFolder($parent_id);
    if (is_a($parent, 'PEAR_Error')) {
        $result = $parent;
    } else {
        $result = $parent->addFolder(array('name' => Util::getFormData('name')));
    }
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
    } else {
        header('Location: ' . Horde::applicationUrl(Util::addParameter('browse.php', 'f', $result), true));
        exit;
    }
    break;
}

if (Util::getFormData('popup')) {
    $notification->push('window.focus();', 'javascript');
}
$title = _("New Bookmark");
require TREAN_TEMPLATES . '/common-header.inc';
if (!Util::getFormData('popup') && !Util::getFormData('iframe')) {
    require TREAN_TEMPLATES . '/menu.inc';
}
require TREAN_TEMPLATES . '/add.html.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
