<?php
/**
 * Ansel_XRequest_EditFaces:: class for performing Ajax discovery and editing
 * of image faces
 *
 * $Horde: ansel/lib/XRequest/EditFaces.php,v 1.14.2.2 2009/01/17 16:50:29 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_XRequest_EditFaces extends Ansel_XRequest {

    /**
     * Attach these actions to the view
     *
     */
    function _attach()
    {

        // The base of the URL needed to call this XRequest object.
        $returnUrl = $this->_params['selfUrl'];
        $url = Horde::applicationUrl(
            Util::addParameter('xrequest.php',
                               array('url' => $returnUrl,
                                     'requestType' => 'EditFaces'),
                               null,
                               false),
            true);

        // Localized text
        $loading_text = _("Loading...");

        // Build the javascript needed for these actions
        // FIXME: These should be refactored to use json output so we can
        // cache all this javascript once and reuse for each image.
        // TODO: Need to update the content node after actions are successful
        $js = array();
        $js[] = <<<EOT
            function deleteFace(image_id, face_id)
            {
                url = '$url/action=delete/image=' + image_id + '/face=' + face_id;
                new Ajax.Request(url);
                \$('face' + face_id).remove();
            }
            function setFaceName(image_id, face_id)
            {
                url = '$url/action=setname/face=' + face_id + '/image=' + image_id + '/facename=' + encodeURIComponent(\$F('facename' + face_id));
                new Ajax.Updater({success: 'face' + face_id}, url);
            }
            function doFaceEdit(image_id)
            {
                $('faces_widget_content').update('$loading_text');
                url = '$url/image=' + image_id + '/action=process';
                new Ajax.Updater({success:'faces_widget_content'}, url);
            }
EOT;

        // Start observing for clicks on the edit/detect link
        $js[] = "document.observe('dom:loaded', function() {"
                . "  Event.observe('" . $this->_params['domid'] . "', 'click', function(event) {doFaceEdit(" . $this->_params['image_id'] . ");Event.stop(event)});"
                . "});";

        // Output the JS to the browser.
        $this->_outputJS($js);
    }

    function handle($args)
    {
        global $registry, $ansel_storage;

        // These should only be available to auth'd users.
        // TODO: Need to double check any security issues here...
        if (Auth::getAuth()) {
            require_once ANSEL_BASE . '/lib/base.php';
            require_once ANSEL_BASE . '/lib/Faces.php';

            // we require an action to be passsed in.
            if (!empty($args['action'])) {
                $faces = Ansel_Faces::factory();
                if (is_a($faces, 'PEAR_Error')) {
                    die($faces->getMessage());
                }
                $image_id = (int)$args['image'];
                switch($args['action']) {

                case 'process':
                    // process - detects all faces in the image.
                    $name = '';
                    $autocreate = true;
                    $reload = (!empty($args['reload']) ? $args['reload'] : 0);
                    $result = $faces->getImageFacesData($image_id);
                    // Attempt to get faces from the picture if we don't already have results,
                    // or if we were asked to explicitly try again.
                    if (($reload || empty($result))) {
                        $image = &$ansel_storage->getImage($image_id);
                        if (is_a($image, 'PEAR_Error')) {
                            exit;
                        }

                        $result = $image->createView('screen');
                        if (is_a($result, 'PEAR_Error')) {
                            exit;
                        }

                        $result = $faces->getFromPicture($image_id, $autocreate);
                        if (is_a($result, 'PEAR_Error')) {
                            exit;
                        }
                    }
                    if (!empty($result)) {
                        $imgdir = $registry->getImageDir('horde');
                        $customurl = Horde::applicationUrl('faces/custom.php');
                        $url = (!empty($args['url']) ? $args['url'] : '');
                        require_once ANSEL_TEMPLATES . '/faces/image.inc';
                    } else {
                        echo _("No faces found");
                    }
                    break;

                case 'delete':
                    // delete - deletes a single face from an image.
                    $face_id = (int)$args['face'];
                    $image = &$ansel_storage->getImage($image_id);
                    if (is_a($image, 'PEAR_Error')) {
                        die($image->getMessage());
                    }

                    $gallery = &$ansel_storage->getGallery($image->gallery);
                    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
                        die(_("Access denied editing the photo."));
                    }

                    $faces = Ansel_Faces::factory();
                    if (is_a($faces, 'PEAR_Error')) {
                        die($faces->getMessage());
                    }

                    $result = $faces->delete($image, $face_id);
                    if (is_a($result, 'PEAR_Error')) {
                        die($result->getMessage());
                    }
                    break;

                case 'setname':
                    // setname - sets the name of a single image.
                    $face_id = (int)$args['face'];
                    $name = $args['facename'];
                    $image = &$ansel_storage->getImage($image_id);
                    if (is_a($image, 'PEAR_Error')) {
                        die($image->getMessage());
                    }

                    $gallery = &$ansel_storage->getGallery($image->gallery);
                    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
                        die(_("You are not allowed to edit this photo."));
                    }

                    $faces = Ansel_Faces::factory();
                    if (is_a($faces, 'PEAR_Error')) {
                        die($faces->getMessage());
                    }

                    $result = $faces->setName($face_id, $name);
                    if (is_a($result, 'PEAR_Error')) {
                        die($result->getDebugInfo());
                    }
                    echo Ansel_Faces::getFaceTile($face_id);
                    break;
                }

            }
        }
    }

}
