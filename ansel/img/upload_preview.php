<?php
/**
 * $Horde: ansel/img/upload_preview.php,v 1.1.2.3 2009/01/06 15:22:24 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';

$gallery_id = (int)Util::getFormData('gallery');
$gallery = $ansel_storage->getGallery($gallery_id);
if (is_a($gallery, 'PEAR_Error') ||
    !$gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {
    die(sprintf(_("Gallery %s not found."), $gallery_id));
}

$from = (int)Util::getFormData('from');
$to = (int)Util::getFormData('to');
$count = $to - $from + 1;

$images = $gallery->getImages($from, $count);
if (is_a($images, 'PEAR_Error')) {
    die($images->getError());
}

foreach ($images as $image) {
    echo '<li class="small">';
    echo '<div style="width:90px;">';
    echo '<img src="' . Ansel::getImageUrl($image->id, 'mini') . '" title="' . $image->filename . '" />';
    echo '</div></li>' . "\n";
}
