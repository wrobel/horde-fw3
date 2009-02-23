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
require_once 'Horde/Kolab/Test/Filter.php';

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
class Horde_Kolab_Filter_ContentTest extends Horde_Kolab_Test_Filter
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        $result = $this->prepareBasicSetup();

        $this->server  = &$result['server'];
        $this->storage = &$result['storage'];
        $this->auth    = &$result['auth'];

        global $conf;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;
        $conf['kolab']['imap']['allow_special_users'] = true;
        $conf['kolab']['filter']['reject_forged_from_header'] = false;
        $conf['kolab']['filter']['email_domain'] = 'example.com';
        $conf['kolab']['filter']['privileged_networks'] = '127.0.0.1';
        $conf['kolab']['filter']['verify_from_header'] = true;
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

        ob_start();

        /* Parse the mail */
        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }

        $this->output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(file_get_contents(dirname(__FILE__)
                                              . '/fixtures/simple_out.ret'), $this->output);

    }

    /**
     * Test sending a message from a priviledged network.
     */
    public function testPriviledgedOut()
    {
        global $conf;

        $conf['kolab']['filter']['privileged_networks'] = '192.168.0.0/16';

        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--user=', '--recipient=you@example.com', '--client=', '--host=example.com');

        $inh = fopen(dirname(__FILE__) . '/fixtures/forged.eml', 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        ob_start();

        /* Parse the mail */
        $result = $parser->parse($inh, 'echo');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result);
        } else {
            $this->assertTrue(empty($result));
        }

        $this->output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(file_get_contents(dirname(__FILE__)
                                              . '/fixtures/privileged.ret'), $this->output);
    }

    /**
     * Test sending the tiny.eml message.
     *
     * @dataProvider addressCombinations
     */
    public function testSendingValidated($user, $client, $from, $to, $file, $error = '')
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0],
                                 '--sender=' . $from,
                                 '--recipient=' . $to,
                                 '--user=' . $user,
                                 '--host=example.com',
                                 '--client=' . $client);

        $in = file_get_contents(dirname(__FILE__) . '/fixtures/' . $file . '.eml', 'r');

        $tmpfile = Util::getTempFile('KolabFilterTest');
        $tmpfh = @fopen($tmpfile, 'w');
        @fwrite($tmpfh, sprintf($in, $from, $to));
        @fclose($tmpfh);

        $inh = @fopen($tmpfile, 'r');

        /* Setup the class */
        $parser   = &new Horde_Kolab_Filter_Content();

        ob_start();

        /* Parse the mail */
        $result = $parser->parse($inh, 'echo');
        if (empty($error)) {
            $this->assertNoError($result);
            $this->assertTrue(empty($result));

            $output = ob_get_contents();
            ob_end_clean();

            $out = file_get_contents(dirname(__FILE__) . '/fixtures/' . $file . '.ret');
            $this->assertEquals(sprintf($out, $from, $to), $output);
        } else {
            $this->assertError($result, $error);
        }

    }

    public function addressCombinations()
    {
        return array(
            array('', '192.168.178.1', 'me@example.com', 'you@example.com', 'forged'),
            array('', '', 'me@example.com', 'you@example.net', 'vacation'),
            array('', '', 'me@example.com', 'you@example.com', 'tiny'),
            array('me@example.org', 'remote.example.com', 'me@example.org', 'you@example.org', 'validation'),
            array('me@example.org', 'remote.example.com', 'me.me@example.org', 'you@example.org', 'validation'),
            array('me@example.org', 'remote.example.com', 'me.me@example.org', 'you@example.org', 'validation'),
            array('me@example.org', 'remote.example.com', 'meme@example.org', 'you@example.org', 'validation'),
            array('me@example.org', 'remote.example.com', 'else@example.org', 'you@example.org', 'validation'),
            array('me@example.org', 'remote.example.com', 'else3@example.org', 'you@example.org', 'validation', 'Invalid From: header. else3@example.org looks like a forged sender'),
        );
    }
}
