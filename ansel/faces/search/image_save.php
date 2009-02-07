<?php
/**
 * Process an single image
 *
 * $Horde: ansel/faces/search/image_save.php,v 1.7.2.1 2009/01/06 15:22:23 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/Image.php';

/* Check if image exists. */
$tmp = Horde::getTempDir();
$path = $tmp . '/search_face_' . Auth::getAuth() . $faces->getExtension();

if (!file_exists($path)) {
    $notification->push(_("You must upload the search photo first"));
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
}

$x1 = (int)Util::getFormData('x1');
$y1 = (int)Util::getFormData('y1');
$x2 = (int)Util::getFormData('x2');
$y2 = (int)Util::getFormData('y2');

if ($x2 - $x1 < 50 || $y2 - $y1 < 50) {
    $notification->push(_("Photo is too small. Search photo must be at least 50x50 pixels."));
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

/* Create Horde_Image driver. */
$driver = empty($conf['image']['convert']) ? 'gd' : 'im';
$img = Horde_Image::factory($driver, array('type' => $conf['image']['type'],
                                           'temp' => $tmp));

$result = $img->loadFile($path);
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result->getMessage());
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

/* Crop image. */
$result = $img->crop($x1, $y1, $x2, $y2);
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result->getMessage());
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

/* Resize image. */
$img->getDimensions();
if ($img->_width >= 50) {
    $img->resize(min(50, $img->_width), min(50, $img->_height), true);
}

/* Save image. */
$path = $tmp . '/search_face_thumb_' . Auth::getAuth() . $faces->getExtension();
if (!file_put_contents($path, $img->raw())) {
    $notification->push(_("Cannot store search photo"));
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

/* Get original signature. */
$signature = $faces->getSignatureFromFile($path);
if (empty($signature)) {
    $notification->push(_("Cannot read photo signature"));
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

/* Save signature. */
$path = $tmp . '/search_face_' . Auth::getAuth() . '.sig';
if (file_put_contents($path, $signature)) {
    header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    exit;
}

$notification->push(_("Cannot save photo signature"));
header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
exit;