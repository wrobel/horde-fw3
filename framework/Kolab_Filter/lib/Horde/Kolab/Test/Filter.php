<?php
/**
 * Base for PHPUnit scenarios.
 *
 * $Horde: framework/Kolab_Storage/lib/Horde/Kolab/Test/Storage.php,v 1.1.2.3 2009/01/06 15:23:18 jan Exp $
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 *  We need the unit test framework
 */
require_once 'Horde/Kolab/Test/Storage.php';

/**
 * Base for PHPUnit scenarios.
 *
 * $Horde: framework/Kolab_Storage/lib/Horde/Kolab/Test/Storage.php,v 1.1.2.3 2009/01/06 15:23:18 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Test_Filter extends Horde_Kolab_Test_Storage
{
    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runGiven($world, $action, $arguments);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runWhen($world, $action, $arguments);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runThen($world, $action, $arguments);
        }
    }

    /**
     * Fill a Kolab Server with test users.
     *
     * @param Kolab_Server &$server The server to populate.
     *
     * @return Horde_Kolab_Server The empty server.
     */
    public function prepareUsers(&$server)
    {
        parent::prepareUsers(&$server);
        $result = $server->add($this->provideFilterUserOne());
        $this->assertNoError($result);
        $result = $server->add($this->provideFilterUserTwo());
        $this->assertNoError($result);
        $result = $server->add($this->provideFilterUserThree());
        $this->assertNoError($result);
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserOne()
    {
        return array('givenName' => 'Me',
                     'sn' => 'Me',
                     'type' => KOLAB_OBJECT_USER,
                     'mail' => 'me@example.org',
                     'uid' => 'me',
                     'userPassword' => 'me',
                     'kolabHomeServer' => 'home.example.org',
                     'kolabImapServer' => 'imap.example.org',
                     'kolabFreeBusyServer' => 'https://fb.example.org/freebusy',
                     KOLAB_ATTR_IPOLICY => array('ACT_REJECT_IF_CONFLICTS'),
                     'alias' => array('me.me@example.org', 'MEME@example.org'),
                );
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserTwo()
    {
        return array('givenName' => 'You',
                     'sn' => 'You',
                     'type' => KOLAB_OBJECT_USER,
                     'mail' => 'you@example.org',
                     'uid' => 'you',
                     'userPassword' => 'you',
                     'kolabHomeServer' => 'home.example.org',
                     'kolabImapServer' => 'home.example.org',
                     'kolabFreeBusyServer' => 'https://fb.example.org/freebusy',
                     'alias' => array('you.you@example.org'),
                     KOLAB_ATTR_KOLABDELEGATE => 'wrobel@example.org',);
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserThree()
    {
        return array('givenName' => 'Else',
                     'sn' => 'Else',
                     'type' => KOLAB_OBJECT_USER,
                     'mail' => 'else@example.org',
                     'uid' => 'else',
                     'userPassword' => 'else',
                     'kolabHomeServer' => 'home.example.org',
                     'kolabImapServer' => 'home.example.org',
                     'kolabFreeBusyServer' => 'https://fb.example.org/freebusy',
                     KOLAB_ATTR_KOLABDELEGATE => 'me@example.org',);
    }
}
