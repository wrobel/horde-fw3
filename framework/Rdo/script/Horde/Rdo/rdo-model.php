#!@php_bin@
<?php
/**
 * Script to automatically create Model classes for Rdo.
 *
 * $Horde: framework/Rdo/script/Horde/Rdo/rdo-model.php,v 1.1.2.2 2009-01-06 15:23:33 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Rdo
 */

require_once 'Horde/Loader.php';
@include dirname(__FILE__) . '/rdo-model-conf.php';

$p = new Horde_Argv_Parser(array('optionList' => array(
    new Horde_Argv_Option('-t', '--table', array('help' => 'Database table to generate a static Model for')),
)));
list($values, $args) = $p->parseArgs();


if (!$values->table) {
    $p->printHelp();
    exit;
}
$table = $values->table;

/**
 * This class will stand in for our table for generating the Model.
 */
class Proxy extends Horde_Rdo_Base {
}

/**
 * This class will stand in for our table's Mapper.
 */
class ProxyMapper extends Horde_Rdo_Mapper {

    public function __construct($table)
    {
        $this->_table = $table;
    }

    public function getAdapter()
    {
        return Horde_Rdo_Adapter::factory('pdo', $GLOBALS['conf']);
    }

}

$mapper = new ProxyMapper($table);
$model = var_export($mapper->model, true);

// Start massaging from eval-able code into a saveable class.
$model = str_replace('Horde_Rdo_Model::__set_state(array(', '', $model);
$model = substr($model, 0, -4) . ';';
$model = str_replace('NULL', 'null', $model);
$model = str_replace('\'_fields\' =>', 'var $_fields =', $model);
$model = preg_replace('/,(\s+)\'table\' =>/m', ';$1var \$table =', $model);
$model = preg_replace('/,(\s+)\'key\' =>/m', ';$1var \$key =', $model);
$model = "<?php\nclass " . ucwords($table) . "_Model extends Horde_Rdo_Model {" . $model . "\n}\n";
echo $model;
