<?php
/**
 * $Horde: gollem/config/mime_drivers.php.dist,v 1.8.2.1 2008/10/09 20:54:40 jan Exp $
 *
 * Decide which output drivers you want to activate for Gollem.
 * Settings in this file override settings in horde/config/mime_drivers.php.
 *
 * The available drivers are:
 * --------------------------
 * images     View images inline
 */
$mime_drivers_map['gollem']['registered'] = array('images');

/**
 * If you want to specifically override any MIME type to be handled by
 * a specific driver, then enter it here. Normally, this is safe to
 * leave, but it's useful when multiple drivers handle the same MIME
 * type, and you want to specify exactly which one should handle it.
 */
$mime_drivers_map['gollem']['overrides'] = array();

/**
 * Driver specific settings. See horde/config/mime_drivers.php for
 * the format.
 */

/**
 * Image driver settings
 */
$mime_drivers['gollem']['images'] = array(
    'inline' => true,
    'handles' => array(
        'image/*'
    )
);