--TEST--
String::substr() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/../String.php';

$string = "Lörem ipsüm dölör sit ämet";
echo String::substr($string, 20, null, 'UTF-8') . "\n";
echo String::substr($string, -6, null, 'utf-8') . "\n";
echo String::substr($string, 0, 5, 'utf-8') . "\n";
echo String::substr($string, 0, -21, 'utf-8') . "\n";
echo String::substr($string, 6, 5, 'utf-8') . "\n";

?>
--EXPECT--
t ämet
t ämet
Lörem
Lörem
ipsüm
