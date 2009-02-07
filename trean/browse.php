<?php
/**
 * $Horde: trean/browse.php,v 1.68 2008/06/18 17:28:11 mrubinsk Exp $
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
require_once TREAN_BASE . '/lib/Views/BookmarkList.php';
require_once 'Horde/Tree.php';

/* Get bookmarks to display. */
$folderId = Util::getFormData('f');

/* Default to the current user's default folder or if we are a guest, try to
 * get a list of folders we have PERMS_READ for.
 */
if (empty($folderId) && Auth::getAuth()) {
    $folderId = $trean_shares->getId(Auth::getAuth());
    $folder = &$trean_shares->getFolder($folderId);
    if (is_a($folder, 'PEAR_Error')) {
        /* Can't redirect back to browse since that would set up a loop. */
        Horde::fatal($folder, __FILE__, __LINE__, true);
    }
} elseif (empty($folderId)) {
    /* We're accessing Trean as a guest, try to get a folder to browse */
    $folders = Trean::listFolders(PERMS_READ);
    if(count($folders)) {
        $folder = array_pop(array_values($folders));
    }
} else {
    $folder = &$trean_shares->getFolder($folderId);
    if (is_a($folder, 'PEAR_Error')) {
        /* Can't redirect back to browse since that would set up a loop. */
        Horde::fatal($folder, __FILE__, __LINE__, true);
    }

    /* Make sure user has permission to view this folder. */
    if (!$folder->hasPermission(Auth::getAuth(), PERMS_READ)) {
        $notification->push(_("You do not have permission to view this folder."), 'horde.error');
        header('Location: ' . Horde::applicationUrl('browse.php', true));
        exit;
    }
}

if (!empty($folder)) {
    /* Get folder contents. */
    $bookmarks = $folder->listBookmarks($prefs->getValue('sortby'),
                                        $prefs->getValue('sortdir'));
}

Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('effects.js', 'horde', true);
Horde::addScriptFile('redbox.js', 'horde', true);
$title = _("Browse");
require TREAN_TEMPLATES . '/common-header.inc';
if (!Util::getFormData('popup')) {
    require TREAN_TEMPLATES . '/menu.inc';
}
require TREAN_TEMPLATES . '/browse.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
