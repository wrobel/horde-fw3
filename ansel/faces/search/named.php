<?php
/**
 * Show all named faces.
 *
 * $Horde: ansel/faces/search/named.php,v 1.3.2.1 2009/01/06 15:22:23 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/UI/Pager.php';

$title = _("Named faces");
$page = Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');
$results = array();
$count = $faces->countNamedFaces();
if (is_a($count, 'PEAR_Error')) {
    $notification->push($count->getDebugInfo());
    $count = 0;
} elseif ($count > 0) {
    $results = $faces->namedFaces($page * $perpage, $perpage);
}

$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $count,
          'url' => 'faces/search/named.php',
          'perpage' => $perpage));

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
include ANSEL_TEMPLATES . '/faces/faces.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
