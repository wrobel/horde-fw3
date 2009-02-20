<?php
/**
 * Test resource handling within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceOutputTest.php,v 1.3.2.1 2009/02/20 22:37:17 wrobel Exp $
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
require_once 'Horde/Kolab/Resource.php';
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/iCalendar.php';
require_once 'Horde/iCalendar/vfreebusy.php';

/**
 * Test resource handling
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceOutputTest.php,v 1.3.2.1 2009/02/20 22:37:17 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ResourceOutputTest extends PHPUnit_Extensions_OutputTestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();

        $this->test = new Horde_Kolab_Test();
        $this->test->prepareBasicSetup();

        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;

        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    /**
     * Test invitation.
     */
    public function testRecurrenceInvitation()
    {
        require_once 'Horde/iCalendar/vfreebusy.php';
        $fb = &new Horde_iCalendar_vfreebusy();
        $fb->setAttribute('DTSTART', '20080926T000000');
        $fb->setAttribute('DTEND', '20081126T000000');

        $stub = $this->getMock('Kolab_Resource');
        $stub->expects($this->any())
            ->method('internalGetFreeBusy')
            ->will($this->returnValue($fb));
        
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=test@example.org', '--recipient=wrobel@example.org', '--user=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/recur_invitation.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Incoming();
        
        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/recur_invitation.ret'));
        
        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test invitation.
     */
    public function testLongStringInvitation()
    {
        require_once 'Horde/iCalendar/vfreebusy.php';
        $fb = &new Horde_iCalendar_vfreebusy();
        $fb->setAttribute('DTSTART', '20080926T000000');
        $fb->setAttribute('DTEND', '20081126T000000');

        $stub = $this->getMock('Kolab_Resource');
        $stub->expects($this->any())
            ->method('internalGetFreeBusy')
            ->will($this->returnValue($fb));
        
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=test@example.org', '--recipient=wrobel@example.org', '--user=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/longstring_invitation.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Incoming();
        
        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/longstring_invitation.ret'));
        
        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        } else {
            $this->assertTrue(empty($result));
        }
    }

}
