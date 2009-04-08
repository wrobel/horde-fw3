<?php

require_once dirname(dirname(__FILE__)) . '/iCalendar.php';
require_once 'Horde/Date.php';
putenv('TZ=UTC');

$test_files = glob(dirname(__FILE__) . '/fixtures/vTimezone/*.???');
foreach ($test_files as $file) {
    echo basename($file) . "\n";
    $ical = new Horde_iCalendar();
    $ical->parsevCalendar(file_get_contents($file));
    foreach ($ical->getComponents() as $component) {
        if ($component->getType() != 'vEvent') {
            continue;
        }
        $date = $component->getAttribute('DTSTART');
        if (is_array($date)) {
            continue;
        }
        echo $component->getAttribute('SUMMARY') . "\n";
        $d = new Horde_Date($date);
        echo $d->format('H:i') . "\n";
    }
    echo "\n";
}

?>
