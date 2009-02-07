<?php
require_once dirname(__FILE__) . '/TestBase.php';
/**
 * Unit tests for Ansel_Gallery /  Ansel_Gallery_Share stuff.
 *
 * NOTE: These test operate on the globally configured database.
 *       While there should be no destructive actions taken against existing
 *       data, it does modify the database so you should be sure to have a
 *       backup of any important tables before running these tests on production
 *       systems.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

class Ansel_GalleryTest Extends Ansel_TestBase {

    function setUp()
    {
         parent::setUp();
    }

    /**
     * Tests entire lifecycle of an Ansel_Gallery. Checks for proper creation
     * and Ansel_Gallery_Share being set correctly, checks proper setting of
     * gallery attributes and tags and checks for proper removal of gallery.
     * Checks that sub-galleries are created with proper heirarchy and that
     * parents are not removeable while they contain children.
     *
     * @dataProvider newGalleryData
     */
    function test_galleryCRUD($attributes)
    {
        global $ansel_storage, $ansel_shares;

        // Required values.
        if (empty($attributes['owner'])) {
            $attributes['owner'] = Auth::getAuth();
        }
        if (empty($attributes['name'])) {
            $attributes['name'] = _("Unnamed");
        }
        if (empty($attributes['desc'])) {
            $attributes['desc'] = '';
        }

        $gallery = $ansel_storage->createGallery($attributes);
        if (is_a($gallery, 'PEAR_Error')) {
            $this->fail($gallery->getMessage());
        }
        $id = $gallery->id;

        // Make sure no overt errors...
        $this->assertEquals(true, is_a($gallery, 'Ansel_Gallery'));

        // Check for proper share creation.
        $this->assertEquals(true, is_a($gallery->_galleryShare, 'Ansel_Gallery_Share'));

        // Save for later.
        $shareId = $gallery->_galleryShare->getId();

        // Delete our copy to see if we can get it back.
        unset($gallery);

        $gallery = $ansel_storage->getGallery($id);
        $this->assertEquals(true, is_a($gallery, 'Ansel_Gallery'));

        // Check the values to be sure they were set correctly.
        foreach ($attributes as $key => $value) {
            // The tags are not retrieved via 'get'
            if ($key == 'tags') {
                foreach ($value as $tag) {
                    if (!array_search($tag, $gallery->getTags())) {
                        $this->fail('Failed checking tags');
                        exit;
                    }
                }
            } else {
                $this->assertEquals($value, $gallery->get($key));
            }
        }
        // Check that perms were correctly set / not set etc...
        $this->assertEquals(true, $gallery->hasPermission('test_user', PERMS_EDIT));
        if ($gallery->hasPermission('arubinsk', PERMS_EDIT)) {
            $this->fail("Permission check failed for arubinsk/PERMS_EDIT");
        }

        // Try adding a sub-gallery.
        $attributes['desc'] = 'Sub Gallery';
        $subGallery = $ansel_storage->createGallery($attributes, null, $gallery->id);
        if (!is_a($subGallery, 'Ansel_Gallery')) {
            $this->fail('Sub gallery creation failed.');
        }

        // Check the parent to be sure it was set correctly.
        $parent = $subGallery->getParent();
        $this->assertEquals($parent->id, $gallery->id);

        // See if listing subgalleries works.
        $children = $parent->countGalleryChildren();

        // Since we did not add any images to the gallery, this should
        // return 1.
        $this->assertEquals(1, $children, 'Did not return expected children count');

        //...and that it agrees with Ansel_Storage::countGalleries()
        $children = $GLOBALS['ansel_storage']->countGalleries('test_user',
                                                              PERMS_SHOW,
                                                              'test_user',
                                                              $parent->get('id'),
                                                              false);
        $this->assertEquals(1, $children, 'Ansel_Storage::countGalleries() failed.');

        // ... and that that is different than the datatree_root result.
        $children = $GLOBALS['ansel_storage']->countGalleries('test_user',
                                                              PERMS_SHOW,
                                                              'test_user',
                                                              null);
        if (!$children > 1) {
            $this->fail('Ansel_Storage::countGalleries() failed at the root.');
        }

        // Now try to remove the gallery. This should fail since we have a
        // child gallery.
        $result = $ansel_storage->removeGallery($gallery);
        if (!is_a($result, 'PEAR_Error')) {
            $this->fail('Gallery should not be removable if it contains children.');
        }

        // Delete the sub gallery.
        $ansel_storage->removeGallery($subGallery);

        // Then the parent.
        $ansel_storage->removeGallery($gallery);

        // Make sure the share got killed as well
        $share = $ansel_storage->shares->getGalleryShare($shareId);
        if (!is_a($share->getId(), 'PEAR_Error')) {
            $this->fail('Share was not properly deleted.');
        }
    }

    /**
     * Test provider to provide test gallery data for gallery creation.
     *
     */
    public static function newGalleryData()
    {
        $galleries = array(
            array(array('owner' => 'test_user',
                  'name' => 'Test Gallery',
                  'desc' => 'this is a test',
                  'tags' => array('tag1', 'tag2'))));
        return $galleries;

    }

}