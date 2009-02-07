<?php
/**
 * $Horde: trean/bookmark.php,v 1.5 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';

$bookmark = $trean_shares->getBookmark(Util::getFormData('b'));
if (is_a($bookmark, 'PEAR_Error')) {
    die($bookmark);
}
$folder = $trean_shares->getFolder($bookmark->folder);
if (is_a($folder, 'PEAR_Error')) {
    die($folder);
} elseif (!$folder->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    die('Permission denied');
}

// We support changing the rating.
if (!is_null($rating = Util::getFormData('r'))) {
    if ($rating < 0 || $rating > 5) {
        die('Invalid data');
    }

    $bookmark->rating = $rating;
    $bookmark->save();
}

// Partial requests (Ajax or other rest-ish calls) just return the new
// bookmark data (currently rating).
if (Util::getFormData('partial')) {
    echo $bookmark->rating;
    exit;
}

// Back to browsing that bookmark's folder, unless we were sent a
// next-URL (nu) parameter.
if (!is_null($url = Util::getFormData('nu'))) {
    header('Location: ' . $url);
} else {
    header('Location: ' . Util::addParameter(Horde::applicationUrl('browse.php', true), 'f', $bookmark->folder));
}
