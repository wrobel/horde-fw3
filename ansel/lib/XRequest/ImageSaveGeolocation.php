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
 * $Horde: ansel/lib/XRequest/ImageSaveGeolocation.php,v 1.1.2.8 2009/09/12 08:29:55 jan Exp $
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
        $image = $ansel_storage->getImage((int)$args['img']);
        if (is_a($image, 'PEAR_Error')) {
            echo 0;
            exit;
        }
        $gallery = $ansel_storage->getGallery($image->gallery);
        if (is_a($gallery, 'PEAR_Error')) {
            echo 0;
            exit;
        }
        // Bail out if no perms on the image.
        if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            echo 0;
            exit;
        }

        switch ($args['type']) {
        case 'geotag':
            $image->geotag($args['lat'], $args['lng'], !empty($args['location']) ? $args['location'] : '');
            echo 1;
            exit;

        case 'location':
            $image->location = !empty($args['location']) ? urldecode($args['location']) : '';
            $image->save();
            echo htmlentities($image->location);
            exit;

        case 'untag':
            $image->geotag('', '', '');
            // Now get the "add geotag" stuff
            $addurl = Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $args['img']);
            $addLink = Horde::link($addurl, '', '', '', 'popup(\'' . Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $args['img']) . '\'); return false;');
            $imgs = $GLOBALS['ansel_storage']->getRecentImagesGeodata(Auth::getAuth());
            if (count($imgs) > 0) {
                $imgsrc = '<div class="ansel_location_sameas">';
                foreach ($imgs as $id => $data) {
                    if (!empty($data['image_location'])) {
                        $title = $data['image_location'];
                    } else {
                        $title = $this->_point2Deg($data['image_latitude'], true) . ' ' . $this->_point2Deg($data['image_longitude']);
                    }
                    $imgsrc .= Horde::link($addurl, $title, '', '', "setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "');return false") . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[image]" /></a>';
                }
                $imgsrc .= '</div>';
                $content = sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
            } else {
                $content = sprintf(_("No location data present. You may add some %s"), $addLink . _("here") . '</a>');
            }

            echo $content;
            exit;
        }
    }

}
