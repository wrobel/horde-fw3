<?php
/**
 * Test reseting the gallery default image
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */
require_once dirname(__FILE__) . '/TestBase.php';

class Ansel_ResetDefaultImageTest Extends Ansel_TestBase {

    var $fixture = null;
    var $gallery = null;

    function setup()
    {
        parent::setup();

        /* Hackish - but saves me from doing a full test DB implementation */
        $fixture = array('image_id' => 1590);

        /* Create a new default image to be sure it's there */
        $image = &$GLOBALS['ansel_storage']->getImage($fixture['image_id']);
        $this->gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
        $this->gallery->getDefaultImage('ansel_polaroid');
    }

    function test_ResetDefaultImage()
    {
        global $ansel_storage;

        $id = unserialize($this->gallery->get('default_prettythumb'));
        $styleHash = md5('polaroidthumb.white');
        $result = $this->gallery->removeImage($id[$styleHash]);
        //Check that the image was really removed.
        if (!is_a($ansel_storage->getImage($id[$styleHash]), 'PEAR_Error')) {
            $this->fail("Default image deletion failed.");
        }

        /* Set the property */
        $this->gallery->set('default_prettythumb', '', true);
        $gallery = $ansel_storage->getGallery($this->gallery->id);
        if ($this->gallery->get('default_prettythumb')) {
            $this->fail('Removing default image from gallery failed.');
        }
    }

}