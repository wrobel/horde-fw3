<?php
require_once dirname(__FILE__) . '/TestBase.php';
/**
 * Unit test for getGalleries.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

class Ansel_GetGalleriesBySlugsTest Extends Ansel_TestBase {

    function test_listGalleries()
    {
        $slugs = array('test', 'test1');

        $results = $GLOBALS['ansel_storage']->getGalleriesBySlugs($slugs);
        foreach ($results as $key => $gallery) {
            if (!is_a($gallery, 'Ansel_Gallery')) {
                $this->fail("Ansel Gallery creation failed.");
            }
            echo 'Gallery id = ' . $key . "\n";
        }
    }

}
