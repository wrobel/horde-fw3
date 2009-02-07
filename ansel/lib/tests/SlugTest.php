<?php
require_once dirname(__FILE__) . '/TestBase.php';
/**
 * Unit tests for slug stuff.
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

class Ansel_SlugTest Extends Ansel_TestBase {

    function setUp()
    {
         parent::setUp();
    }

    function test_slug()
    {
        global $ansel_storage, $ansel_shares;

        /* Just get an arbitrary gallery from storage */
        $gallery = $ansel_storage->getRandomGallery();
        $this->assertOk($gallery);

        /* Remember the id */
        $id = $gallery->id;

        /* Read the slug */
        $slug = $gallery->get('slug');

        /* Set the slug to something new */
        $slug = $gallery->set('slug', 'SLUG_TEST');
        $gallery->save();

        /* Now try to retrieve the gallery by the slug */
        $gallery = $ansel_storage->getGalleryBySlug('SLUG_TEST');
        $this->assertEquals($id, $gallery->id, 'Ansel_Storage::getGalleryBySlug() failed');

        /* Restore the previous slug */
        $gallery->set('slug', $slug);
        $gallery->save();
    }

}