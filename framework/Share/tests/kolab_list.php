<?php

require_once 'Horde/Kolab/Test.php';
$test = new Horde_Kolab_Test();

$world = $test->prepareBasicSetup();

$test->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                               array('password' => 'none')));

$test->prepareNewFolder($world['storage'], 'Calendar', 'event');

require_once dirname(__FILE__) . '/../Share.php';

$shares = Horde_Share::singleton('kronolith', 'kolab');

$keys = array_keys($shares->listShares('wrobel@example.org'));
foreach ($keys as $key) {
  echo $key . "\n";
}
?>
