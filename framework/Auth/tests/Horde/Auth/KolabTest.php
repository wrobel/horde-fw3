<?php
/**
 * Kolab authentication tests.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabTest.php,v 1.1.2.2 2009/01/06 15:22:52 jan Exp $
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test.php';

/**
 * Kolab authentication tests.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabTest.php,v 1.1.2.2 2009/01/06 15:22:52 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Auth_KolabTest extends Horde_Kolab_Test
{
    /**
     * Test loggin in after a user has been added.
     *
     * @return NULL
     */
    public function testLogin()
    {
        /** Create the test base */
        $server = &$this->prepareEmptyKolabServer();
        $auth = &$this->prepareKolabAuthDriver();
        $this->prepareBrowser();
        $this->prepareRegistry();
        $this->assertEquals('Auth_kolab', get_class($auth));

        /** Ensure we always use the test server */
        $GLOBALS['conf']['kolab']['server']['driver'] = 'test';

        $test_user = $this->provideBasicUserOne();
        $result = $server->add($test_user);
        $this->assertNoError($result);

        $uid = $server->uidForIdOrMail($test_user['mail']);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $result = $server->_bind($uid, $test_user['userPassword']);
        $this->assertTrue($result);

        $result = $auth->authenticate($test_user['mail'],
                                      array('password' => $test_user['userPassword']));
        $this->assertNoError($result);
        $this->assertTrue($result);

        $session = Horde_Kolab_Session::singleton();
        $this->assertNoError($session->user_mail);
        $this->assertEquals($test_user['mail'], $session->user_mail);

        $result = $server->_bind($uid, 'invalid');
        $this->assertError($result, 'Incorrect password!');

        /** Ensure we don't use a connection from older tests */
        $server->unbind();

        $result = $auth->authenticate($test_user['uid'],
                                      array('password' => 'invalid'));
        $this->assertNoError($result);
        $this->assertFalse($result);

        /** Ensure we don't use a connection from older tests */
        $server->unbind();
        $result = $auth->authenticate($test_user['uid'],
                                      array('password' => $test_user['userPassword']));
        $this->assertNoError($result);
        $this->assertTrue($result);

        $session = Horde_Kolab_Session::singleton();
        $this->assertNoError($session->user_mail);
        $this->assertEquals($test_user['mail'], $session->user_mail);

        $this->assertEquals($test_user['mail'], Auth::getAuth());
    }
}