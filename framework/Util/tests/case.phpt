--TEST--
String:: case tests
--SKIPIF--
<?php
if (!setlocale(LC_ALL, 'tr_TR')) echo 'skip No Turkish locale installed.';
?>
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/../String.php';

echo String::upper('abCDefGHiI', true, 'us-ascii') . "\n";
echo String::lower('abCDefGHiI', true, 'us-ascii') . "\n";
echo "\n";
echo String::upper('abCDefGHiI', true, 'Big5') . "\n";
echo String::lower('abCDefGHiI', true, 'Big5') . "\n";
echo "\n";
setlocale(LC_ALL, 'tr_TR');
echo strtoupper('abCDefGHiI') . "\n";
echo strtolower('abCDefGHiI') . "\n";
echo ucfirst('integer') . "\n";
echo "\n";
echo String::upper('abCDefGHiI') . "\n";
echo String::lower('abCDefGHiI') . "\n";
echo String::ucfirst('integer') . "\n";

?>
--EXPECT--
ABCDEFGHII
abcdefghii

ABCDEFGHII
abcdefghii

ABCDEFGHÝI
abcdefghiý
Ýnteger

ABCDEFGHII
abcdefghii
Integer
