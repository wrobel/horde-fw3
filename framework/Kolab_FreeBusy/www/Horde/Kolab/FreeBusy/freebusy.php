<?php
/**
 * A script for fetching the Kolab Free/Busy information.
 *
 * $Horde: framework/Kolab_FreeBusy/www/Horde/Kolab/FreeBusy/freebusy.php,v 1.4.2.1 2009/03/06 18:12:02 wrobel Exp $
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
$view = $fb->fetch();
$view->render();

