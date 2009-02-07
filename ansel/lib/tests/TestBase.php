<?php
/**
 * Base class for Ansel test cases
 *
 * $Horde: ansel/lib/tests/TestBase.php,v 1.1 2008/01/16 01:48:09 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 * @subpackage UnitTests
 */
class Ansel_TestBase Extends PHPUnit_Framework_TestCase {


    function setUp()
    {
        // TODO: Do we need to actually fake auth for any tests?
        @define('AUTH_HANDLER', true);
        @define('HORDE_BASE', dirname(__FILE__) . '/../../..');
        @define('ANSEL_BASE', dirname(__FILE__) . '/../..');

        require_once HORDE_BASE . '/lib/core.php';
        /* Turn PHP stuff off that can really screw things up. */
        ini_set('magic_quotes_sybase', 0);
        ini_set('magic_quotes_runtime', 0);
        ini_set('include_path', '/srv/www/pear:' . ini_get('include_path'));
        ini_set('zend.ze1_compatibility_mode', 0);

        /* Unset all variables populated through register_globals. */
        if (ini_get('register_globals')) {
            foreach (array_keys($_GET) as $key) {
                unset($$key);
            }
            foreach (array_keys($_POST) as $key) {
                unset($$key);
            }
            foreach (array_keys($_COOKIE) as $key) {
                unset($$key);
            }
            foreach (array_keys($_ENV) as $key) {
                unset($$key);
            }
            foreach (array_keys($_SERVER) as $key) {
                unset($$key);
            }
        }

        /* If the Horde Framework packages are not installed in PHP's global
         * include_path, you must add an ini_set() call here to add their location to
         * the include_path. */
        // ini_set('include_path', dirname(__FILE__) . PATH_SEPARATOR . ini_get('include_path'));

        /* PEAR base class. */
        include_once 'PEAR.php';

        /* Horde core classes. */
        include_once 'Horde.php';
        include_once 'Horde/Registry.php';
        include_once 'Horde/String.php';
        include_once 'Horde/NLS.php';
        include_once 'Horde/Notification.php';
        include_once 'Horde/Auth.php';
        include_once 'Horde/Browser.php';
        include_once 'Horde/Perms.php';

        /* Browser detection object. */
        if (class_exists('Browser')) {
            $GLOBALS['browser'] = &Browser::singleton();
        }

        // Set up the CLI enviroment.
        require_once 'Horde/CLI.php';
        Horde_CLI::init();

        // Need to load registry. For some reason including base.php doesn't
        // work properly yet. ($registry is not set when prefs.php loads)?
        //require_once HORDE_BASE . '/lib/base.php';
        $GLOBALS['registry'] = &Registry::singleton();
        @define('ANSEL_TEMPLATES', $GLOBALS['registry']->get('templates', 'ansel'));

        require_once ANSEL_BASE . '/lib/base.php';
        require_once ANSEL_BASE . '/lib/Ansel.php';

        // Auth
        $auth = &Auth::singleton('sql');
        $auth->setAuth('test_user', 'blah');
    }

    /**
     * Asserts that the supplied result is not a PEAR_Error
     *
     * Fails with a descriptive message if so
     * @param mixed $result  The value to check
     * @return boolean  Whether the assertion was successful
     */
    function assertOk($result)
    {
        if (is_a($result, 'DB_Error')) {
            $this->fail($result->getDebugInfo());
            return false;
        } elseif (is_a($result, 'PEAR_Error')) {
            $this->fail($result->getMessage());
            return false;
        }

        return true;
    }

}

