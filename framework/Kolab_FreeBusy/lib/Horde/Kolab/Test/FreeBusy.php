<?php
/**
 * Base for PHPUnit scenarios.
 *
 * $Horde: framework/Kolab_FreeBusy/lib/Horde/Kolab/Test/FreeBusy.php,v 1.1.2.1 2009/03/06 20:47:39 wrobel Exp $
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
 * $Horde: framework/Kolab_FreeBusy/lib/Horde/Kolab/Test/FreeBusy.php,v 1.1.2.1 2009/03/06 20:47:39 wrobel Exp $
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
class Horde_Kolab_Test_Freebusy extends Horde_Kolab_Test_Storage
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

        $conf['kolab']['ldap']['phpdn'] = null;
        $conf['fb']['cache_dir']             = '/tmp';
        $conf['kolab']['freebusy']['server'] = 'https://fb.example.org/freebusy';
        $conf['fb']['use_acls'] = true;
    }

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
        case 'triggering the folder':
            include_once 'Horde/Kolab/FreeBusy.php';

            $_GET['folder']   = $arguments[0];
            $_GET['extended'] = '1';

            $fb = &new Horde_Kolab_FreeBusy();

            $world['result']['trigger'] = $fb->trigger();

            break;
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

}
