<?php
/**
 * Records clicks and clean the URL with Horde::externalUrl().
 *
 * $Horde: trean/redirect.php,v 1.11 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Chavet <ben@horde.org>
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';

$bookmark_id = Util::getFormData('b');
if (!$bookmark_id) {
    exit;
}
$bookmark = $trean_shares->getBookmark($bookmark_id);
if (is_a($bookmark, 'PEAR_Error')) {
    exit;
}

++$bookmark->clicks;
$bookmark->save();

header('Location: ' . Horde::externalUrl($bookmark->url));
