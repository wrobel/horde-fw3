<?php
/**
 * $Horde: trean/reports.php,v 1.13 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Ben Chavet <ben@horde.org>
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';
require_once TREAN_BASE . '/lib/Views/BookmarkList.php';

$drilldown = Util::getFormData('drilldown');
$title = _("Reports");
Horde::addScriptFile('stripe.js', 'horde', true);
require TREAN_TEMPLATES . '/common-header.inc';
require TREAN_TEMPLATES . '/menu.inc';

if ($drilldown) {
    $bookmarks = $trean_shares->searchBookmarks(array(array('http_status', 'LIKE', substr($drilldown, 0, 1), array('begin' => true))));
    $search_title = _("HTTP Status") . ' :: ' . sprintf(_("%s Response Codes"), $drilldown) . ' (' . count($bookmarks) . ')';

    /* Display the results. */
    require TREAN_TEMPLATES . '/search.php';
} else {
    require TREAN_TEMPLATES . '/reports.php';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
