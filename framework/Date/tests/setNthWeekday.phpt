--TEST--
Horde_Date::setNthWeekday() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Date.php';

$date = new Horde_Date();
$date->mday = 1;
$date->month = 10;
$date->year = 2004;

$date->setNthWeekday(HORDE_DATE_SATURDAY);
echo $date->mday . "\n";

$date->setNthWeekday(HORDE_DATE_SATURDAY, 2);
echo $date->mday . "\n";

?>
--EXPECT--
2
9
