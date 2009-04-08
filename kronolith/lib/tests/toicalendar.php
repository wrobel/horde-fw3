<?php

require 'Horde/CLI.php';
Horde_CLI::init();
define('AUTH_HANDLER', true);
require dirname(__FILE__) . '/../base.php';
require 'Horde/iCalendar.php';

$driver = new Kronolith_Driver();
$object = new Kronolith_Event($driver);
$object->start = new Horde_Date('2007-03-15 13:10:20');
$object->end = new Horde_Date('2007-03-15 14:20:00');
$object->setCreatorId('joe');
$object->setUID('20070315143732.4wlenqz3edq8@horde.org');
$object->setTitle('Hübscher Termin');
$object->setDescription("Schöne Bescherung\nNew line");
$object->setCategory('Schöngeistiges');
$object->setLocation('Allgäu');
$object->setAlarm(10);
$object->recurrence = new Horde_Date_Recurrence($object->start);
$object->recurrence->setRecurType(HORDE_DATE_RECUR_DAILY);
$object->recurrence->setRecurInterval(2);
$object->recurrence->addException(2007, 3, 19);
$object->initialized = true;

$ical = new Horde_iCalendar('1.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$ical = new Horde_iCalendar('2.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$object->setPrivate(true);
$object->setStatus(KRONOLITH_STATUS_TENTATIVE);
$object->recurrence = new Horde_Date_Recurrence($object->start);
$object->recurrence->setRecurType(HORDE_DATE_RECUR_MONTHLY_DATE);
$object->recurrence->setRecurInterval(1);
$object->recurrence->addException(2007, 4, 15);
$object->setAttendees(
    array('juergen@example.com' =>
          array('attendance' => KRONOLITH_PART_REQUIRED,
                'response' => KRONOLITH_RESPONSE_NONE,
                'name' => 'Jürgen Doe'),
          0 =>
          array('attendance' => KRONOLITH_PART_OPTIONAL,
                'response' => KRONOLITH_RESPONSE_ACCEPTED,
                'name' => 'Jane Doe'),
          'jack@example.com' =>
          array('attendance' => KRONOLITH_PART_NONE,
                'response' => KRONOLITH_RESPONSE_DECLINED,
                'name' => 'Jack Doe'),
          'jenny@example.com' =>
          array('attendance' => KRONOLITH_PART_NONE,
                'response' => KRONOLITH_RESPONSE_TENTATIVE)));

$ical = new Horde_iCalendar('1.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$ical = new Horde_iCalendar('2.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

?>
