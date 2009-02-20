<?php
/**
 * Test the incoming filter class within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/IncomingTest.php,v 1.6.2.1 2009/02/20 22:37:17 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test.php';

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Extensions/OutputTestCase.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Incoming.php';

/**
 * Test the incoming filter.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/IncomingTest.php,v 1.6.2.1 2009/02/20 22:37:17 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_IncomingTest extends PHPUnit_Extensions_OutputTestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();

        $test = new Horde_Kolab_Test();
        $test->prepareBasicSetup();

        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;

        $_SERVER['SERVER_NAME'] = 'localhost';
    }


    /**
     * Test receiving the simple.eml message.
     */
    public function testSimpleIn()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=wrobel@example.org', '--recipient=test@example.org', '--user=', '--host=example.org');

        $inh = fopen(dirname(__FILE__) . '/fixtures/simple.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Incoming();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/simple2.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test handling the line end with incoming messages.
     */
    public function testIncomingLineEnd()
    {
        $_SERVER['argv'] = array($_SERVER['argv'], '--host=example.org', '--sender=wrobel@example.org', '--recipient=test@example.org', '--client=127.0.0.1', '--user=');

        $inh = fopen(dirname(__FILE__) . '/fixtures/empty.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Incoming();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/empty2.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        } else {
            $this->assertTrue(empty($result));
        }
    }
}
