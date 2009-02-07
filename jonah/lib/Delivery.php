<?php
/**
 * Jonah_Delivery Class
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * $Horde: jonah/lib/Delivery.php,v 1.33 2008/05/07 03:52:48 chuck Exp $
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Jonah
 */

/** DataTree */
require_once 'Horde/DataTree.php';

/**
 * @package Jonah
 */
class Jonah_Delivery {

    /**
     * A hash containing any parameters for the current action driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this action driver.
     */
    function Jonah_Delivery($params)
    {
        $this->_params = $params;
    }

    /**
     * Generate a new request id.
     *
     * @return string
     */
    function newRequestId()
    {
        /* Request id needs to be unique and short. Crude for now. */
        return dechex(time());
    }

    /**
     * Delivers the news. Hands off the actual delivery to the specific
     * driver class.
     *
     * @param integer $channel_id  The id of the channel for which to deliver
     *                             the news.
     */
    function deliver($channel_id = null)
    {
        global $news;

        if (is_null($channel_id)) {
            $channels = $news->getChannels();
        } else {
            $channels = array($news->getChannel($channel_id));
        }

        foreach ($channels as $channel) {
            $recipients = $this->getRecipients($channel['channel_id']);
            if (!$recipients || is_a($recipients, 'PEAR_Error')) {
                /* No recipients or could not fetch recipients for this
                 * channel. */
                continue;
            }
            $stories = $news->getStories($channel['channel_id']);
            $delivery_info = $this->_lists->getDeliveryInfo($channel['channel_id']);
            /* If no last delivery information set it to 0. */
            if (!isset($delivery_info['last_send'])) {
                $delivery_info['last_send'] = 0;
            }
            /* Filter out all the old stories and leave the stories array only
             * with new stories since last delivery. */
            foreach ($stories as $key => $story) {
                if ($story['timestamp'] < $delivery_info['last_send']) {
                    unset($stories[$key]);
                }
            }
            $sent = $this->_deliver($stories, $recipients);
            if (is_a($sent, 'PEAR_Error')) {
                return $sent;
            }
            $this->_lists->setDeliveryInfo($channel['channel_id'], 'last_send', time());
        }
    }

    function singleDeliver($story)
    {
        if (!$recipients = $this->getRecipients($story['channel_id'])) {
            return false;
        }

        $delivered = $this->_deliver(array($story), $recipients);
        if (is_a($delivered, 'PEAR_Error')) {
            return $delivered;
        }
        $this->_lists->setDeliveryInfo($story['channel_id'], 'last_send', time());
        return true;
    }

    /**
     * Returns a list of available action drivers.
     *
     * @return array  An array of available drivers.
     */
    function getDrivers()
    {
        static $drivers = array();
        if (!empty($drivers)) {
            return $drivers;
        }

        $driver_path = dirname(__FILE__) . '/Delivery/';
        $drivers = array();

        if ($driver_dir = opendir($driver_path)) {
            while (false !== ($file = readdir($driver_dir))) {
                /* Hide dot files and non .php files. */
                if (substr($file, 0, 1) != '.' && substr($file, -4) == '.php') {
                    $driver = substr($file, 0, -4);
                    $driver_info = Jonah_Delivery::getDeliveryInfo($driver);
                    $drivers[$driver] = $driver_info['name'];
                }
            }
            closedir($driver_dir);
        }

        return $drivers;
    }

    function getDeliveryInfo($driver)
    {
        static $info = array();
        if (isset($info[$driver])) {
            return $info[$driver];
        }

        require_once dirname(__FILE__) . '/Delivery/' . $driver . '.php';
        $class = 'Jonah_Delivery_' . $driver;
        $info[$driver] = call_user_func(array($class, 'getInfo'));

        return $info[$driver];
    }

    function getDeliveryParams($delivery)
    {
        static $params = array();
        if (isset($params[$delivery])) {
            return $params[$delivery];
        }

        require_once dirname(__FILE__) . '/Delivery/' . $delivery . '.php';
        $class = 'Jonah_Delivery_' . $delivery;
        $params[$delivery] = call_user_func(array($class, 'getParams'));

        return $params[$delivery];
    }

    /**
     * Deletes lists for a given channel. Can be called statically which would
     * set up a datatree object and delete all the relative entries.
     * If a drievr is passed it will delete the list only for that driver,
     * otherwise all lists are deleted.
     */
    function deleteChannelLists($channel_id, $driver = '')
    {
        /* Current object's datatree object, or create a new one? */
        if (is_a($this, 'Jonah_Delivery')) {
            $datatree = &$this->_lists->_datatree;
        } else {
            $datatree_driver = $GLOBALS['conf']['datatree']['driver'];
            $params = array_merge(Horde::getDriverConfig('datatree', $datatree_driver),
                                  array('group' =>'jonah.delivery'));

            require_once 'Horde/DataTree.php';
            $datatree = &DataTree::singleton($datatree_driver, $params);
        }

        /* Figure out what datatree_node we are deleting. */
        $datatree_node = 'channels:' . $channel_id;
        if (!empty($driver)) {
            $datatree_node .= ':' . $driver;
        }

        /* Delete the lists if they exist. */
        if ($datatree->exists($datatree_node)) {
            return $datatree->remove($datatree_node, true);
        }

        return true;
    }

    /**
     * Attempts to return a concrete Jonah_Delivery instance based on $driver.
     *
     * @param string $driver  The type of concrete Jonah_Delivery subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return object Jonah_Delivery  The newly created concrete Jonah_Delivery
     *                                instance, or false on error.
     */
    function &factory($driver, $params = array())
    {
        $driver = basename($driver);
        include_once dirname(__FILE__) . '/Delivery/' . $driver . '.php';
        $class = 'Jonah_Delivery_' . $driver;
        if (class_exists($class)) {
            $delivery = &new $class($params);
            return $delivery;
        } else {
            Horde::fatal(PEAR::raiseError(sprintf(_("No such action \"%s\" found"), $driver)), __FILE__, __LINE__);
        }
    }

    /**
     * Attempts to return a reference to a concrete Jonah_Delivery instance
     * based on $driver.
     *
     * It will only create a new instance if no Jonah_Delivery instance with
     * the same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Jonah_Delivery::singleton()
     *
     * @param string $driver  The type of concrete Jonah_Delivery subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Jonah_Delivery instance, or false on
     *                error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Jonah_Delivery::factory($driver, $params);
        }

        return $instances[$signature];
    }

}

/**
 * Jonah_Delivery_Lists Class handles all the lists for delivery of news.
 */
class Jonah_Delivery_Lists extends DataTree {

    var $_datatree;
    var $_delivery;

    function Jonah_Delivery_Lists($delivery_params)
    {
        global $conf;

        $this->_delivery = $delivery_params['delivery'];

        if (empty($conf['datatree']['driver'])) {
            Horde::fatal(_("You must configure a Horde Datatree backend to use Jonah."), __FILE__, __LINE__);
        }
        $driver = $conf['datatree']['driver'];
        $params = array_merge(Horde::getDriverConfig('datatree', $driver),
                              array('group' =>'jonah.delivery'));

        require_once 'Horde/DataTree.php';
        $this->_datatree = &DataTree::singleton($driver, $params);
    }

    /**
     * Adds a request to the queue waiting for confirmation.
     *
     * @param array $request  An array with the information for the request to
     *                        be queued. The following key/value pairs are
     *                        required:
     *                          'channel_id' - the id of the channel
     *                          'action'     - 'join' or 'leave' request
     *                          'recipient'  - recipient's email address
     *                        and in case of a join action also:
     *                          'name'       - recipient's name
     *
     * @return int  $request_id  The id for the request.
     */
    function queue($request)
    {
        $list_name = 'requests:' . $this->_delivery;
        $request_id = Jonah_Delivery::newRequestId();
        if ($this->_datatree->exists($list_name)) {
            $list = $this->getList($list_name);
            $update = true;
        } else {
            $list = $this->newList($list_name);
            $update = false;
        }

        /* TODO: put in a search in the attributes of this queue to check for
         * the same request (recipient/channel_id) and return the same id to
         * avoid multiple submits and possibly multiple outgoing confirmation
         * requests. */

        $list->data[$request_id] = $request;
        $list->data[$request_id]['timestamp'] = time();

        if ($update) {
            $this->_datatree->updateData($list);
        } else {
            $this->_datatree->add($list);
        }

        return $request_id;
    }

    /**
     * Given a request id, return all the details for the request.
     */
    function getRequest($request_id)
    {
        $list_name = 'requests:' . $this->_delivery;
        $list = $this->getList($list_name);
        /* Check if the request is a valid existing one. */
        if (!isset($list->data[$request_id])) {
            return false;
        }

        return $list->data[$request_id];
    }

    /**
     * The given request id is removed from the lists as it was either
     * confirmed or expired.
     */
    function removeRequest($request_id)
    {
        $list_name = 'requests:' . $this->_delivery;
        $list = $this->getList($list_name);
        unset($list->data[$request_id]);
        $this->_datatree->updateData($list);
    }

    function join($join)
    {
        /* Cache the lists. Needed to improve performance when calling multiple
         * joins(), eg. when loading a list. */
        static $list = array();
        $list_name = 'channels:' . $join['channel_id'] . ':' . $this->_delivery;
        if (isset($list[$list_name])) {
            $update = true;
        } elseif ($this->_datatree->exists($list_name)) {
            $list[$list_name] = $this->getList($list_name);
            $update = true;
        } else {
            $list[$list_name] = $this->newList($list_name);
            $update = false;
        }

        /* Set up the list object's data array. */
        $list[$list_name]->data[$join['recipient']] = array('name' => $join['name']);

        if ($update) {
            $this->_datatree->updateData($list[$list_name]);
        } else {
            $this->_datatree->add($list[$list_name]);
        }

        return true;
    }

    function leave($leave)
    {
        $list_name = 'channels:' . $leave['channel_id'] . ':' . $this->_delivery;
        if (!$this->_datatree->exists($list_name)) {
            /* TODO: Error on bad list. */
            return false;
        }

        $list = $this->getList($list_name);

        /* Remove the recipient from the list object's data array. */
        if (isset($list->data[$leave['recipient']])) {
            unset($list->data[$leave['recipient']]);
        }
        return $this->_datatree->updateData($list);
    }

    /**
     * Returns the list of recipients for a channel.
     */
    function getRecipients($channel_id)
    {
        $list_name = 'channels:' . $channel_id . ':' . $this->_delivery;
        if (!$this->_datatree->exists($list_name)) {
            return false;
        }
        $recipients = $this->getList($list_name);
        unset($recipients->data['__info']);
        return $recipients->data;
    }

    function getDeliveryInfo($channel_id)
    {
        $list_name = 'channels:' . $channel_id . ':' . $this->_delivery;
        if (!$this->_datatree->exists($list_name)) {
            return false;
        }
        $list = $this->getList($list_name);
        if (isset($list->data['__info'])) {
            return $list->data['__info'];
        } else {
            return array();
        }
    }

    function setDeliveryInfo($channel_id, $key, $value)
    {
        $list_name = 'channels:' . $channel_id . ':' . $this->_delivery;
        if (!$this->_datatree->exists($list_name)) {
            return false;
        }
        $list = $this->getList($list_name);
        $list->data['__info'][$key] = $value;
        return $this->_datatree->updateData($list);
    }

    function &getList($list_name)
    {
        $list = &$this->_datatree->getObject($list_name, 'Jonah_Delivery_Lists_DataTreeObject');
        if (is_a($list, 'PEAR_Error')) {
            return $list;
        }
        $list->setListsOb($this);
        return $list;
    }

    function &newList($list_name)
    {
        if (empty($list_name)) {
            return PEAR::raiseError('List names must be non-empty');
        }
        $list = &new Jonah_Delivery_Lists_DataTreeObject($list_name);
        $list->setListsOb($this);
        return $list;
    }

    function &singleton($delivery_params)
    {
        static $_lists;

        if (!isset($_lists)) {
            $_lists = &new Jonah_Delivery_Lists($delivery_params);
        }

        return $_lists;
    }

}

class Jonah_Delivery_Lists_DataTreeObject extends DataTreeObject {

    var $_listsOb;

    function Jonah_Delivery_Lists_DataTreeObject($name)
    {
        parent::DataTreeObject($name);
    }

    function setListsOb(&$_listsOb)
    {
        $this->_listsOb = &$_listsOb;
    }

    function _toAttributes()
    {
        $attributes = array();

        foreach ($this->data as $index => $entry) {
            foreach ($entry as $key => $value) {
                $attributes[] = array('name' => (string)$index,
                                      'key' => (string)$key,
                                      'value' => (string)$value);
            }
        }

        return $attributes;
    }

    function _fromAttributes($attributes)
    {
        $this->data = array();

        foreach ($attributes as $attr) {
            if (!isset($this->data[$attr['name']])) {
                $this->data[$attr['name']] = array();
            }
            $this->data[$attr['name']][$attr['key']] = $attr['value'];
        }
    }

}
