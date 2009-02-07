<?php
/**
 * $Horde: jonah/content_edit.php,v 1.12 2008/06/15 13:13:42 jan Exp $
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @author Jan Schneider <jan@horde.org>
 */

@define('JONAH_BASE', dirname(__FILE__));
require_once JONAH_BASE . '/lib/base.php';
require_once 'Horde/Block/Collection.php';
require_once 'Horde/Block/Layout/Manager.php';

// Instantiate the blocks objects.
$blocks = &Horde_Block_Collection::singleton('mynews', array('jonah'));
$layout = &Horde_Block_Layout_Manager::singleton('mynews', $blocks, unserialize($prefs->getValue('mynews_layout')));

// Handle requested actions.
$layout->handle(Util::getFormData('action'),
                (int)Util::getFormData('row'),
                (int)Util::getFormData('col'),
                Util::getFormData('url'));
if ($layout->updated()) {
    $prefs->setValue('mynews_layout', $layout->serialize());
}

$title = _("My News :: Add Content");
require JONAH_TEMPLATES . '/common-header.inc';
echo '<div id="menu">' . Jonah::getMenu('string') . '</div>';
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
