<?php

require dirname(__FILE__) . '/../Object.php';
require dirname(__FILE__) . '/../Driver.php';

$attributes = array(
  'name' => 'Jan Schneiderö',
  'namePrefix' => 'Mr.',
  'firstname' => 'Jan',
  'middlenames' => 'K.',
  'lastname' => 'Schneiderö',
  'email' => 'jan@horde.org',
  'alias' => 'yunosh',
  'homeAddress' => 'Schönestr. 15
33604 Bielefeld',
  'workStreet' => 'Hübschestr. 19',
  'workCity' => 'Köln',
  'workProvince' => 'Allgäu',
  'workPostalcode' => '33602',
  'workCountry' => 'Dänemark',
  'homePhone' => '+49 521 555123',
  'workPhone' => '+49 521 555456',
  'cellPhone' => '+49 177 555123',
  'fax' => '+49 521 555789',
  'pager' => '+49 123 555789',
  'birthday' => '1971-10-01',
  'title' => 'Senior Developer (äöü)',
  'role' => 'Developer (äöü)',
  'company' => 'Horde Project',
  'department' => 'äöü',
  'notes' => 'A German guy (äöü)',
  'website' => 'http://janschneider.de',
  'timezone' => 'Europe/Berlin',
  'latitude' => '52.516276',
  'longitude' => '13.377778',
  'photo' => file_get_contents(dirname(__FILE__) . '/az.png'),
  'phototype' => 'image/png',
);

$driver = new Turba_Driver(array());
$object = new Turba_Object($driver, $attributes);
$vcard = $driver->tovCard($object, '2.1');
echo $vcard->exportvCalendar() . "\n";
$vcard = $driver->tovCard($object, '3.0');
echo $vcard->exportvCalendar();

?>
