<?php
/**
 * $Horde: gollem/view.php,v 1.51.2.6 2009/04/17 11:10:44 jan Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

$session_control = 'readonly';
@define('GOLLEM_BASE', dirname(__FILE__));
require_once GOLLEM_BASE . '/lib/base.php';

$actionID = Util::getFormData('actionID');
$driver = Util::getFormData('driver');
$filedir = Util::getFormData('dir');
$filename = Util::getFormData('file');
$type = Util::getFormData('type');

if ($driver != $GLOBALS['gollem_be']['driver']) {
    $url = Util::addParameter(Horde::applicationUrl('login.php'), array('backend_key' => $driver, 'change_backend' => 1, 'url' => Horde::selfURL(true)), null, false);
    header('Location: ' . $url);
    exit;
}

$stream = null;
$data = '';
if (is_callable(array($GLOBALS['gollem_vfs'], 'readStream'))) {
    $stream = $GLOBALS['gollem_vfs']->readStream($filedir, $filename);
    if (is_a($stream, 'PEAR_Error')) {
        Horde::logMessage($stream, __FILE__, __LINE__, PEAR_LOG_NOTICE);
        printf(_("Access denied to %s"), $filename);
        exit;
    }
} else {
    $data = $GLOBALS['gollem_vfs']->read($filedir, $filename);
    if (is_a($data, 'PEAR_Error')) {
        Horde::logMessage($data, __FILE__, __LINE__, PEAR_LOG_NOTICE);
        printf(_("Access denied to %s"), $filename);
        exit;
    }
}

/* Run through action handlers. */
switch ($actionID) {
case 'download_file':
    $browser->downloadHeaders($filename, null, false, $GLOBALS['gollem_vfs']->size($filedir, $filename));
    if (is_resource($stream)) {
        while ($buffer = fread($stream, 8192)) {
            echo $buffer;
            ob_flush();
            flush();
        }
    } else {
        echo $data;
    }
    break;

case 'view_file':
    require_once 'Horde/MIME/Contents.php';
    require_once 'Horde/MIME/Magic.php';
    if (is_callable(array('Horde', 'loadConfiguration'))) {
        $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
        extract($result);
        $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'gollem');
        require_once 'Horde/Array.php';
        $mime_drivers = Horde_Array::array_merge_recursive_overwrite($mime_drivers, $result['mime_drivers']);
        $mime_drivers_map = Horde_Array::array_merge_recursive_overwrite($mime_drivers_map, $result['mime_drivers_map']);
    } else {
        require HORDE_BASE . '/config/mime_drivers.php';
        require GOLLEM_BASE . '/config/mime_drivers.php';
    }

    if (is_resource($stream)) {
        $data = '';
        while ($buffer = fread($stream, 102400)) {
            $data .= $buffer;
        }
    }
    $mime = &new MIME_Part(MIME_Magic::extToMIME($type), $data);
    $mime->setName($filename);
    $contents = &new MIME_Contents($mime);
    $body = $contents->renderMIMEPart($mime);
    $type = $contents->getMIMEViewerType($mime);
    $browser->downloadHeaders($mime->getName(true, true), $type, true, strlen($body));
    echo $body;
    break;
}
