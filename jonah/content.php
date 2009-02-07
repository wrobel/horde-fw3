<?php
/**
 * $Horde: jonah/content.php,v 1.53 2008/06/15 13:13:42 jan Exp $
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('JONAH_BASE', dirname(__FILE__));
require_once JONAH_BASE . '/lib/base.php';
require_once 'Horde/Block/Layout/View.php';

// Get refresh interval.
if ($r_time = $prefs->getValue('summary_refresh_time')) {
    if ($browser->hasFeature('xmlhttpreq')) {
        Horde::addScriptFile('prototype.js', 'horde', true);
    } else {
        $refresh_time = $r_time;
        $refresh_url = Horde::applicationUrl('content.php');
    }
}

// Load layout from preferences.
$layout = new Horde_Block_Layout_View(
    @unserialize($prefs->getValue('mynews_layout')),
    Horde::applicationUrl('content_edit.php'),
    Horde::applicationUrl('content.php', true));
$layout_html = $layout->toHtml();

$title = _("My News");
require JONAH_TEMPLATES . '/common-header.inc';
echo '<div id="menu">' . Jonah::getMenu('string') . '</div>';
echo '<div id="menuBottom"><a href="' . Horde::applicationUrl('content_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
$notification->notify(array('listeners' => 'status'));
echo $layout_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
