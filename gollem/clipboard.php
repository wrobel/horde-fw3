<?php
/**
 * $Horde: gollem/clipboard.php,v 1.4.2.6 2009/01/06 15:23:53 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

@define('GOLLEM_BASE', dirname(__FILE__));
require_once GOLLEM_BASE . '/lib/base.php';

$dir = Util::getFormData('dir');

$title = _("Clipboard");
Horde::addScriptFile('prototype.js', 'gollem', true);
Horde::addScriptFile('tables.js', 'gollem', true);
require GOLLEM_TEMPLATES . '/common-header.inc';
Gollem::menu();
Gollem::status();

$entry = array();
foreach ($_SESSION['gollem']['clipboard'] as $key => $val) {
    $entry[] = array(
        'copy' => ($val['action'] == 'copy'),
        'cut' => ($val['action'] == 'cut'),
        'id' => $key,
        'name' => $val['display']
    );
}

/* Set up the template object. */
$template = new Gollem_Template();
$template->setOption('gettext', true);
$template->set('cancelbutton', _("Cancel"));
$template->set('clearbutton', _("Clear"));
$template->set('pastebutton', _("Paste"));
$template->set('cutgraphic', Horde::img('cut.png', _("Cut")));
$template->set('copygraphic', Horde::img('copy.png', _("Copy")));
$template->set('currdir', Gollem::getDisplayPath($dir));
$template->set('dir', $dir);
$template->set('entry', $entry, true);
$template->set('manager_url', Horde::applicationUrl('manager.php'));

echo $template->fetch(GOLLEM_TEMPLATES . '/clipboard/clipboard.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
