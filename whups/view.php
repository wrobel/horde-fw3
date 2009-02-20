<?php
/**
 * $Horde: whups/view.php,v 1.26.2.1 2009/01/06 15:28:14 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

define('WHUPS_BASE', dirname(__FILE__));
require_once WHUPS_BASE . '/lib/base.php';
require_once 'Horde/MIME/Magic.php';
require_once 'Horde/MIME/Viewer.php';
require_once 'Horde/MIME/Part.php';
if (is_callable(array('Horde', 'loadConfiguration'))) {
    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
    extract($result);
    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'whups');
    require_once 'Horde/Array.php';
    $mime_drivers = Horde_Array::array_merge_recursive_overwrite($mime_drivers, $result['mime_drivers']);
    $mime_drivers_map = Horde_Array::array_merge_recursive_overwrite($mime_drivers_map, $result['mime_drivers_map']);
} else {
    require HORDE_BASE . '/config/mime_drivers.php';
    require WHUPS_BASE . '/config/mime_drivers.php';
}

$actionID = Util::getFormData('actionID');
$ticket = Util::getFormData('ticket');
$filename = Util::getFormData('file');
$type = Util::getFormData('type');

// Get the ticket details first.
if (empty($ticket)) {
    exit;
}
$details = $whups_driver->getTicketDetails($ticket);
if (is_a($details, 'PEAR_Error')) {
    if ($details->code === 0) {
        // No permissions to this ticket.
        $url = Horde::url($registry->get('webroot', 'horde') . '/login.php', true);
        $url = Util::addParameter($url, 'url', Horde::selfUrl(true));
        header('Location: ' . $url);
        exit;
    } else {
        Horde::fatal($details->getMessage(), __FILE__, __LINE__);
    }
}

// Check permissions on this ticket.
if (!count(Whups::permissionsFilter($whups_driver->getHistory($ticket), 'comment', PERMS_READ))) {
    Horde::fatal(sprintf(_("You are not allowed to view ticket %d."), $ticket), __FILE__, __LINE__);
}

if (empty($conf['vfs']['type'])) {
    Horde::fatal(_("The VFS backend needs to be configured to enable attachment uploads."), __FILE__, __LINE__);
}

require_once 'VFS.php';
$vfs = VFS::factory($conf['vfs']['type'], Horde::getDriverConfig('vfs'));
if (is_a($vfs, 'PEAR_Error')) {
    Horde::fatal($vfs, __FILE__, __LINE__);
} else {
    $data = $vfs->read(WHUPS_VFS_ATTACH_PATH . '/' . $ticket, $filename);
}
if (is_a($data, 'PEAR_Error')) {
    Horde::fatal(sprintf(_("Access denied to %s"), $filename), __FILE__, __LINE__);
}

/* Run through action handlers */
switch ($actionID) {
case 'download_file':
     $browser->downloadHeaders($filename, null, false, strlen($data));
     echo $data;
     exit;

case 'view_file':
    $mime_part = new MIME_Part(MIME_Magic::extToMIME($type), $data);
    $mime_part->setName($filename);
    $viewer = MIME_Viewer::factory($mime_part);

    $body = $viewer->render();
    $browser->downloadHeaders($mime_part->getName(), $viewer->getType(), true, strlen($body));
    echo $body;
    exit;
}