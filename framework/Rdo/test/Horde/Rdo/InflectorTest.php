<?php
/**
 * @package    Horde_Rdo
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/Rdo/Inflector.php';

class Horde_Rdo_InflectorTest extends PHPUnit_Framework_TestCase {

    public $words = array(
        'sheep' => 'sheep',
        'man' => 'men',
        'woman' => 'women',
        'user' => 'users',
        'foot' => 'feet',
        'hive' => 'hives',
        'chive' => 'chives',
        'event' => 'events',
        'task' => 'tasks',
        'preference' => 'preferences',
        'child' => 'children',
        'moose' => 'moose',
        'mouse' => 'mice',
    );

    public function setUp()
    {
        $this->inflector = new Horde_Rdo_Inflector;
    }

    public function testInflection()
    {
        foreach ($this->words as $singular => $plural) {
            $this->assertEquals($plural, $this->inflector->pluralize($singular));
            $this->assertEquals($singular, $this->inflector->singularize($plural));
        }
    }

}
