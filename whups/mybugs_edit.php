<?php
/**
 * $Horde: whups/mybugs_edit.php,v 1.13.2.1 2009/01/06 15:28:14 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('WHUPS_BASE', dirname(__FILE__));
require_once WHUPS_BASE . '/lib/base.php';
require_once 'Horde/Block/Collection.php';
require_once 'Horde/Block/Layout/Manager.php';

// Instantiate the blocks objects.
$blocks = &Horde_Block_Collection::singleton('mybugs', array('whups'));
$layout = &Horde_Block_Layout_Manager::singleton('mybugs', $blocks, @unserialize($prefs->getValue('mybugs_layout')));

// Handle requested actions.
$layout->handle(Util::getFormData('action'),
                (int)Util::getFormData('row'),
                (int)Util::getFormData('col'),
                Util::getFormData('url'));
if ($layout->updated()) {
    $prefs->setValue('mybugs_layout', $layout->serialize());
}

$title = sprintf(_("My %s :: Add Content"), $registry->get('name'));
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
