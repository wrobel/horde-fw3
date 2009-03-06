<?php
/**
 * A script for triggering an update of the Kolab Free/Busy information.
 *
 * This script generates partial free/busy information based on a
 * single calendar folder on the Kolab groupware server. The partial
 * information is cached and later assembled for display by the
 * freebusy.php script.
 *
 * $Horde: framework/Kolab_FreeBusy/www/Horde/Kolab/FreeBusy/pfb.php,v 1.4.2.1 2009/03/06 18:12:02 wrobel Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <p@rdus.de>
 * @author  Thomas Arendsen Hein <thomas@intevation.de>
 * @package Kolab_FreeBusy
 */

/** Load the required free/busy library */ 
require_once 'Horde/Kolab/FreeBusy.php';

/** Load the configuration */ 
require_once 'config.php';

$fb = &new Horde_Kolab_FreeBusy();
$view = $fb->trigger();
$view->render();
