--TEST--
Test for Bug #2838, overwriting of preferences when multiple scopes are retrieved.
--FILE--
<?php

define('HORDE_BASE', dirname(dirname(dirname(dirname(__FILE__)))));
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/Prefs.php';

$registry = &Registry::singleton();

$prefs = Prefs::factory('session', 'horde', 'testuser', 'testpw');
$prefs->retrieve('imp');
$prefs->setValue('last_login', 'test');
echo $prefs->getValue('last_login') . "\n";

$prefs->retrieve('ingo');
echo $prefs->getValue('last_login') . "\n";

?>
--EXPECT--
test
test
