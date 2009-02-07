--TEST--
String:: case PHP 6 tests
--SKIPIF--
<?php
if (version_compare(PHP_VERSION, '6.0', '<')) {
   echo 'skip mbstring is broken in PHP < 6.0';
}
?>
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/../String.php';

echo String::upper('abCDefGHiI', true, 'iso-8859-9') . "\n";
echo String::lower('abCDefGHiI', true, 'iso-8859-9') . "\n";
echo "\n";
echo String::ucfirst('integer', true, 'us-ascii') . "\n";
echo String::ucfirst('integer', true, 'iso-8859-9') . "\n";

?>
--EXPECT--
ABCDEFGHÝI
abcdefghiý

Integer
Ýnteger
