<?php

require_once dirname(__FILE__) . '/../iCalendar.php';
$ical = new Horde_iCalendar();
$readIcal = new Horde_iCalendar();

$event1 = Horde_iCalendar::newComponent('vevent', $ical);

$event1->setAttribute('UID', '20041120-8550-innerjoin-org');
$event1->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 3), array('VALUE' => 'DATE'));
$event1->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
$event1->setAttribute('SUMMARY', 'Escaped Comma in Description Field');
$event1->setAttribute('DESCRIPTION', 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?');
$event1->setAttribute('ORGANIZER', 'mailto:mueller@example.org', array('CN' => "Klä,rc\"hen;\n Mül:ler"));

$ical->addComponent($event1);

echo $ical->exportVCalendar();

$readIcal->parseVCalendar($ical->exportVCalendar());
$event1 = $readIcal->getComponent(0);
$attr = $event1->getAttribute('ORGANIZER', true);
echo $attr[0]['CN'];
?>
