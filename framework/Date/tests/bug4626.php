<?php

require 'Horde/Date/Recurrence.php';
require 'Horde/iCalendar.php';

$rrule = new Horde_Date_Recurrence('2008-04-05 00:00:00');
$rrule->setRecurType(HORDE_DATE_RECUR_MONTHLY_WEEKDAY);
$rrule->setRecurOnDay(HORDE_DATE_MASK_SATURDAY);

echo $rrule->toRRule10(new Horde_iCalendar()) . "\n";
echo $rrule->toRRule20(new Horde_iCalendar()) . "\n";

?>
