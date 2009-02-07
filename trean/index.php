<?php
/**
 * $Horde: trean/index.php,v 1.16 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD).  If you
 * did not receive this file, see http://www.horde.org/asl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('TREAN_BASE', dirname(__FILE__));
$trean_configured = (is_readable(TREAN_BASE . '/config/conf.php') &&
                     is_readable(TREAN_BASE . '/config/prefs.php'));

if (!$trean_configured) {
    require TREAN_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Trean', TREAN_BASE, array('conf.php', 'prefs.php'));
}

require TREAN_BASE . '/browse.php';
