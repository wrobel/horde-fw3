<?php
/**
 * $Horde: jonah/index.php,v 1.36 2008/01/02 11:13:16 jan Exp $
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('JONAH_BASE', dirname(__FILE__));
$jonah_configured = (is_readable(JONAH_BASE . '/config/conf.php') &&
                     is_readable(JONAH_BASE . '/config/templates.php') &&
                     is_readable(JONAH_BASE . '/config/prefs.php'));

if (!$jonah_configured) {
    require JONAH_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Jonah', JONAH_BASE,
        array('conf.php', 'prefs.php'),
        array('templates.php' => 'This file defines the HTML (or other) templates that are used to generate different views of the news channels that Jonah provides.'));
}

require JONAH_BASE . '/content.php';
