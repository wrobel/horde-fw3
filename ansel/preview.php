<?php
/**
 * $Horde: ansel/preview.php,v 1.12.2.4 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
$imageId = Util::getFormData('image');
$image = &$ansel_storage->getImage($imageId);
if (is_a($image, 'PEAR_Error')) {
    Horde::logMessage($image, __LINE__, __FILE__, PEAR_LOG_ERR);
    exit;
}
$gal = $ansel_storage->getGallery(abs($image->gallery));
if (is_a($gal, 'PEAR_Error')) {
    Horde::logMessage($image, __LINE__, __FILE__, PEAR_LOG_ERR);
    exit;
}
$img = Ansel::getImageUrl($imageId, 'thumb', false);
if (!is_a($img, 'PEAR_Error') &&
        $gal->hasPermission(Auth::getAuth(), PERMS_SHOW) &&
        !$gal->hasPasswd() &&
        $gal->isOldEnough()) {
    echo '<img src="' . $img . '" />';
} else {
    echo '';
}