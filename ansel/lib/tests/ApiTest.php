<?php
/**
 * Test for using other application scopes within Ansel.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/TestBase.php';

class Ansel_ApiTest Extends Ansel_TestBase {

    var $fixture = array();

    function setup()
    {
        parent::setup();
        $image = Ansel::getImageFromFile(dirname(__FILE__) . '/data/img01.jpg',
                                         array('description' => 'test image'));
         $this->assertOk($image);

        // Ansel::getImageFromFile() returns an array keyed differently than
        // what the api call expects.
        $img = array('filename' => $image['image_filename'],
                     'type' => $image['image_type'],
                     'data' => $image['data'],
                     'description' => $image['description']);

        $this->fixture = array('gallery_one' => array(
                                    'name' => 'Gallery One',
                                    'owner' => Auth::getAuth(),
                                    'desc' => 'This is from the ApiTest'),
                                    'image_one' => $img);
    }

    /**
     * Tests typical operations performed via the api
     *
     * Note that depending on the permissions of your VFS you might not
     * be able to see the images added by this test when viewed from the
     * Ansel UI since this script is probably not being run as your web server
     * user. Refreshing the VFS owner:group will fix it. Shouldn't be an issue
     * though since the test, if run completely, will remove any added images
     * anyway.
     *
     */
    function testAll()
    {
        global $registry, $conf;

        // Create the first gallery in the test_scope.
        $id = $registry->call('images/createGallery',
                              array(null, $this->fixture['gallery_one']));
        $this->assertOk($id);

        // Check that this id is returned in the list of Galleries.
        $galleries = $registry->call('images/listGalleries', array());
        if (is_a($galleries, 'PEAR_Error')) {
            $this->fail($galleries->getMessage());
        }
        $ids = array_keys($galleries);
        if (!in_array($id, $ids)) {
            $this->fail('The gallery was not returned via images/listGalleries');
        }

        // Check some attributes.
        $owner = $galleries[$id]->get('owner');
        $desc = $galleries[$id]->get('desc');
        $name = $galleries[$id]->get('name');
        $this->assertEquals($owner, $this->fixture['gallery_one']['owner']);
        $this->assertEquals($desc, $this->fixture['gallery_one']['desc']);
        $this->assertEquals($name, $this->fixture['gallery_one']['name']);

        // Check that images/galleryExists returns true
        $exists = $registry->call('images/galleryExists', array(null, $id));
        if (!$exists) {
            $this->fail('Gallery DOES exists but is not reported to.');
        }

        // Try adding an image to the new gallery.
        $imgInfo = $registry->call('images/saveImage',
                                  array(null, $id,
                                        $this->fixture['image_one']));

        if (!$imgInfo) {
            $this->fail("Adding image to gallery failed.\n" . $imgInfo->getMessage());
        }

        // Make sure it was added to correct gallery.
        $this->assertEquals($id, $imgInfo['gallery-id']);

        // Make sure it is included in the list of images from the gallery.
        $images = $registry->call('images/listImages', array(null, $id));
        $this->assertOk($images);

        $image_ids = array_keys($images);
        if (!in_array($imgInfo['image-id'], $image_ids)) {
            $this->fail("Image was not returned from listImages");
        }

        // Try to count the images in the gallery. This should return one.
        $count = $registry->call('images/count', array(null, $id));
        $this->assertEquals(1, $count);

        // Add another image and check again, but this time make this the
        // default
        $imgInfo2 = $registry->call('images/saveImage',
                                  array(null, $id,
                                        $this->fixture['image_one'], true));
        $count = $registry->call('images/count', array(null, $id));
        $this->assertEquals(2, $count);

        // Now check for the default image.
        $default = $registry->call('images/getDefaultImage',  array(null, $id));
        $this->assertEquals($imgInfo2['image-id'], $default);

        // Get the image url
        $url = $registry->call('images/getImageUrl',
                               array(null, $imgInfo2['image-id']));
        // Could return a url to /img/screen.php or a direct url to the vfs.
        if ($url != '/ansel/img/screen.php?image=' . $imgInfo2['image-id'] &&
            empty($conf['vfs']['direct'])) {
            $this->fail('Could not fetch the image url');
        } elseif (!empty($conf['vfs']['direct'])) {
            // Too lazy...just print out the url if we are doing direct vfs
            echo $url . "\n";
        }

        // See if get recent images returns the two we just added.
        $recent = $registry->call('images/getRecentImages',
                                  array(null, array($id), 2));

        $rimg = array();
        foreach ($recent as $image) {
            $rimg[] = $image->id;
        }
        if (!in_array($imgInfo['image-id'], $rimg) &&
            !in_array($imgInfo2['image-id'], $img)) {

            $this->fail('Fetching recent images failed.');
        }

        $count = $registry->call('images/countGalleries', array());
        $this->assertOk($count);
        if (!$count > 0) {
            $this->fail("Gallery cound should be at least 1");
        }

        // Clean up and test while we go...
        $results = $registry->call('images/removeImage',
                                   array(null, $id, $imgInfo['image-id']));
        $this->assertOk($results);

        //...and make sure it's not still there
        $images = $registry->call('images/listImages', array(null, $id));
        $this->assertOk($images);
        $image_ids = array_keys($images);
        if (in_array($imgInfo['image-id'], $image_ids)) {
            $this->fail('Image was not properly removed.');
        }

        // Now just kill the whole gallery.
        $results = $registry->call('images/removeGallery', array(null, $id));
        $this->assertOk($results);

        //...and verify.
        $results = $registry->call('images/galleryExists', array(null, $id));
        if ($results) {
            $this->fail('Gallery not removed properly.');
        }

        // Get a gallery id we can test with.
        //FIXME: this is wrong...the keys are share names now
        $ids = $GLOBALS['ansel_storage']->listGalleries();
        $ids = array_keys($ids);
        $id = array_pop($ids);
        $results = $registry->call('images/getGalleries', array($id));

        // Just spot check the id
        $this->assertEquals($id, $results[$id]['id']);
    }

}
