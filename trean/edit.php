<?php
/**
 * $Horde: trean/edit.php,v 1.55 2008/06/15 18:01:57 mrubinsk Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';

$folderId = Util::getFormData('f', $trean_shares->getId(Auth::getAuth()));

$actionID = Util::getFormData('actionID');
if ($actionID == 'button') {
    if (Util::getFormData('new_bookmark')
        || !is_null(Util::getFormData('new_bookmark_x'))) {
        header('Location: ' . Horde::applicationUrl('add.php?f=' . $folderId, true));
        exit;
    } elseif (Util::getFormData('edit_bookmarks')) {
        $actionID = null;
    } elseif (Util::getFormData('delete_bookmarks')
              || !is_null(Util::getFormData('delete_bookmarks_x'))) {
        $actionID = 'delete';
    }
}

$bookmarks = Util::getFormData('bookmarks');
if (!is_array($bookmarks)) {
    $bookmarks = array($bookmarks);
}
$folder = Util::getFormData('folder');

switch ($actionID) {
case 'save':
    $url = Util::getFormData('url');
    $title = Util::getFormData('title');
    $description = Util::getFormData('description');
    $new_folder = Util::getFormData('new_folder');
    $delete = Util::getFormData('delete');
    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            if (isset($delete[$id])) {
                $result = $trean_shares->removeBookmark($bookmark);
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $result->getMessage()), 'horde.error');
                }
            } else {
                $old_url = $bookmark->url;

                $bookmark->url = $url[$id];
                $bookmark->title = $title[$id];
                $bookmark->description = $description[$id];

                if ($old_url != $bookmark->url) {
                    $bookmark->http_status = '';
                }

                $result = $bookmark->save();

                if ($new_folder[$id] != $bookmark->folder) {
                    $bookmark->folder = $new_folder[$id];
                    $result = $bookmark->save();
                }

                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(sprintf(_("There was an error saving the bookmark: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    if (count($folder)) {
        $name = Util::getFormData('name');
        foreach ($folder as $id) {
            $folder = &$trean_shares->getFolder($id);
            $folder->set('name', $name[$id], true);
            $result = $folder->save();
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was an error saving the folder: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (Util::getFormData('popup')) {
        if ($notification->count() <= 1) {
            Util::closeWindowJS();
        } else {
            $notification->notify();
        }
    } else {
        $url = Util::addParameter('browse.php', 'f', $folderId);
        header('Location: ' . Horde::applicationUrl($url, true));
    }
    exit;

case 'delete':
    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $result = $trean_shares->removeBookmark($bookmark);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        foreach ($folder as $id => $delete) {
            if ($delete) {
                $folder = &$trean_shares->getFolder($id);
                $result = $folder->delete();
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Deleted folder: ") . $folder->get('name'), 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem deleting the folder: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    // Return to the folder listing
    $url = Util::addParameter('browse.php', 'f', $folderId);
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;

case 'move':
    $create_folder = Util::getFormData('create_folder');
    $new_folder = Util::getFormData('new_folder');

    /* Create a new folder if requested */
    if ($create_folder) {
        $parent_id = $trean_shares->getId(Auth::getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder(array('name' => $new_folder));

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $new_folder = $result;
        }
    }

    $new_folder = &$trean_shares->getFolder($new_folder);

    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $bookmark->folder = $new_folder->getId();
            $result = $bookmark->save();
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Moved bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem moving the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        foreach ($folder as $id => $delete) {
            if ($delete) {
                $folder = &$trean_shares->getFolder($id);
                $result = $trean_shares->move($folder, $new_folder);
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Moved folder: ") . $folder->get('name'), 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem moving the folder: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    // Return to the folder listing
    $url = Util::addParameter('browse.php', 'f', $folderId);
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;

case 'copy':
    $create_folder = Util::getFormData('create_folder');
    $new_folder = Util::getFormData('new_folder');

    /* Create a new folder if requested */
    if ($create_folder) {
        $properties = array();
        $properties['name'] = $new_folder;

        $parent_id = $trean_shares->getId(Auth::getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder($properties);

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $new_folder = $result;
        }
    }

    $new_folder = &$trean_shares->getFolder($new_folder);

    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $result = $bookmark->copyTo($new_folder);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Copied bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem copying the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        $notification->push(sprintf(_("Copying folders is not supported.")), 'horde.message');
    }

    // Return to the folder listing
    $url = Util::addParameter('browse.php', 'f', $folderId);
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;

case 'rename':
    /* Rename a Bookmark Folder. */
    $name = Util::getFormData('name');

    $folder = &$trean_shares->getFolder($folderId);
    $result = $folder->set('name', $name, true);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("\"%s\" was not renamed: %s."), $name, $result->getMessage()), 'horde.error');
    } else {
        $url = Util::addParameter('browse.php', 'f', $folderId);
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    break;

case 'del_folder':
    $folder = &$trean_shares->getFolder($folderId);
    $title = _("Confirm Deletion");
    require TREAN_TEMPLATES . '/common-header.inc';
    require TREAN_TEMPLATES . '/menu.inc';
    require TREAN_TEMPLATES . '/edit/delete_folder_confirmation.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

case 'del_folder_confirmed':
    $folderId = Util::getPost('f');
    if (!$folderId) {
        exit;
    }

    $folder = &$trean_shares->getFolder($folderId);
    if (is_a($folder, 'PEAR_Error')) {
        $notification->push($folder->getMessage(), 'horde.error');
        header('Location: ' . Horde::applicationUrl('browse.php'));
        exit;
    }

    $parent = $folder->getParent();
    $result = $folder->delete();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage(), 'horde.error');
        header('Location: ' . Horde::applicationUrl(Util::addParameter('browse.php', 'f', $folderId), true));
    } else {
        $notification->push(sprintf(_("Deleted the folder \"%s\""), $folder->get('name')), 'horde.success');
        header('Location: ' . Horde::applicationUrl(Util::addParameter('browse.php', 'f', $parent), true));
    }
    exit;

case 'cancel':
    $url = Util::addParameter('browse.php', 'f', $folderId);
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;
}

// Return to browse if there is nothing to edit.
if (!count($bookmarks) && !count($folder)) {
    $notification->push(_("Nothing to edit."), 'horde.message');
    $url = Util::addParameter('browse.php', 'f', $folderId);
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;
}

$title = _("Edit Bookmark");
require TREAN_TEMPLATES . '/common-header.inc';
if (!Util::getFormData('popup')) {
    require TREAN_TEMPLATES . '/menu.inc';
}
require TREAN_TEMPLATES . '/edit/header.inc';

if (count($folder)) {
    foreach ($folder as $id) {
        $folder = $trean_shares->getFolder($id);
        require TREAN_TEMPLATES . '/edit/folder.inc';
    }
}

if (count($bookmarks)) {
    foreach ($bookmarks as $id) {
        $bookmark = $trean_shares->getBookmark($id);
        if (!is_a($bookmark, 'PEAR_Error')) {
            require TREAN_TEMPLATES . '/edit/bookmark.inc';
        }
    }
}

require TREAN_TEMPLATES . '/edit/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
