<?php
/**
 * Checks for the Kolab Free/Busy system.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/FreeBusyScenarioTest.php,v 1.2.2.1 2009/03/06 18:12:01 wrobel Exp $
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test.php';

/**
 * Checks for the Kolab Free/Busy system.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/FreeBusyScenarioTest.php,v 1.2.2.1 2009/03/06 18:12:01 wrobel Exp $
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
class Horde_Kolab_FreeBusy_FreeBusyScenarioTest extends Horde_Kolab_Test
{
    /**
     * Test triggering a calendar folder.
     *
     * @scenario
     *
     * @return NULL
     */
    public function triggering()
    {
        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', 'Calendar')
            ->and('triggering the folder', 'wrobel@example.org/Calendar')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the result should be an object of type', 'Horde_Kolab_FreeBusy_View_vfb');
    }
}