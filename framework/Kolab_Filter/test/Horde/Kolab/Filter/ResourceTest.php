<?php
/**
 * Test resource handling within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4.2.2 2009/02/24 11:17:40 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Filter.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Resource.php';
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/iCalendar.php';
require_once 'Horde/iCalendar/vfreebusy.php';

/**
 * Test resource handling
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4.2.2 2009/02/24 11:17:40 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ResourceTest extends Horde_Kolab_Test_Filter
{

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
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $this->assertEquals(1222419600, $events[0]['start-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test an that contains a long string.
     */
    public function testLongStringInvitation()
    {
        require_once 'Horde/iCalendar/vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/longstring_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            $summaries[] = $event['summary'];
        }
        $this->assertContains('invitationtest2', $summaries);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }
}
