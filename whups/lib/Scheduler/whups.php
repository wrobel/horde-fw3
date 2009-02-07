<?php

require_once 'Horde/Scheduler/cron.php';
require_once 'Horde/Variables.php';

/**
 * Horde_Scheduler_whups:: Send reminders for tickets based on the
 * reminders configuration file.
 *
 * $Horde: whups/lib/Scheduler/whups.php,v 1.12 2005/10/13 03:49:00 selsky Exp $
 *
 * @package Horde_Scheduler
 */
class Horde_Scheduler_whups extends Horde_Scheduler {

    var $_reminders;
    var $_runtime;
    var $_filestamp = 0;

    function Horde_Scheduler_whups($params = array())
    {
        parent::Horde_Scheduler($params);
    }

    function run()
    {
        $this->_runtime = time();

        // See if we need to include the reminders config file.
        if (filemtime(WHUPS_BASE . '/config/reminders.php') > $this->_filestamp) {
            $this->_filestamp = $this->_runtime;
            include WHUPS_BASE . '/config/reminders.php';
            $this->_reminders = $reminders;
        }

        foreach ($this->_reminders as $reminder) {
            $ds = &new Horde_Scheduler_cronDate($reminder['frequency']);
            if ($ds->scheduledAt($this->_runtime)) {
                if (!empty($reminder['server_name'])) {
                    $GLOBALS['conf']['server']['name'] = $reminder['server_name'];
                }
                $vars = &new Variables($reminder);
                Whups::sendReminders($vars);
            }
        }
    }

}
