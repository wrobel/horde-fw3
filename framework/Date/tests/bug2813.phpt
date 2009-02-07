--TEST--
Bug #2813: Wrong recurrence end from imported iCalendar events.
--FILE--
<?php

require 'Horde/Date/Recurrence.php';
require 'Horde/iCalendar.php';

$iCal = new Horde_iCalendar();
$iCal->parsevCalendar(file_get_contents(dirname(__FILE__) . '/fixtures/bug2813.ics'));
$components = $iCal->getComponents();

putenv('TZ=US/Eastern');

foreach ($components as $content) {
    if (is_a($content, 'Horde_iCalendar_vevent')) {
        $start = new Horde_Date($content->getAttribute('DTSTART'));
        $end = new Horde_Date($content->getAttribute('DTEND'));
        $rrule = $content->getAttribute('RRULE');
        $recurrence = new Horde_Date_Recurrence($start, $end);
        $recurrence->fromRRule20($rrule);
        break;
    }
}

$after = array('year' => 2006, 'month' => 6);
for ($mday = 16; $mday <= 18; $mday++) {
    $after['mday'] = $mday;
    var_dump($recurrence->nextRecurrence($after));
}

?>
--EXPECT--
object(horde_date)(7) {
  ["year"]=>
  int(2006)
  ["month"]=>
  int(6)
  ["mday"]=>
  int(16)
  ["hour"]=>
  int(18)
  ["min"]=>
  int(0)
  ["sec"]=>
  int(0)
  ["_supportedSpecs"]=>
  string(21) "%CdDeHImMnRStTyYbBpxX"
}
object(horde_date)(7) {
  ["year"]=>
  int(2006)
  ["month"]=>
  int(6)
  ["mday"]=>
  int(17)
  ["hour"]=>
  int(18)
  ["min"]=>
  int(0)
  ["sec"]=>
  int(0)
  ["_supportedSpecs"]=>
  string(21) "%CdDeHImMnRStTyYbBpxX"
}
bool(false)
