<?php
/**
 * Whups external API interface.
 *
 * This file defines Whups' external API interface. Other applications
 * can interact with Whups through this API.
 *
 * $Horde: whups/lib/api.php,v 1.148.2.1 2009/06/19 17:09:56 jan Exp $
 *
 * @package Whups
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}hashHash'
);

$_services['show'] = array(
    'link' => '%application%/ticket/?id=|id|',
);

$_services['browse'] = array(
    'args' => array('path' => 'string'),
    'type' => '{urn:horde}hashHash',
);

$_services['list'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['add'] = array(
    'args' => array('name' => 'string'),
    'type' => 'int'
);

$_services['getAssignedTicketIds'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['getRequestedTicketIds'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['addTicket'] = array(
    // This is not the correct argument type, we need to create a custom
    // complex type.
    'args' => array('info' => '{urn:horde}stringArray'),
    'type' => 'int'
);

$_services['updateTicket'] = array(
    // This is not the correct argument type, we need to create a custom
    // complex type.
    'args' => array('info' => '{urn:horde}stringArray'),
    'type' => 'int'
);

$_services['addComment'] = array(
    'args' => array('ticket_id' => 'int',
                    'comment' => 'string',
                    'group' => 'string'),
    'type' => 'int',
);

$_services['addAttachment'] = array(
    'args' => array('ticket_id' => 'int',
                    'comment' => 'string',
                    'group' => 'string'),
    'type' => 'int',
);

$_services['setTicketAttributes'] = array(
    // This is not the correct argument type, we need to create a custom
    // complex type.
    'args' => array('info' => '{urn:horde}stringArray')
);

$_services['getListTypes'] = array(
    'args' => array(),
    // This is actually a boolHash
    'type' => '{urn:horde}stringArray'
);

$_services['listAs'] = array(
    'args' => array('type' => 'string'),
    'type' => '{urn:horde}hashHash'
);

$_services['listQueues'] = array(
    'args' => array(),
    'type' => '{urn:horde}hash'
);

$_services['getQueueDetails'] = array(
    'args' => array('queue' => 'int'),
    'type' => '{urn:horde}hash'
);

// We can't overload methods, so this is an alternate signature of this
// method.
// $_services['getQueueDetails'] = array(
//     'args' => array('queue' => '{urn:horde}stringArray'),
//     'type' => '{urn:horde}hashHash'
// );

$_services['listVersions'] = array(
    'args' => array('queue' => 'int'),
    'type' => '{urn:horde}hashHash'
);

$_services['addVersion'] = array(
    'args' => array('queue' => 'int', 'name' => 'string', 'description' => 'string'),
    'type' => 'int',
);

$_services['getVersionDetails'] = array(
    'args' => array('version_id' => 'int'),
    'type' => '{urn:horde}hash'
);

$_services['getTicketDetails'] = array(
    'args' => array('queue_id' => 'int', 'state' => 'string'),
    'type' => '{urn:horde}hash'
);

$_services['listCostObjects'] = array(
    'args' => array('criteria' => '{urn:horde}hash'),
    'type' => '{urn:horde}stringArray'
);

$_services['listTimeObjectCategories'] = array(
    'type' => '{urn:horde}stringArray'
);

$_services['listTimeObjects'] = array(
    'args' => array('start' => 'int', 'end' => 'int'),
    'type' => '{urn:horde}hashHash'
);

/**
 * Browse through Whups' object tree.
 *
 * @param string $path  The level of the tree to browse.
 *
 * @return array  The contents of $path
 */
function _whups_browse($path = '')
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver, $registry;

    if (substr($path, 0, 5) == 'whups') {
        $path = substr($path, 5);
    }
    $path = trim($path, '/');

    if (empty($path)) {
        $results = array(
            'whups/queue' => array(
                'name' => _("Queues"),
                'icon' => $registry->getImageDir() . '/whups.png',
                'browseable' => count(
                    Whups::permissionsFilter($whups_driver->getQueues(),
                                             'queue', PERMS_READ))));
    } else {
        $path = explode('/', $path);
        Horde::logMessage(var_export($path, true), __FILE__, __LINE__);
        $results = array();

        switch ($path[0]) {
        case 'queue':
            $queues = Whups::permissionsFilter($whups_driver->getQueues(),
                                               'queue', PERMS_SHOW);
            if (count($path) == 1) {
                foreach ($queues as $queue => $name) {
                    $results['whups/queue/' . $queue] = array(
                        'name' => $name,
                        'browseable' => true);
                }
            } else {
                if (!Whups::hasPermission($queues[$path[1]], 'queue',
                                          PERMS_READ)) {
                    return PEAR::raiseError('permission denied');
                }

                $tickets = $whups_driver->getTicketsByProperties(
                    array('queue' => $path[1]));
                foreach ($tickets as $ticket) {
                    $results['whups/queue/' . $path[1] . '/' . $ticket['id']] = array(
                        'name' => $ticket['summary'],
                        'browseable' => false);
                }
            }
            break;
        }
    }

    return $results;
}

/**
 * Get a list of queues that the current user has read permissions for.
 *
 * @return array Queue list
 */
function _whups_list()
{
    require_once dirname(__FILE__) . '/base.php';
    return Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(), 'queue', PERMS_READ);
}

/**
 * Create a new queue.
 *
 * @param string $name The queue's name.
 *
 * @return integer  The new queue id.
 */
function _whups_add($name)
{
    require_once dirname(__FILE__) . '/base.php';
    if (Auth::isAdmin('whups:admin')) {
        return $GLOBALS['whups_driver']->addQueue($name, '');
    } else {
        return PEAR::raiseError('You must be an administrator to perform this action.');
    }
}

/**
 * Return the ids of all open tickets assigned to the current user.
 *
 * @return array  Array of ticket ids.
 */
function _whups_getAssignedTicketIds()
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    $info = array('owner' => 'user:' . Auth::getAuth(),
                  'nores' => true);
    $tickets = $whups_driver->getTicketsByProperties($info);
    if (is_a($tickets, 'PEAR_Error')) {
        return $tickets;
    }
    $result = array();
    foreach ($tickets as $ticket) {
        $result[] = $ticket['id'];
    }
    return $result;
}

/**
 * Return the ids of all open tickets that the current user created.
 *
 * @return array  Array of ticket ids.
 */
function _whups_getRequestedTicketIds()
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    $info = array('requester' => Auth::getAuth(),
                  'nores' => true);
    $tickets = $whups_driver->getTicketsByProperties($info);
    if (is_a($tickets, 'PEAR_Error')) {
        return $tickets;
    }
    $result = array();
    foreach ($tickets as $ticket) {
        $result[] = (int) $ticket['id'];
    }
    return $result;
}

/**
 * Create a new ticket.
 *
 * @param array $ticket_info An array of form variables representing all of the
 * data collected by CreateStep1Form, CreateStep2Form, CreateStep3Form, and
 * optionally CreateStep4Form.
 *
 * @return integer The new ticket id.
 */
function _whups_addTicket($ticket_info)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once dirname(__FILE__) . '/Forms/CreateTicket.php';
    require_once dirname(__FILE__) . '/Ticket.php';
    require_once 'Horde/Variables.php';
    global $whups_driver;

    if (!is_array($ticket_info)) {
        return PEAR::raiseError('Invalid arguments');
    }

    $vars = new Variables($ticket_info);

    $form1 = &new CreateStep1Form($vars);
    $form2 = &new CreateStep2Form($vars);
    $form3 = &new CreateStep3Form($vars);

    // FIXME: This is an almighty hack, but we can't have form
    // tokens in rpc calls.
    $form1->useToken(false);
    $form2->useToken(false);
    $form3->useToken(false);

    // Complain if we've been given bad parameters.
    if (!$form1->validate($vars, true)) {
        $f1 = var_export($form1->_errors, true);
        return PEAR::raiseError("Invalid arguments ($f1)");
    }
    if (!$form2->validate($vars, true)) {
        $f2 = var_export($form2->_errors, true);
        return PEAR::raiseError("Invalid arguments ($f2)");
    }
    if (!$form3->validate($vars, true)) {
        $f3 = var_export($form3->_errors, true);
        return PEAR::raiseError("Invalid arguments ($f3)");
    }

    $form1->getInfo($vars, $info);
    $form2->getInfo($vars, $info);
    $form3->getInfo($vars, $info);

    // More checks if we're assigning the ticket at create-time.
    if (Auth::getAuth() && $whups_driver->isCategory('assigned', $vars->get('state'))) {
        $form4 = &new CreateStep4Form($vars);
        $form4->useToken(false);
        if (!$form4->validate($vars, true)) {
            return PEAR::raiseError('Invalid arguments (' . var_export($form4->_errors, true) . ')');
        }

        $form4->getInfo($vars, $info);
    }

    $ticket = Whups_Ticket::newTicket($info, Auth::getAuth());
    if (is_a($ticket, 'PEAR_Error')) {
        return $ticket;
    } else {
        return $ticket->getId();
    }
}

/**
 * Update a ticket's properties.
 *
 * @param integer $ticket_id    The id of the id to changes.
 * @param array   $ticket_info  The attributes to set, from the EditTicketForm.
 *
 * @return boolean  True
 */
function _whups_updateTicket($ticket_id, $ticket_info)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once dirname(__FILE__) . '/Ticket.php';
    require_once dirname(__FILE__) . '/Forms/EditTicket.php';
    require_once 'Horde/Variables.php';
    global $whups_driver;

    // Cast as an int for safety.
    $ticket = Whups_Ticket::makeTicket((int)$ticket_id);
    if (is_a($ticket, 'PEAR_Error')) {
        // The ticket is either invalid or we don't have permission to
        // read it.
        return $ticket;
    }

    // Check that we have permission to update the ticket
    if (!Auth::getAuth() ||
        !Whups::hasPermission($ticket->get('queue'), 'queue', 'update')) {
        return PEAR::raiseError(_('You do not have permission to update this ticket.'));
    }

    // Populate $vars with existing ticket details.
    $vars = new Variables();
    $ticket->setDetails($vars);

    // Copy new ticket details in.
    foreach ($ticket_info as $detail => $newval) {
        $vars->set($detail, $newval);
    }

    // Create and populate the EditTicketForm for validation. API calls can't
    // use form tokens and aren't the result of the EditTicketForm being
    // submitted.
    $editform = new EditTicketForm($vars, null, $ticket);
    $editform->useToken(false);
    $editform->setSubmitted(true);

    // Attempt to validate and update the ticket.
    if (!$editform->validate($vars)) {
         $form_errors = var_export($editform->_errors, true);
         return PEAR::raiseError(sprintf(_("Invalid ticket data supplied: %s"), $form_errors));
    }

    $editform->getInfo($vars, $info);

    $ticket->change('summary', $info['summary']);
    $ticket->change('state', $info['state']);
    $ticket->change('priority', $info['priority']);
    if (!empty($info['newcomment'])) {
        $ticket->change('comment', $info['newcomment']);
    }

    // Update attributes.
    $whups_driver->setAttributes($info);

    // Add attachment if one was uploaded.
    if (!empty($info['newattachment']['name'])) {
        $ticket->change('attachment',
                        array('name' => $info['newattachment']['name'],
                              'tmp_name' => $info['newattachment']['tmp_name']));
    }

    // If there was a new comment and permissions were specified on
    // it, set them.
    if (!empty($info['group'])) {
        $ticket->change('comment-perms', $info['group']);
    }

    $result = $ticket->commit();
    if (is_a($result, 'PEAR_Error')) {
        return $result;
    }

    // Ticket updated successfully
    return true;
}

/**
 * Add a comment to a ticket.
 *
 * @param integer $ticket_id  The id of the ticket to comment on.
 * @param string  $comment    The comment text to add.
 * @param string  $group      (optional) Restrict this comment to a specific group.
 *
 * @return boolean  True
 */
function _whups_addComment($ticket_id, $comment, $group = null)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once WHUPS_BASE . '/lib/Ticket.php';

    $ticket_id = (int)$ticket_id;
    if (empty($ticket_id)) {
        return PEAR::raiseError('Invalid ticket id');
    }

    $ticket = Whups_Ticket::makeTicket($ticket_id);
    if (is_a($ticket, 'PEAR_Error')) {
        return $ticket;
    }

    if (empty($comment)) {
        return PEAR::raiseError('Empty comments are not allowed');
    }

    // Add comment.
    $ticket->change('comment', $comment);

    // Add comment permissions, if specified.
    // @TODO: validate the user is allowed to specify this group
    if (!empty($group)) {
        $ticket->change('comment-perms', $group);
    }

    $result = $ticket->commit();
    if (is_a($result, 'PEAR_Error')) {
        return $result;
    }

    return true;
}

/**
 * Adds an attachment to a ticket.
 *
 * @param integer $ticket_id  The ticket number.
 * @param string $name        The name of the attachment.
 * @param string $data        The attachment data.
 *
 * @return mixed  True on success or PEAR_Error on failure.
 */
function _whups_addAttachment($ticket_id, $name, $data)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once WHUPS_BASE . '/lib/Ticket.php';

    $ticket_id = (int)$ticket_id;
    if (empty($ticket_id)) {
        return PEAR::raiseError(_("Invalid Ticket Id"));
    }

    $ticket = Whups_Ticket::makeTicket($ticket_id);
    if (is_a($ticket, 'PEAR_Error')) {
        return $ticket;
    }

    if (!strlen($name) || !strlen($data)) {
        return PEAR::raiseError(_("Empty attachment"));
    }

    $tmp_name = Util::getTempFile('whups', true, $GLOBALS['conf']['tmpdir']);
    $fp = fopen($tmp_name, 'wb');
    fwrite($fp, $data);
    fclose($fp);

    $ticket->change('attachment',
                    array('name' => $name, 'tmp_name' => $tmp_name));
    $result = $ticket->commit();
    if (is_a($result, 'PEAR_Error')) {
        return $result;
    }

    return true;
}

/**
 * Set attributes for a ticket
 *
 * @TODO fold this into the updateTicket method
 */
function _whups_setTicketAttributes($info)
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    if (!isset($info['ticket_id']) || !isset($info['attributes'])) {
        return PEAR::raiseError(_("Invalid arguments: Must supply a ticket number and new attributes."));
    }

    $ticket = $whups_driver->getTicketDetails($info['ticket_id']);
    if (is_a($ticket, "PEAR_Error")) {
        // Either the ticket doesn't exist or the caller didn't have
        // permission.
        return $ticket;
    }

    // Convert the RPC parameters into what we'd expect if we were
    // posting the EditAttributes form.
    $ainfo = array();
    foreach ($info['attributes'] as $attrib) {
        if (!isset($attrib['id']) || !isset($attrib['value'])) {
            return PEAR::raiseError(_("Invalid argument: Missing attribute name or value."));
        }

        $ainfo['a' . $attrib['id']] = $attrib['value'];
    }

    $ainfo['id'] = $info['ticket_id'];

    return $whups_driver->setAttributes($ainfo);
}

/**
 * Get the types that Whups items can be listed as.
 *
 * @return array  Array of list types.
 */
function _whups_getListTypes()
{
    return array('taskHash' => true);
}

/**
 * Get a list of items from whups as type $type.
 *
 * @param string $type  The list type to use (@see getListTypes). Currently supported: 'taskHash'
 *
 * @return array  An array of tickets.
 */
function _whups_listAs($type)
{
    switch ($type) {
    case 'taskHash':
        require_once dirname(__FILE__) . '/base.php';
        global $whups_driver;
        $info = array('owner' => 'user:' . Auth::getAuth(),
                      'nores' => true);
        $tickets = $whups_driver->getTicketsByProperties($info);
        if (is_a($tickets, 'PEAR_Error')) {
            return $tickets;
        }
        $result = array();
        foreach ($tickets as $ticket) {
            $view_link = Whups::urlFor('ticket', $ticket['id'], true);
            $delete_link = Whups::urlFor('ticket_action', array('delete', $ticket['id']), true);
            $complete_link = Whups::urlFor('ticket_action', array('update', $ticket['id']), true);

            $result['whups/' . $ticket['id']] = array(
                'task_id'           => $ticket['id'],
                'priority'          => $ticket['priority_name'],
                'tasklist_id'       => '**EXTERNAL**',
                'completed'         => ($ticket['state_category'] == 'resolved'),
                'name'              => '[#' . $ticket['id'] . '] ' . $ticket['summary'],
                'desc'              => null,
                'due'               => null,
                'category'          => null,
                'view_link'         => $view_link,
                'delete_link'       => $delete_link,
                'edit_link'         => $view_link,
                'complete_link'     => $complete_link
                );
        }
        break;

    default:
        $result = array();
        break;
    }

    return $result;
}

/**
 * Return a list of queues that the current user has read permissions for
 *
 * @return array  Array of queue details
 */
function _whups_listQueues()
{
    require_once dirname(__FILE__) . '/base.php';
    return Whups::permissionsFilter($GLOBALS['whups_driver']->getQueuesInternal(), 'queue', PERMS_SHOW);
}

/**
 * Get details for a queue
 *
 * @param array | integer $queue  Either an array of queue ids or a single queue id.
 *
 * @return array  An array of queue information (or an array of arrays, if multiple queues were passed).
 */
function _whups_getQueueDetails($queue)
{
    require_once dirname(__FILE__) . '/base.php';
    if (is_array($queue)) {
        $queues = Whups::permissionsFilter($queue, 'queue_id');
        $details = array();
        foreach ($queues as $id) {
            $details[$id] = $GLOBALS['whups_driver']->getQueueInternal($id);
        }
        return $details;
    }

    $queues = Whups::permissionsFilter(array($queue), 'queue_id');
    if ($queues) {
        return $GLOBALS['whups_driver']->getQueueInternal($queue);
    }

    return array();
}

/**
 * List the versions associated with a queue
 *
 * @param integer $queue  The queue id to get versions for.
 *
 * @return array  Array of queue versions
 */
function _whups_listVersions($queue)
{
    require_once dirname(__FILE__) . '/base.php';

    $queues = Whups::permissionsFilter(array($queue), 'queue_id');
    if (!$queues) {
        return array();
    }

    $versions = array();
    $version_list = $GLOBALS['whups_driver']->getVersionInfoInternal($queue);
    foreach ($version_list as $version) {
        $versions[] = array('id' => $version['version_id'],
                            'name' => $version['version_name'],
                            'description' => $version['version_description'],
                            'readonly' => false);
    }

    usort($versions, create_function('$a, $b', 'return version_compare($b[\'name\'], $a[\'name\']);'));
    return $versions;
}

/**
 * Add a version to a queue
 *
 * @param integer $queue  The queue id
 * @param string $name    The version name
 * @param string $description  The version description
 */
function _whups_addVersion($queue, $name, $description)
{
    require_once dirname(__FILE__) . '/base.php';
    return $GLOBALS['whups_driver']->addVersion($queue, $name, $description);
}

/**
 * Return the details for a queue version
 *
 * @param integer $version_id  The version to fetch
 *
 * @return array  Array of version details
 */
function _whups_getVersionDetails($version_id)
{
    require_once dirname(__FILE__) . '/base.php';
    return $GLOBALS['whups_driver']->getVersionInternal($version_id);
}

/**
 * Get the all tickets for a queue, optionally with a specific state.
 *
 * @param integer $queue_id  The queue to get tickets for
 * @param string  $state     The state filter, if any.
 *
 * @return array  Array of tickets
 */
function _whups_getTicketDetails($queue_id, $state = null)
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    $info['queue_id'] = $queue_id;
    if (!empty($state)) {
        $info['category'] = $state;
    }
    $tickets = $whups_driver->getTicketsByProperties($info);

    for ($i = 0; $i < count($tickets); $i++) {
        $view_link = Whups::urlFor('ticket', $tickets[$i]['id'], true);
        $delete_link = Whups::urlFor('ticket_action', array('delete', $tickets[$i]['id']), true);
        $complete_link = Whups::urlFor('ticket_action', array('update', $tickets[$i]['id']), true);

        $tickets[$i] = array(
                'ticket_id'         => $tickets[$i]['id'],
                'completed'         => ($tickets[$i]['state_category'] == 'resolved'),
                'assigned'          => ($tickets[$i]['state_category'] == 'assigned'),
                'name'              => $tickets[$i]['queue_name'] . ' #' .
                                       $tickets[$i]['id'] . ' - ' . $tickets[$i]['summary'],
                'state'             => $tickets[$i]['state_name'],
                'type'              => $tickets[$i]['type_name'],
                'priority'          => $tickets[$i]['priority_name'],
                'desc'              => null,
                'due'               => null,
                'category'          => null,
                'view_link'         => $view_link,
                'delete_link'       => $delete_link,
                'edit_link'         => $view_link,
                'complete_link'     => $complete_link
                );
    }

    return $tickets;
}

/**
 * Permissions available from Whups
 *
 * @return array  Permissions tree
 */
function _whups_perms()
{
    static $perms = array();
    if (!empty($perms)) {
        return $perms;
    }

    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    /* Available Whups permissions. */
    $perms['tree']['whups']['admin'] = false;
    $perms['title']['whups:admin'] = _("Administration");

    $perms['tree']['whups']['hiddenComments'] = false;
    $perms['title']['whups:hiddenComments'] = _("Hidden Comments");

    $perms['tree']['whups']['queues'] = array();
    $perms['title']['whups:queues'] = _("Queues");

    /* Loop through queues and add their titles. */
    $queues = $whups_driver->getQueues();
    foreach ($queues as $id => $name) {
        $perms['tree']['whups']['queues'][$id] = false;
        $perms['title']['whups:queues:' . $id] = $name;

        $perms['tree']['whups']['queues'][$id]['update'] = false;
        $perms['title']['whups:queues:' . $id . ':update'] = _("Update");
        $perms['type']['whups:queues:' . $id . ':update'] = 'boolean';
        $perms['params']['whups:queues:' . $id . ':update'] = array();

        $perms['tree']['whups']['queues'][$id]['assign'] = false;
        $perms['title']['whups:queues:' . $id . ':assign'] = _("Assign");
        $perms['type']['whups:queues:' . $id . ':assign'] = 'boolean';
        $perms['params']['whups:queues:' . $id . ':assign'] = array();

        $perms['tree']['whups']['queues'][$id]['requester'] = false;
        $perms['title']['whups:queues:' . $id . ':requester'] = _("Set Requester");
        $perms['type']['whups:queues:' . $id . ':requester'] = 'boolean';
        $perms['params']['whups:queues:' . $id . ':requester'] = array();
    }

    $perms['tree']['whups']['replies'] = array();
    $perms['title']['whups:replies'] = _("Form Replies");

    /* Loop through type and replies and add their titles. */
    foreach ($whups_driver->getAllTypes() as $type_id => $type_name) {
        foreach ($whups_driver->getReplies($type_id) as $reply_id => $reply) {
            $perms['tree']['whups']['replies'][$reply_id] = false;
            $perms['title']['whups:replies:' . $reply_id] = $type_name . ': ' . $reply['reply_name'];
        }
    }

    return $perms;
}

/**
 * List cost objects
 *
 * @param array $criteria  The list criteria
 *
 * @return array  Tickets (as cost objects) matching $criteria
 */
function _whups_listCostObjects($criteria)
{
    require_once dirname(__FILE__) . '/base.php';
    global $whups_driver;

    $info = array();
    if (!empty($criteria['user'])) {
        $info['owner'] = 'user:' . Auth::getAuth();
    }
    if (!empty($criteria['active'])) {
        $info['nores'] = true;
    }
    if (!empty($criteria['id'])) {
        $info['id'] = $criteria['id'];
    }

    $tickets = $whups_driver->getTicketsByProperties($info);
    if (is_a($tickets, 'PEAR_Error')) {
        return $tickets;
    }
    $result = array();
    foreach ($tickets as $ticket) {
        $result[$ticket['id']] = array('id'     => $ticket['id'],
                                       'active' => ($ticket['state_category'] != 'resolved'),
                                       'name'   => sprintf(_("Ticket %s - %s"),
                                                           $ticket['id'],
                                                           $ticket['summary']));

        /* If the user has an estimate attribute, use that for cost object
         * hour estimates. */
        $attributes = $whups_driver->getTicketAttributesWithNames($ticket['id']);
        if (!is_a($attributes, 'PEAR_Error')) {
            foreach ($attributes as $k => $v) {
                if (strtolower($k) == _("estimated time")) {
                    if (!empty($v)) {
                        $result[$ticket['id']]['estimate'] = (double) $v;
                    }
                }
            }
        }
    }
    ksort($result);
    if (count($result) == 0) {
        return array();
    } else {
        return array(array('category' => _("Tickets"),
                           'objects'  => array_values($result)));
    }
}

/**
 * List the ways that tickets can be treated as time objects
 *
 * @return array  Array of time object types
 */
function _whups_listTimeObjectCategories()
{
    return array('created' => _("My tickets by creation date"),
                 'assigned' => _("My tickets by assignment date"),
                 'due' => _("My tickets by due date"),
                 'resolved' => _("My tickets by resolution date"));
}

/**
 * Lists tickets with due dates as time objects.
 *
 * @param array $categories  The time categories (from listTimeObjectCategories) to list.
 * @param mixed $start       The start date of the period.
 * @param mixed $end         The end date of the period.
 */
function _whups_listTimeObjects($categories, $start, $end)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once WHUPS_BASE . '/lib/Ticket.php';
    global $whups_driver;

    $start = new Horde_Date($start);
    $start_ts = $start->timestamp();
    $end = new Horde_Date($end);
    $end_ts = $end->timestamp();

    $criteria['owner'] = Whups::getOwnerCriteria(Auth::getAuth());

    /* @TODO Use $categories */
    $category = 'due';
    switch ($category) {
    case 'assigned':
        $label = _("Assigned");
        $criteria['ass'] = true;
        break;

    case 'created':
        $label = _("Created");
        break;

    case 'due':
        $label = _("Due");
        $criteria['nores'] = true;
        break;

    case 'resolved':
        $label = _("Resolved");
        $criteria['res'] = true;
        break;
    }

    $tickets = $whups_driver->getTicketsByProperties($criteria);
    if (is_a($tickets, 'PEAR_Error')) {
        return array();
    }

    $objects = array();
    foreach ($tickets as $ticket) {
        switch ($category) {
        case 'assigned':
            $t_start = $ticket['date_assigned'];
            break;

        case 'created':
            $t_start = $ticket['timestamp'];
            break;

        case 'due':
            if (empty($ticket['due'])) {
                continue 2;
            }
            $t_start = $ticket['due'];
            break;

        case 'resolved':
            $t_start = $ticket['date_resolved'];
            break;
        }

        if ($t_start + 1 < $start_ts || $t_start > $end_ts) {
            continue;
        }
        $t = new Whups_Ticket($ticket['id'], $ticket);
        $objects[$ticket['id']] = array(
            'title' => sprintf('%s: [#%s] %s', $label, $ticket['id'], $ticket['summary']),
            'description' => $t->toString(),
            'id' => $ticket['id'],
            'start' => date('Y-m-d\TH:i:s', $t_start),
            'end' => date('Y-m-d\TH:i:s', $t_start + 1),
            'params' => array('id' => $ticket['id']));
    }

    return $objects;
}
