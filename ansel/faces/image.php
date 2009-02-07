<?php
/**
 * Process an single image (to be called via Ajax)
 *
 * $Horde: ansel/faces/image.php,v 1.8.2.1 2009/01/06 15:22:20 jan Exp $
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

$faces = Ansel_Faces::factory();
if (is_a($faces, 'PEAR_Error')) {
    die($faces->getMessage());
}

$name = '';
$autocreate = true;
$image_id = (int)Util::getFormData('image');
$reload = (int)Util::getFormData('reload');
$result = $faces->getImageFacesData($image_id);

// Attempt to get faces from the picture if we don't already have results,
// or if we were asked to explicitly try again.
if (($reload || empty($result))) {
    $image = &$ansel_storage->getImage($image_id);
    if (is_a($image, 'PEAR_Error')) {
        exit;
    }

    $result = $image->createView('screen');
    if (is_a($result, 'PEAR_Error')) {
        exit;
    }

    $result = $faces->getFromPicture($image_id, $autocreate);
    if (is_a($result, 'PEAR_Error')) {
        exit;
    }
}

if (!empty($result)) {
    $imgdir = $registry->getImageDir('horde');
    $customurl = Horde::applicationUrl('faces/custom.php');
    require_once ANSEL_TEMPLATES . '/faces/image.inc';
} else {
    echo _("No faces found");
}