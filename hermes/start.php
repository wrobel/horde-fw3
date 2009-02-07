<?php
/**
 * $Horde: hermes/start.php,v 1.9.2.1 2009/01/06 15:23:58 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

@define('HERMES_BASE', dirname(__FILE__));
require_once HERMES_BASE . '/lib/base.php';
require_once 'Horde/Variables.php';

$vars = Variables::getDefaultVariables();

$form = &new Horde_Form($vars, _("Stop Watch"));
$form->addVariable(_("Stop watch description"), 'description', 'text', true);

if ($form->validate($vars)) {
    $timers = $prefs->getValue('running_timers', false);
    if (empty($timers)) {
        $timers = array();
    } else {
        $timers = @unserialize($timers);
        if (!$timers) {
            $timers = array();
        }
    }
    $now = time();
    $timers[$now] = array('name' => String::convertCharset($vars->get('description'),
                                                       NLS::getCharset(),
                                                       $prefs->getCharset()),
                          'time' => $now);
    $prefs->setValue('running_timers', serialize($timers), false);

    Util::closeWindowJS('alert(\'' . addslashes(sprintf(_("The stop watch \"%s\" has been started and will appear in the sidebar at the next refresh."), $vars->get('description'))) . '\');');
    exit;
}

$title = _("Stop Watch");
require HERMES_TEMPLATES . '/common-header.inc';

$renderer = new Horde_Form_Renderer();
$form->renderActive($renderer, $vars, 'start.php', 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
