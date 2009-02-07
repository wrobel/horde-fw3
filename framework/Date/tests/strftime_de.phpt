--TEST--
Horde_Date::strftime() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Date.php';
setlocale(LC_TIME, 'de_DE');

$date = new Horde_Date('2001-02-03 16:05:06');

echo strftime('%b%n%B%n%p%n%r%n%x%n%X%n%n', $date->timestamp());
echo $date->strftime('%b%n%B%n%p%n%r%n%x%n%X');

?>
--EXPECT--
Feb
Februar

04:05:06 
03.02.2001
16:05:06

Feb
Februar

04:05:06 
03.02.2001
16:05:06
