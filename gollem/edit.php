<?php
/**
 * $Horde: gollem/edit.php,v 1.5.2.2 2009/01/06 15:23:53 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

@define('GOLLEM_BASE', dirname(__FILE__));
require_once GOLLEM_BASE . '/lib/base.php';

$actionID = Util::getFormData('actionID');
$driver = Util::getFormData('driver');
$filedir = Util::getFormData('dir');
$filename = Util::getFormData('file');
$type = Util::getFormData('type');

if ($driver != $GLOBALS['gollem_be']['driver']) {
    Util::closeWindowJS();
    exit;
}

/* Run through action handlers. */
switch ($actionID) {
case 'save_file':
    $data = Util::getFormData('content');
    $result = $gollem_vfs->writeData($filedir, $filename, $data);
    if (is_a($result, 'PEAR_Error')) {
        $message = sprintf(_("Access denied to %s"), $filename);
    } else {
        $message = sprintf(_("%s successfully saved."), $filename);
    }
    Util::closeWindowJS('alert(\'' . addslashes($message) . '\');');
    exit;

case 'edit_file':
    $data = $gollem_vfs->read($filedir, $filename);
    if (is_a($data, 'PEAR_Error')) {
        Util::closeWindowJS('alert(\'' . addslashes(sprintf(_("Access denied to %s"), $filename)) . '\');');
        exit;
    }
    require_once 'Horde/MIME/Magic.php';
    $mime_type = MIME_Magic::extToMIME($type);
    if (strpos($mime_type, 'text/') !== 0) {
        Util::closeWindowJS();
    }
    if ($mime_type == 'text/html') {
        require_once 'Horde/Editor.php';
        $editor = &Horde_Editor::singleton('xinha', array('id' => 'content'));
    }
    require GOLLEM_TEMPLATES . '/common-header.inc';
    Gollem::status();
    require GOLLEM_TEMPLATES . '/edit/edit.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

Util::closeWindowJS();
