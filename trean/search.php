<?php
/**
 * $Horde: trean/search.php,v 1.29 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';
require_once TREAN_BASE . '/lib/Forms/Search.php';
require_once TREAN_BASE . '/lib/Views/BookmarkList.php';

// Include PEAR packages
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

$title = _("Search");
require TREAN_TEMPLATES . '/common-header.inc';
require TREAN_TEMPLATES . '/menu.inc';

// Set up the search form.
$vars = Variables::getDefaultVariables();
$form = new SearchForm($vars);

// Render the search form.
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::selfUrl(), 'post');
echo '<br />';

if ($form->validate($vars)) {
    // Create the filter.
    $combine = Util::getFormData('combine', 'OR');
    $op = Util::getFormData('op', 'LIKE');
    $criteria = array();

    // Searching for URL?
    if (strlen($u = Util::getFormData('url'))) {
        $criteria[] = array('url', $op, $u);
    }

    // Searching title?
    if (strlen($t = Util::getFormData('title'))) {
        $criteria[] = array('title', $op, $t);
    }

    // Searching description?
    if (strlen($d = Util::getFormData('description'))) {
        $criteria[] = array('description', $op, $d);
    }

    if ($criteria) {
        // Get the bookmarks.
        $bookmarks = $trean_shares->searchBookmarks($criteria, $combine);
        $search_title = sprintf(_("Search Results (%s)"), count($bookmarks));

        // Display the results.
        require TREAN_TEMPLATES . '/search.php';
    }
}

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
