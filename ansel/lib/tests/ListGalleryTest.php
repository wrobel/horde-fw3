<?php
require_once dirname(__FILE__) . '/TestBase.php';
/**
 * Unit test for listGalleries. Basically just check that listGalleries()
 * doesn't return any errors, and that changing sort field, direction etc...
 * returns expected data.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

class Ansel_ListGalleryTest Extends Ansel_TestBase {

   /**
     * Test that $GLOBALS['ansel_storage']->listGalleries() returns Ansel_Galleries
     * @TODO: Use a known set of data for testing!
     */
    function test_listGalleries()
    {
        $results = $GLOBALS['ansel_storage']->listGalleries();;
        foreach ($results as $key => $gallery) {
            if (!is_a($gallery, 'Ansel_Gallery')) {
                $this->fail("Retrieving Ansel Gallery failed.");
            }
            echo 'Gallery id = ' . $key . "\n";
        }

        // Now test with some parameters.
        $results = $GLOBALS['ansel_storage']->listGalleries(PERMS_SHOW,
                                                            'test_user', null,
                                                            true, 0, 6,
                                                            'last_modified', 1);
        foreach ($results as $key => $gallery) {
            if (!is_a($gallery, 'Ansel_Gallery')) {
                print_r($gallery);
                $this->fail("Ansel Gallery creation failed.");

            }
            echo 'Gallery id = ' . $key . "\n";
        }

    }

}
