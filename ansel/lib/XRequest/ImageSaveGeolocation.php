<?php
/**
 * Ansel_XRequest_ImageSaveGeolocation:: class for saving/updating image geo
 * location data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ansel/lib/XRequest/ImageSaveGeolocation.php,v 1.1.2.5 2009/07/10 17:50:42 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_ImageSaveGeolocation extends Ansel_XRequest {

    function _attach()
    {
        //noop
    }

    function handle($args)
    {
        global $ansel_storage;

        if (empty($args['img']) ||
            (!empty($args['type']) && $args['type'] == 'location' && empty($args['location'])) ||
            ((empty($args['type']) || (!empty($args['type']) && $args['type'] == 'all')) &&
             (!empty($args['type']) && $args['type'] == 'all' && empty($args['lat'])))) {

            echo 0;
            exit;
        }

        // Get the image and gallery to check perms
        $image = $ansel_storage->getImage($args['img']);
        if (is_a($image, 'PEAR_Error')) {
            echo 0;
            exit;
        }
        $gallery = $ansel_storage->getGallery($image->gallery);
        if (is_a($gallery, 'PEAR_Error')) {
            return 0;
            exit;
        }
        // Bail out if no perms on the image.
        if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            echo 0;
            exit;
        }

        // Only updating textual location?
        if (!empty($args['type']) && $args['type'] == 'location') {
            $image->location = !empty($args['location']) ? urldecode($args['location']) : '';
            $image->save();
            echo htmlentities($image->location);
            exit;
        }
        $image->geotag($args['lat'], $args['lng'], !empty($args['location']) ? $args['location'] : '');

        echo 1;
        exit;
    }

}
