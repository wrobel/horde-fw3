<?php
/**
 * A script for regenerating the Kolab Free/Busy cache.
 *
 * $Horde: framework/Kolab_FreeBusy/www/Horde/Kolab/FreeBusy/regenerate.php,v 1.3.2.1 2009/03/06 18:12:02 wrobel Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Gunnar Wrobel <p@rdus.de>
 * @author  Thomas Arendsen Hein <thomas@intevation.de>
 * @package Kolab_FreeBusy
 */

/** Report all errors */ 
error_reporting(E_ALL);

/** requires safe_mode to be turned off */
ini_set('memory_limit', -1);

/**
 * Load the required free/busy libraries - this also loads Horde:: and
 * Util:: as well as the PEAR constants
 */ 
require_once 'Horde/Kolab/FreeBusy.php';

/** Load the configuration */ 
require_once 'config.php';

$conf['kolab']['misc']['allow_special'] = true;

$fb = &new Horde_Kolab_FreeBusy();
$result = $fb->regenerate();
if (is_a($result, 'PEAR_Error')) {
    echo $result->getMessage();
    exit(1);
}

if (!is_array($result)) {
    $result = array($result);
}

foreach ($result as $line) {
    echo $line . '<br/>';
}
exit(0);
