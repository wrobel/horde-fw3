<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/savecustom.php,v 1.7.2.2 2009/07/06 15:58:55 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';

$image_id = (int)Util::getFormData('image_id');
$gallery_id = (int)Util::getFormData('gallery_id');
$face_id = (int)Util::getFormData('face_id');
$url = Util::getFormData('url');
$page = Util::getFormData('page', 0);

$back_url = empty($url) ?
    Util::addParameter(Horde::applicationUrl('faces/gallery.php'),
                             array('gallery' => $gallery_id,
                                   'page' => $page), null, false) :
    $url;

if (Util::getPost('submit') == _("Cancel")) {
    $notification->push(_("Changes cancelled."), 'horde.warning');
    header('Location: ' . $back_url);
    exit;
}

$faces = Ansel_Faces::factory();
if (is_a($faces, 'PEAR_Error')) {
    $notification->push($faces);
    header('Location: ' . $back_url);
    exit;
}

$result = $faces->saveCustomFace($face_id,
                           $image_id,
                           (int)Util::getFormData('x1'),
                           (int)Util::getFormData('y1'),
                           (int)Util::getFormData('x2'),
                           (int)Util::getFormData('y2'),
                           Util::getFormData('name'));

if (is_a($result, 'PEAR_Error')) {
    $notification->push($result);
    $notification->push($result->getDebugInfo());
    header('Location: ' . $back_url);
    exit;
} elseif ($face_id == 0) {
    $notification->push(_("Face successfuly created"), 'horde.success');
} else {
    $notification->push(_("Face successfuly updated"), 'horde.success');
}

header('Location: ' . $back_url);
exit;
