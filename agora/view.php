<?php
/**
 * Script to download attachments.
 *
 * $Horde: agora/view.php,v 1.8.2.1 2009/01/06 15:22:12 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';

$action_id = Util::getFormData('action_id', 'download');
$file_id = Util::getFormData('file_id');
$file_name = Util::getFormData('file_name');
$vfs_path = AGORA_VFS_PATH . Util::getFormData('forum_id') . '/' . Util::getFormData('message_id');
$file_type = Util::getFormData('file_type');

/* Get VFS object. */
$vfs = Agora::getVFS();

/* Run through action handlers. TODO: Do inline viewing. */
switch ($action_id) {
case 'download':
    $file_data = $vfs->read($vfs_path, $file_id);
    $browser->downloadHeaders($file_name, $file_type, false, strlen($file_data));
    echo $file_data;
    break;
}
