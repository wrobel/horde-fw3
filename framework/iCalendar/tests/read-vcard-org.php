<?php

$data = 'BEGIN:VCARD
VERSION:3.0
FN:Test User
ORG:My Organization;My Unit
END:VCARD';

require_once dirname(__FILE__) . '/../iCalendar.php';
$ical = new Horde_iCalendar();
$ical->parseVCalendar($data);
$card = $ical->getComponent(0);
var_dump($card->getAttributeValues('ORG'));

?>
