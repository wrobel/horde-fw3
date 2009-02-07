--TEST--
Util::removeParameter() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';

$url = 'test?foo=1&bar=2';
echo Util::removeParameter($url, 'foo') . "\n";
echo Util::removeParameter($url, 'bar') . "\n";
echo Util::removeParameter($url, array('foo', 'bar')) . "\n";
$url = 'test?foo=1&amp;bar=2';
echo Util::removeParameter($url, 'foo') . "\n";
echo Util::removeParameter($url, 'bar') . "\n";
echo Util::removeParameter($url, array('foo', 'bar')) . "\n";
$url = 'test?foo=1&bar=2&baz=3';
echo Util::removeParameter($url, 'foo') . "\n";
$url = 'test?foo=1&amp;bar=2&amp;baz=3';
echo Util::removeParameter($url, 'foo') . "\n";

?>
--EXPECT--
test?bar=2
test?foo=1
test
test?bar=2
test?foo=1
test
test?bar=2&baz=3
test?bar=2&amp;baz=3
