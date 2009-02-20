<?php
/**
 * Test resource handling within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4 2008/12/12 15:24:04 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Resource.php';
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/iCalendar.php';
require_once 'Horde/iCalendar/vfreebusy.php';

/**
 * Test resource handling
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4 2008/12/12 15:24:04 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ResourceTest extends Horde_Kolab_Test
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();

        $this->prepareBasicSetup();

        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;

        $_SERVER['SERVER_NAME'] = 'localhost';
    }


    /**
     * Test retrieval of the resource information
     */
    public function testGetResourceData()
    {
        $r = &new Kolab_Resource();
        $d = $r->_getResourceData('test@example.org', 'wrobel@example.org');
        $this->assertNoError($d);
        $this->assertEquals('wrobel@example.org', $d['id']);
        $this->assertEquals('home.example.org', $d['homeserver']);
        $this->assertEquals('ACT_REJECT_IF_CONFLICTS', $d['action']);
        $this->assertEquals('Gunnar Wrobel', $d['cn']);
    }

    /**
     * Test manual actions
     */
    public function testManual()
    {
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('otherhost', 'test@example.org', 'wrobel@example.org', null));
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('localhost', 'test@example.org', 'wrobel@example.org', null));
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
        //$this->expectOutputString('');
        
        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        } else {
            $this->assertTrue(empty($result));
        }
    }

}
