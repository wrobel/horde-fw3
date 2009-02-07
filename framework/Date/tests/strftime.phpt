--TEST--
Horde_Date::strftime() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Date.php';
setlocale(LC_TIME, 'en_US.UTF-8');

$date = new Horde_Date('2001-02-03 16:05:06');
echo strftime('%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%%n%n', $date->timestamp());
echo $date->strftime('%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%%n%n');

echo strftime('%b%n%B%n%p%n%r%n%x%n%X%n%n', $date->timestamp());
echo $date->strftime('%b%n%B%n%p%n%r%n%x%n%X%n%n');

$date->year = 1899;
echo $date->strftime('%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%%n');

?>
--EXPECT--
20
03
02/03/01
 3
16
04
02
05
16:05
06
	
16:05:06
01
2001
%

20
03
02/03/01
 3
16
04
02
05
16:05
06
	
16:05:06
01
2001
%

Feb
February
PM
04:05:06 PM
02/03/2001
04:05:06 PM

Feb
February
PM
04:05:06 PM
02/03/2001
04:05:06 PM

18
03
02/03/99
 3
16
04
02
05
16:05
06
	
16:05:06
99
1899
%
