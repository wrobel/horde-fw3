<?php
/**
 * Test for using other application scopes within Ansel.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/TestBase.php';

class Ansel_ApiScopeTest Extends Ansel_TestBase {

    var $fixture = array();

    function setup()
    {
        parent::setup();
        $this->fixture = array('scope' => 'test_scope',
                         'gallery_one' => array('name' => 'Test Gallery One',
                                                'owner' => Auth::getAuth(),
                                                'desc' => 'This is a test for scope'));

    }

    function testOtherScope()
    {
        global $registry;

        // Create the first gallery in the test_scope.
        $id = $registry->call('images/createGallery',
                              array($this->fixture['scope'],
                                  $this->fixture['gallery_one']));
        $this->assertOk($id);

        // Check that this id is returned from the test_scope.
        $galleries = $registry->call('images/listGalleries',
                                     array($this->fixture['scope']));

        if (is_a($galleries, 'PEAR_Error')) {
            $this->fail($galleries->getMessage());
        }
        $ids = array_keys($galleries);
        $this->assertEquals($ids[0], $id);

        // Make sure it is *not* returned from ansel's scope
        $galleries = $registry->call('images/listGalleries',
                                     array('ansel'));
        if (is_a($galleries, 'PEAR_Error')) {
            $this->fail($galleries->getMessage());
        }
        $ids = array_keys($galleries);
        if (in_array($id, $ids)) {
            $this->fail('Gallery should not appear in Ansel scope');
        }

        // Clean up and delete the added gallery...might as well test
        // the api here as well.
        $results = $registry->call('images/removeGallery',
                                    array($this->fixture['scope'], $id));

        $this->assertOk($results);

    }



}
