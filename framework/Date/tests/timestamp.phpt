--TEST--
Horde_Date::timestamp() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Date.php';
putenv('TZ=America/New_York');

$date = new Horde_Date(array('mday' => 1, 'month' => 10, 'year' => 2004));
echo $date->timestamp() . "\n";
echo mktime(0, 0, 0, $date->month, $date->mday, $date->year) . "\n";

$date = new Horde_Date(array('mday' => 1, 'month' => 5, 'year' => 1948));
echo $date->timestamp() . "\n";
echo mktime(0, 0, 0, $date->month, $date->mday, $date->year) . "\n";
?>
--EXPECT--
1096603200
1096603200
-683841600
-683841600
