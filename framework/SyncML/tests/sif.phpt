--TEST--
SIF tests.
--FILE--
<?php

// Setup stubs.
class BackendStub {
    function logMessage() {}
}
$backend = new BackendStub();
define('PEAR_LOG_DEBUG', null);

// Load device handler.
require_once dirname(__FILE__) . '/../SyncML/Device.php';
$device = SyncML_Device::factory('Sync4j');

$data = <<<EVENT
BEGIN:VCALENDAR
VERSION:2.0
X-WR-CALNAME:cdillon's Calendar
PRODID:-//The Horde Project//Horde_iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20080630T110000Z
DTEND:20080630T120000Z
DTSTAMP:20080630T201939Z
UID:20080630151854.190949aaovgixvhq@www.wolves.k12.mo.us
CREATED:20080630T201854Z
LAST-MODIFIED:20080630T201854Z
SUMMARY:Server02
ORGANIZER;CN=Chris Dillon:mailto:cdillon@wolves.k12.mo.us
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:OPAQUE
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="Dillon,
  Chris":mailto:cdillon@wolves.k12.mo.us
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
EVENT;

echo $device->vevent2sif($data);
echo "\n\n";

$data = <<<CONTACT
<?xml version="1.0" encoding="UTF-8"?>
<contact>
<Anniversary/>
<AssistantName/>
<AssistantTelephoneNumber/>
<BillingInformation/>
<Birthday>2008-10-18</Birthday>
<Body>Comments
More comments
And just a couple more</Body>
<Business2TelephoneNumber/>
<BusinessAddressCity>Golden Hills</BusinessAddressCity>
<BusinessAddressCountry>Australia</BusinessAddressCountry>
<BusinessAddressPostOfficeBox/>
<BusinessAddressPostalCode>4009</BusinessAddressPostalCode>
<BusinessAddressState>Qld</BusinessAddressState>
<BusinessAddressStreet>Company
Unit 2, 123 St Freds Tce</BusinessAddressStreet>
<BusinessFaxNumber/>
<BusinessTelephoneNumber>+61 712341234</BusinessTelephoneNumber>
<CallbackTelephoneNumber/>
<CarTelephoneNumber/>
<Categories/>
<Children/>
<Companies/>
<CompanyMainTelephoneNumber/>
<CompanyName>Company</CompanyName>
<ComputerNetworkName/>
<Department/>
<Email1Address>test@domain.com</Email1Address>
<Email1AddressType>SMTP</Email1AddressType>
<Email2Address>user@seconddomain.com</Email2Address>
<Email2AddressType>SMTP</Email2AddressType>
<Email3Address/>
<Email3AddressType/>
<FileAs>Lastname, Firstname</FileAs>
<FirstName>Firstname</FirstName>
<Folder>DEFAULT_FOLDER</Folder>
<Gender>0</Gender>
<Hobby/>
<Home2TelephoneNumber/>
<HomeAddressCity/>
<HomeAddressCountry/>
<HomeAddressPostOfficeBox/>
<HomeAddressPostalCode/>
<HomeAddressState/>
<HomeAddressStreet/>
<HomeFaxNumber/>
<HomeTelephoneNumber/>
<HomeWebPage/>
<IMAddress/>
<Importance>1</Importance>
<Initials>F.L.</Initials>
<JobTitle/>
<Language/>
<LastName>Lastname</LastName>
<MailingAddress>Company
Unit 2, 123 St Freds Tce
Golden Hills  Qld  4009
Australia</MailingAddress>
<ManagerName/>
<MiddleName/>
<Mileage/>
<MobileTelephoneNumber>+61 123123123</MobileTelephoneNumber>
<NickName/>
<OfficeLocation/>
<OrganizationalIDNumber/>
<OtherAddressCity/>
<OtherAddressCountry/>
<OtherAddressPostOfficeBox/>
<OtherAddressPostalCode/>
<OtherAddressState/>
<OtherAddressStreet/>
<OtherFaxNumber/>
<OtherTelephoneNumber/>
<PagerNumber/>
<Photo/>
<PrimaryTelephoneNumber/>
<Profession/>
<RadioTelephoneNumber/>
<Sensitivity>0</Sensitivity>
<Spouse/>
<Subject>Firstname Lastname</Subject>
<Suffix/>
<TelexNumber/>
<Title/>
<WebPage/>
<YomiCompanyName/>
<YomiFirstName/>
<YomiLastName/>
</contact>
CONTACT;

echo $device->sif2vcard($data);

?>
--EXPECT--
<?xml version="1.0"?><appointment><ReminderSet>1</ReminderSet><IsRecurring>0</IsRecurring><BusyStatus>2</BusyStatus><AllDayEvent>0</AllDayEvent><Start>20080630T110000Z</Start><End>20080630T120000Z</End><Subject>Server02</Subject><Sensitivity>0</Sensitivity><ReminderMinutesBeforeStart>15</ReminderMinutesBeforeStart><Duration>60</Duration></appointment>

BEGIN:VCARD
VERSION:3.0
FN:Lastname\, Firstname
TEL;TYPE=WORK:+61 712341234
TEL;TYPE=CELL:+61 123123123
EMAIL:test@domain.com
EMAIL:user@seconddomain.com
NOTE:Comments\nMore comments\nAnd just a couple more
BDAY:2008-10-18
N:Lastname;Firstname;;;
ADR;TYPE=WORK:;;Company\nUnit 2\, 123 St Freds Tce;Golden
  Hills;Qld;4009;Australia
ORG:Company
END:VCARD