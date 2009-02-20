<?php
/**
 * Test the content filter class within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ContentTest.php,v 1.6.2.1 2009/02/20 22:37:17 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Content.php';

/**
 * Test the content filter.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ContentTest.php,v 1.6.2.1 2009/02/20 22:37:17 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ContentTest extends PHPUnit_Extensions_OutputTestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();
        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['server'] = array(
            'driver' => 'test',
            'params' => array(
                'cn=me' => array(
                    'dn' => 'cn=me',
                    'data' => array(
                        'objectClass' => array('kolabInetOrgPerson'),
                        'mail' => array('me@example.com'),
                        'kolabImapHost' => array('localhost'),
                        'uid' => array('me'),
                    )
                ),
                'cn=you' => array(
                    'dn' => 'cn=you',
                    'data' => array(
                        'objectClass' => array('kolabInetOrgPerson'),
                        'mail' => array('you@example.com'),
                        'kolabImapHost' => array('localhost'),
                        'uid' => array('you'),
                    )
                ),
            )
        );
        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;
        $conf['kolab']['imap']['allow_special_users'] = true;
        $conf['kolab']['filter']['reject_forged_from_header'] = false;
        $conf['kolab']['filter']['email_domain'] = 'example.com';
        $conf['kolab']['filter']['privileged_networks'] = '127.0.0.1';
   }


    /**
     * Test sending the simple.eml message.
     */
    public function testSimpleOut()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--recipient=you@example.com', '--user=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/simple.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/simple_out.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test sending the forged.eml message.
     */
    public function testForgedOut()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--user=', '--recipient=you@example.com', '--client=192.168.178.1', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/forged.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/forged.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test sending the vacation.eml message.
     */
    public function testVacationOut()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--user=', '--recipient=you@example.net', '--client=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/vacation.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/vacation.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test sending a message from a prviledged network.
     */
    public function testPriviledgedOut()
    {
        global $conf;

        $conf['kolab']['filter']['privileged_networks'] = '192.168.0.0/16';

        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--user=', '--recipient=you@example.com', '--client=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/forged.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/privileged.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test sending the tiny.eml message.
     */
    public function testTinyOut()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--recipient=you@example.com', '--user=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/tiny.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        /* Parse the mail */
        $this->expectOutputString(file_get_contents(dirname(__FILE__)
                                                    . '/fixtures/tiny.ret'));

        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }
    }
}
