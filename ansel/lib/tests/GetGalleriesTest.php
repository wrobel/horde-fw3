<?php
require_once dirname(__FILE__) . '/TestBase.php';
/**
 * Unit test for getGalleries.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

class Ansel_GetGalleriesTest Extends Ansel_TestBase {

    function test_listGalleries()
    {
        /* First get a known list of gallery ids */
        $galleries = $GLOBALS['ansel_storage']->listGalleries();
        $ids = array_keys($galleries);

        /* Assume there are at least three galleries */
        $ids = array_slice($ids, 0, 3);

        $results = $GLOBALS['ansel_storage']->getGalleries($ids);;
        foreach ($results as $key => $gallery) {
            if (!is_a($gallery, 'Ansel_Gallery')) {
                $this->fail("Ansel Gallery creation failed.");
            }
            echo 'Gallery id = ' . $key . "\n";
        }

        /* Now validate the returned galleries */
        if (count($results) != 3) {
            $this->fail('Did not return expected number of galleries.');
        }
        foreach ($ids as $id) {
            if (!isset($results[$id])) {
                $this->fail('Did not return expected galleries.');
            }
        }

        /* Now try the getGallery() method to be sure it returns one as well */
        $result = $GLOBALS['ansel_storage']->getGallery($ids[0]);
        $this->assertOk($result);
        $this->assertEquals($ids[0], $result->id);
    }

}
