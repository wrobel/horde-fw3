<?php

require_once dirname(__FILE__) . '/../iCalendar.php';

$ical = new Horde_iCalendar();
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_iCalendar('1.0');
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_iCalendar();
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_iCalendar('1.0');
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.', array('CHARSET' => 'UTF-8'));
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_iCalendar('1.0');
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet. Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet.', array('CHARSET' => 'UTF-8'));
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_iCalendar();
$event = Horde_iCalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$description = <<<EOT
SYLVIE DAGORNE a écrit :

Bonjour,

suite à mon appel téléphonique auprès de Jacques Benzerara, il m'a renvoyé vers vous. En effet, je souhaiterais vous rencontrer car:
1°) au niveau de l'observatoire local nous devons lancer une enquête sur un suivi de cohorte à la rentrée prochaine qui concernera tous les étudiants de L1. Nous souhaiterons faire un questionnaire en ligne ce questionnaire devra être hébergé sur un serveur.

2°) dans le cadre de l'observatoire régional, nos partenaires nous demande également de faire des questionnaires en ligne. Nous disposons du logiciel Modalisa qui permet de le réaliser mais du point de vu technique, nous avons besoin de voir avec vous,  les difficultés et les limites d'un tel dispositif afin de voir les démarches à suivre et pouvoir évoquer tous ces problèmes techniques, je souhaiterais vous rencontrer. Merci de me précisez vos disponibilités?
...
Je serai accompagné d'un collègue pour l'observatoire local (David Le foll) et de la chargée d'études de l'observatoire régional (Amélie Gicquel) pour la partie régionale.
EOT;
$event->setAttribute('DESCRIPTION', $description);
$ical->addComponent($event);
echo $ical->exportVCalendar();

?>
