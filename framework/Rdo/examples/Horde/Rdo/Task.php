<?php
/**
 * $Horde: framework/Rdo/examples/Horde/Rdo/Task.php,v 1.1.2.1 2008-05-15 23:23:14 chuck Exp $
 *
 * @package Horde_Rdo
 */

@include './conf.php';
if (empty($conf['sql'])) {
    die('No sql configuration found.');
}

require_once 'Horde/Loader.php';

/**
 */
class Task extends Horde_Rdo_Base {
}

/**
 */
class TaskMapper extends Horde_Rdo_Mapper {

    protected $_table = 'nag_tasks';

    public function getAdapter()
    {
        return Horde_Rdo_Adapter::factory('pdo', $GLOBALS['conf']['sql']);
    }

}

$tm = new TaskMapper();

// Count all tasks.
$count = $tm->count();
echo "# tasks: $count\n";

// List all tasks.
echo "Looking for all tasks:\n";
foreach ($tm->find(Horde_Rdo::FIND_ALL) as $task) {
    echo "  " . $task->task_name . "\n";
}

// List all of Chuck's tasks.
$chuck = $tm->find(Horde_Rdo::FIND_ALL, array('task_owner' => 'chuck'));
echo "\nChuck's tasks:\n";
foreach ($chuck as $task) {
    echo "  " . $task->task_name . "\n";
}
