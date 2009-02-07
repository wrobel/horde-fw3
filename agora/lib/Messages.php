<?php
/** Horde_Cache **/
require_once 'Horde/Cache.php';

/** Filters */
require_once 'Horde/Text/Filter.php';

/**
 * Agora_Messages:: provides the functions to access both threads and
 * individual messages.
 *
 * $Horde: agora/lib/Messages.php,v 1.266.2.3 2009/01/06 15:22:13 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */
class Agora_Messages {

    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * The forums scope.
     *
     * @var string
     */
    var $_scope;

    /**
     * Current forum data
     *
     * @var array
     */
    var $_forum;

    /**
     * Current forum ID
     *
     * @var string
     */
    var $_forum_id;

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    var $_write_db;

    /**
     * Scope theads table name
     *
     * @var string
     */
    var $_threads_table = 'agora_messages';

    /**
     * Scope theads table name
     *
     * @var string
     */
    var $_forums_table = 'agora_forums';

    /**
     * Cache object
     *
     * @var Horde_Cache
     */
    var $_cache;

    /**
     * Constructor
     */
    function Agora_Messages($scope)
    {
        /* Set parameters. */
        $this->_scope = $scope;
        $this->_connect();

        /* Initialize the Cache object. */
        $this->_cache = &Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                                Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));
    }

    /**
     * Attempts to return a reference to a concrete Messages instance. It will
     * only create a new instance if no Messages instance currently exists.
     *
     * This method must be invoked as: $var = &Agora_Messages::singleton();
     *
     * @param string $scope     Application scope to use
     * @param int    $forum_id  Form to link to
     *
     * @return Forums  The concrete Messages reference, or false on error.
     */
    function &singleton($scope = 'agora', $forum_id = 0)
    {
        static $objects = array();

        if (!isset($objects[$scope])) {
            $driver = $GLOBALS['conf']['threads']['split'] ? 'split_sql' : 'sql';
            require_once AGORA_BASE . '/lib/Messages/' . $driver . '.php';
            $class_name = 'Agora_Messages_' . $driver;
            $objects[$scope] = new $class_name($scope);
        }

        if ($forum_id) {
            /* Check if there was a valid forum object to get. */
            $forum = $objects[$scope]->getForum($forum_id);
            if (is_a($forum, 'PEAR_Error')) {
                return $forum;
            }

            /* Set curernt forum id and forum data */
            $objects[$scope]->_forum = $forum;
            $objects[$scope]->_forum_id = $forum_id;
        }

        return $objects[$scope];
    }

    /**
     * Checks if attachments are allowed in messages for the current forum.
     *
     * @return boolean  Whether attachments allowed or not.
     */
    function allowAttachments()
    {
        return ($GLOBALS['conf']['forums']['enable_attachments'] == '1' ||
                ($GLOBALS['conf']['forums']['enable_attachments'] == '0' &&
                 $this->_forum['forum_attachments']));
    }

    /**
     * Saves the message.
     *
     * @param array $info  Array containing all the message data to save.
     *
     * @return mixed  Message ID on success or PEAR_Error on failure.
     */
    function saveMessage($info)
    {
        /* Check if the thread is locked before changing anything. */
        if ($info['message_parent_id'] &&
            $this->isThreadLocked($info['message_parent_id'])) {
            return PEAR::raiseError(_("This thread has been locked."));
        }

        /* Check post permissions. */
        if (!$this->hasPermission(PERMS_EDIT)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to post messages in forum %s."), $this->_forum_id));
        }

        if (empty($info['message_id'])) {
            /* Get thread parents */
            if ($info['message_parent_id'] > 0) {
                $parents = $this->_db->getOne('SELECT parents FROM ' . $this->_threads_table . ' WHERE message_id = ?',
                                              array($info['message_parent_id']));
                $info['parents'] = $parents . ':' . $info['message_parent_id'];
                $info['message_thread'] = $this->getThreadRoot($info['message_parent_id']);
            } else {
                $info['parents'] = '';
                $info['message_thread'] = 0;
            }

            /* Create new message */
            $sql = 'INSERT INTO ' . $this->_threads_table
                . ' (message_id, forum_id, message_thread, parents, '
                . 'message_author, message_subject, body, attachments, '
                . 'message_timestamp, message_modifystamp, ip) '
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)';

            $info['message_id'] = $this->_write_db->nextId('agora_messages');
            $params = array($info['message_id'],
                            $this->_forum_id,
                            $info['message_thread'],
                            $info['parents'],
                            Auth::getAuth() ? Auth::getAuth() : $info['posted_by'],
                            $this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            time(),
                            time(),
                            $_SERVER['REMOTE_ADDR']);

            $result = $this->_write_db->query($sql, $params);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            /* Update sequence */
            $this->forumSequence($this->_forum_id, 'message', '+');
            if ($info['message_thread']) {
                $this->sequence($info['message_thread'], '+');
            } else {
                $this->forumSequence($this->_forum_id, 'thread', '+');
            }
        } else {
            /* Update message data */
            $sql = 'UPDATE ' . $this->_threads_table . ' SET ' .
                   'message_subject = ?, body = ?, message_modifystamp = ? WHERE message_id = ?';
            $params = array($this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            time(),
                            $info['message_id']);

            $result = $this->_write_db->query($sql, $params);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            /* Get message thread for cache expiration */
            $info['message_thread'] = $this->getThreadRoot($info['message_id']);
            if (is_a($info['message_thread'], 'PEAR_Error')) {
                return $info['message_thread'];
            }
        }

        /* Handle attachment saves or deletions. */
        if (!empty($info['message_attachment']) ||
            !empty($info['attachment_delete'])) {
            if (is_a($vfs = Agora::getVFS(), 'PEAR_Error')) {
                return $vfs;
            }
            $vfs_dir = AGORA_VFS_PATH . $this->_forum_id . '/' . $info['message_id'];

            /* Check if delete requested or new attachment loaded, and delete
             * any existing one. */
            if (!empty($info['attachment_delete'])) {
                $sql = 'SELECT file_id FROM agore_files WHERE message_id = ?';
                foreach ($this->_db->getCol($sql, 0, array($info['message_id'])) as $file_id) {
                    if ($vfs->exists($vfs_dir, $file_id)) {
                        $delete = $vfs->deleteFile($vfs_dir, $file_id);
                        if (is_a($delete, 'PEAR_Error')) {
                            return $delete;
                        }
                    }
                }
                $this->_write_db->getCol('DELETE FROM agore_files WHERE message_id = ?', array($info['message_id']));
                $attachments = 0;
            }

            /* Save new attachment information. */
            if (!empty($info['message_attachment'])) {
                $file_id = $this->_write_db->nextId('agora_files');
                $result = $vfs->write($vfs_dir, $file_id, $info['message_attachment']['file'], true);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }

                $file_sql = 'INSERT INTO agora_files (author, file_name, file_type, file_size, message_id) VALUES (?, ?, ?, ?, ?)';
                $file_data = array('message_author' => Auth::getAuth(),
                                   'file_name' => $info['message_attachment']['name'],
                                   'file_type' => $info['message_attachment']['type'],
                                   'file_size' => $info['message_attachment']['size'],
                                   'message_id' => $info['message_id']);

                $result = $this->_write_db->query($file_sql, $file_data);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $result;
                }
                $attachments = 1;
            }

            $sql = 'UPDATE ' . $this->_threads_table . ' SET attachments = ? WHERE message_id = ?';
            $params = array($attachments, $info['message_id']);
            $result = $this->_write_db->query($sql, $params);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }
        }

        /* Clean cache */
        $this->cleanCache($info['message_thread'], $info['message_id']);

        return $info['message_id'];
    }

    /**
     * Moves a thread to another forum.
     *
     * @todo Update the number of messages in the old/new forum
     *
     * @param integer $thread_id  The ID of the thread to move.
     * @param integer $forum_id   The ID of the destination forum.
     */
    function moveThread($thread_id, $forum_id)
    {
        $sql = 'SELECT forum_id FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $old_forum = $this->_db->getOne($sql, array($thread_id));
        if (is_a($old_forum, 'PEAR_Error')) {
            return $old_forum;
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET forum_id = ? WHERE message_thread = ? OR message_id = ?';
        $result = $this->_write_db->query($sql, array($forum_id, $thread_id, $thread_id));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->forumSequence($old_forum, 'thread', '-');
        $this->forumSequence($forum_id, 'thread', '+');

        return $this->cleanCache($thread_id);
    }

    /**
     * Splits a thread on message id.
     *
     * @param integer $message_id  The ID of the message to split at.
     */
    function splitThread($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread_id = $this->_db->getOne($sql, array($message_id));
        if (is_a($thread_id, 'PEAR_Error')) {
            return $thread_id;
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';
        $result = $this->_write_db->query($sql, array(0, '', $message_id));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE parents LIKE ?';
        $children = $this->_db->getAll($sql, array(":$thread_id:%$message_id%"), DB_FETCHMODE_ASSOC);
        if (is_a($children, 'PEAR_Error')) {
            return $children;
        }

        if (!empty($children)) {
            $pos = strpos($children[0]['parents'], ':' . $message_id);
            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = $message_id;
                $children[$i]['parents'] = substr($message['parents'], $pos);
            }

            $sth = $this->_write_db->prepare('UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?');
            $result = $this->_write_db->executeMultiple($sth, $children);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        // Update count on old thread
        $count = $this->countThreads($thread_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        $result = $this->_write_db->query($sql, array($count, $thread_id));

        // Update count on new thread
        $count = $this->countThreads($message_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        $result = $this->_write_db->query($sql, array($count, $message_id));

        $this->forumSequence($this->_forum_id, 'thread', '+');
        $this->cleanCache($thread_id);
    }


    /**
     * Merges two threads.
     *
     * @param integer $thread_id   The ID of the thread to merge.
     * @param integer $message_id  The ID of the message to merge to.
     */
    function mergeThread($thread_from, $message_id)
    {
        $sql = 'SELECT message_thread, parents FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $destination = $this->_db->getRow($sql, array($message_id), DB_FETCHMODE_ASSOC);
        if (is_a($destination, 'PEAR_Error')) {
            return $destination;
        }

        /* Merge to the top level */
        if ($destination['message_thread'] == 0) {
            $destination['message_thread'] = $message_id;
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE message_id = ? OR message_thread = ?';
        $children = $this->_db->getAll($sql, array($thread_from, $thread_from), DB_FETCHMODE_ASSOC);
        if (is_a($children, 'PEAR_Error')) {
            return $children;
        }

        if (!empty($children)) {
            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = $destination['message_thread'];
                $children[$i]['parents'] = $destination['parents'] . $message['parents'];
                if (empty($children[$i]['parents'])) {
                    $children[$i]['parents'] = ':' . $message_id;
                }
            }

            $sth = $this->_write_db->prepare('UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?');
            $result = $this->_write_db->executeMultiple($sth, $children);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        $count = $this->countThreads($destination['message_thread']);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        $result = $this->_write_db->query($sql, array($count, $destination['message_thread']));

        $this->forumSequence($this->_forum_id, 'thread', '-');
        $this->cleanCache($destination['message_thread']);
    }

    /**
     * Fetches a message.
     *
     * @param integer $message_id  The ID of the message to fetch.
     */
    function getMessage($message_id)
    {
        $message = $this->_cache->get('agora_msg' . $message_id, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($message) {
            return unserialize($message);
        }

        $sql = 'SELECT message_id, forum_id, message_thread, parents, '
            . 'message_author, message_subject, body, message_seq, '
            . 'message_timestamp, view_count, locked, attachments FROM '
            . $this->_threads_table . ' WHERE message_id = ?';
        $message = $this->_db->getRow($sql, array($message_id), DB_FETCHMODE_ASSOC);
        if (is_a($message, 'PEAR_Error')) {
            Horde::logMessage($message, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $message;
        }

        if (empty($message)) {
            return PEAR::raiseError(sprintf(_("Message ID \"%d\" not found"),
                                            $message_id));
        }

        $message['message_subject'] = $this->convertFromDriver($message['message_subject']);
        $message['body'] = $this->convertFromDriver($message['body']);
        if ($message['message_thread'] == 0) {
            $message['message_thread'] = $message_id;
        }

        /* Is author a moderator? */
        if (isset($this->_forum['moderators']) &&
            in_array($message['message_author'], $this->_forum['moderators'])) {
            $message['message_author_moderator'] = 1;
        }

        $this->_cache->set('agora_msg' . $message_id, serialize($message));

        return $message;
    }

    /**
     * Returns a hash with all information necessary to reply to a message.
     *
     * @param mixed $message  The ID of the parent message to reply to, or arry of its data.
     *
     * @return array  A hash with all relevant information.
     */
    function replyMessage($message)
    {
        if (!is_array($message)) {
            $message = $this->getMessage($message);
            if (is_a($message, 'PEAR_Error')) {
                return $message;
            }
        }

        /* Set up the form subject with the parent subject. */
        if (String::lower(String::substr($message['message_subject'], 0, 3)) != 're:') {
            $message['message_subject'] = 'Re: ' . $message['message_subject'];
        } else {
            $message['message_subject'] = $message['message_subject'];
        }

        /* Prepare the message quite body . */
        $message['body'] = sprintf(_("Posted by %s on %s"),
                                   htmlspecialchars($message['message_author']),
                                   strftime($GLOBALS['prefs']->getValue('date_format'), $message['message_timestamp']))
            . "\n-------------------------------------------------------\n"
            . $message['body'];
        $message['body'] = "\n> " . String::wrap($message['body'], 60, "\n> ", NLS::getCharset());

        return $message;
    }

    /**
     * Deletes a message and all replies.
     *
     * @param integer $message_id  The ID of the message to delete.
     *
     * @return mixed  Thread ID on success or PEAR_Error on failure.
     */
    function deleteMessage($message_id)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(PERMS_DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread_id = $this->_db->getOne($sql, array($message_id));
        if (is_a($thread_id, 'PEAR_Error')) {
            Horde::logMessage($thread_id, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $thread_id;
        }

        $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_id = ? OR message_thread = ?';
        $result = $this->_write_db->query($sql, array($message_id, $message_id));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Update couts */
        $this->forumSequence($this->_forum_id, 'message', '-');
        if ($thread_id) {
            $this->sequence($thread_id, '-');
        } else {
            $this->forumSequence($this->_forum_id, 'thread', '-');
        }

        $this->cleanCache($thread_id, $message_id);

        return $thread_id;
    }

    /**
     * Increments or decrements a forum's message count.
     *
     * @param integer $forum_id     Forum to update
     * @param string  $type         What to increment message, thread or view.
     * @param integer|string $diff  Incremental or decremental step, either a
     *                              positive or negative integer, or a plus or
     *                              minus sign.
     */
    function forumSequence($forum_id, $type = 'message', $diff = '+')
    {
        $t = $type . '_count';
        $sql = 'UPDATE ' . $this->_forums_table . ' SET ' . $t . ' = ';

        switch ($diff) {
        case '+':
        case '-':
            $sql .= $t . ' ' . $diff . ' 1';
            break;

        default:
            $sql .= (int)$diff;
            break;
        }

        $sql .= ' WHERE forum_id = ?';
        return $this->_write_db->query($sql, array($forum_id));
    }

    /**
     * Increments or decrements a thread's message count.
     *
     * @param integer $thread_id    Thread to update.
     * @param integer|string $diff  Incremental or decremental step, either a
     *                              positive or negative integer, or a plus or
     *                              minus sign.
     */
    function sequence($thread_id, $diff = '+')
    {
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ';

        switch ($diff) {
        case '+':
        case '-':
            $sql .= 'message_seq ' . $diff . ' 1';
            break;

        default:
            $sql .= (int)$diff;
            break;
        }

        $sql .= ', message_modifystamp = ? WHERE message_id = ?';
        return $this->_write_db->query($sql, array(time(), $thread_id));
    }

    /**
     * Deletes an entire message thread.
     *
     * @param integer $thread_id  The ID of the thread to delete. If not
     *                            specified will delete all the threads for the
     *                            current forum.
     */
    function deleteThread($thread_id = 0)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(PERMS_DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        if ($thread_id > 0) {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_thread = ?';
            $result = $this->_write_db->query($sql, array($thread_id));
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE forum_id = ?';
            $messages = $this->_db->getOne($sql, array($this->_forum_id));

            $this->forumSequence($this->_forum_id, 'thread', '-');
            $this->forumSequence($this->_forum_id, 'message', $messages);

            $this->cleanCache($thread_id);

        } else {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE forum_id = ?';
            $result = $this->_write_db->query($sql, array($this->_forum_id));
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            $this->forumSequence($this->_forum_id, 'thread', 0);
            $this->forumSequence($this->_forum_id, 'message', 0);
        }

        return true;
    }

    /**
     * Returns a list of threads.
     *
     * @param integer $thread_root   Message at which to start the thread.
     *                               If null get all forum threads
     * @param boolean $all_levels    Show all child levels or just one level.
     * @param string  $sort_by       The column by which to sort.
     * @param integer $sort_dir      The direction by which to sort:
     *                                   0 - ascending
     *                                   1 - descending
     * @param boolean $message_view
     * @param string  $link_back     A url to pass to the reply script which
     *                               will be returned to after an insertion of
     *                               a post. Useful in cases when this thread
     *                               view is used in blocks to return to the
     *                               original page rather than to Agora.
     * @param string  $base_url      An alternative URL where edit/delete links
     *                               point to. Mainly for api usage. Takes "%p"
     *                               as a placeholder for the parent message ID.
     * @param string  $from          The thread to start listing at.
     * @param string  $count         The number of threads to return.
     * @param boolean $nofollow      Whether to set the 'rel="nofollow"'
     *                               attribute on linked URLs in the messages.
     */
    function getThreads($thread_root = 0,
                        $all_levels = false,
                        $sort_by = 'message_timestamp',
                        $sort_dir = 0,
                        $message_view = false,
                        $link_back = '',
                        $base_url = null,
                        $from = null,
                        $count = null,
                        $nofollow = false)
    {
        /* Check read permissions */
        if (!$this->hasPermission(PERMS_SHOW)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to read messages in forum %s."), $this->_forum_id));
        }

        /* Get messages data */
        $messages = $this->_getThreads($thread_root, $all_levels, $sort_by, $sort_dir, $message_view, $from, $count);
        if (is_a($messages, 'PEAR_Error') || empty($messages)) {
            return $messages;
        }

        /* Moderators */
        if (isset($this->_forum['moderators'])) {
            $moderators = array_flip($this->_forum['moderators']);
        }

        /* Set up the base urls for actions. */
        $view_url = Horde::applicationUrl('messages/index.php');
        if ($base_url) {
            $edit_url = $base_url;
            $del_url = Util::addParameter($base_url, 'delete', 'true');
        } else {
            $edit_url = Horde::applicationUrl('messages/edit.php');
            $del_url = Horde::applicationUrl('messages/delete.php');
        }

        // Get needed prefs
        $per_page = $GLOBALS['prefs']->getValue('thread_per_page');
        $view_bodies = $GLOBALS['prefs']->getValue('thread_view_bodies');
        $abuse_url = Horde::applicationUrl('messages/abuse.php');
        $hot_img = Horde::img('hot.png', _("Hot thread"), array('title' => _("Hot thread")));
        $new_img = Horde::img('required.png', _("New posts"), array('title' => _("New posts")), $GLOBALS['registry']->getImageDir('horde'));
        $is_moderator = $this->hasPermission(PERMS_DELETE);

        /* Loop through the threads and set up the array. */
        foreach ($messages as $id => $message) {
            /* Add attachment link */
            if ($message['attachments']) {
                $messages[$id]['message_attachment'] = $this->getAttachmentLink($id);
            }

            /* Get last message link */
            if (isset($messages[$id]['last_message_id'])) {
                $url = Agora::setAgoraId($messages[$id]['forum_id'], $messages[$id]['last_message_id'], $view_url, $this->_scope);
                $messages[$id]['message_url'] = Horde::link($url, $messages[$id]['last_message_subject'], '', '', '', $messages[$id]['last_message_subject']);
            }

            /* Check if thread is hot */
            $last_timestamp = isset($message['last_message_timestamp']) ? $message['last_message_timestamp'] : $message['message_timestamp'];
            if ($this->isHot($message['view_count'], $last_timestamp)) {
                $messages[$id]['hot'] = $hot_img;
            }

            /* Check if has new posts since user last visit */
            if ($thread_root == 0 && $this->isNew($id, $last_timestamp)) {
                $messages[$id]['new'] = $new_img;
            }

            /* Mark moderators */
            if (isset($this->_forum['moderators']) && array_key_exists($message['message_author'], $moderators)) {
                $messages[$id]['message_author_moderator'] = 1;
            }

            /* Link to view the message. */
            $url = Agora::setAgoraId($messages[$id]['forum_id'], $id, $view_url, $this->_scope);
            $messages[$id]['link'] = Horde::link($url, $message['message_subject'], '', '', '', $message['message_subject']);

            /* Set up indenting for threads. */
            if ($sort_by != 'message_thread') {
                unset($messages[$id]['indent'], $messages[$id]['parent']);

                /* Links to pages */
                if ($thread_root == 0 && $messages[$id]['message_seq'] > $per_page && $view_bodies == 2) {
                    $sub_pages = $messages[$id]['message_seq'] / $per_page;
                    for ($i = 0; $i < $sub_pages; $i++) {
                        $page_title = sprintf(_("Page %d"), $i+1);
                        $messages[$id]['pages'][] = Horde::link(Util::addParameter($url, 'thread_page', $i), $page_title, '', '', '', $page_title) . ($i+1) . '</a>';
                    }
                }
            }

            /* Button to post a reply to the message. */
            if (!$message['locked']) {
                if ($base_url) {
                    $url = $base_url;
                    if (strpos($url, '%p') !== false) {
                        $url = str_replace('%p', $message['message_id'], $url);
                    } else {
                        $url = Util::addParameter($url, 'message_parent_id', $message['message_id']);
                    }
                    if (!empty($link_back)) {
                        $url = Util::addParameter($url, 'url', $link_back);
                    }
                } else {
                    $url = Agora::setAgoraId($messages[$id]['forum_id'], $id, $view_url, $this->_scope);
                }
                $url = Util::addParameter($url, 'reply_focus', 1) . '#messageform';
                $messages[$id]['reply'] = Horde::link($url, _("Reply to message"), '', '', '', _("Reply to message")) . _("Reply") . '</a>';
            }

            /* Link to edit the message. */
            if ($thread_root > 0 && isset($this->_forum['moderators'])) {
                $url = Agora::setAgoraId($messages[$id]['forum_id'], $id, $abuse_url);
                $messages[$id]['actions'][] = Horde::link($url, _("Report as abuse")) . _("Report as abuse") . '</a>';
            }

            if ($is_moderator) {
                /* Link to edit the message. */
                $url = Agora::setAgoraId($messages[$id]['forum_id'], $id, $edit_url, $this->_scope);
                $messages[$id]['actions'][] = Horde::link($url, _("Edit"), '', '', '', _("Edit message")) . _("Edit") . '</a>';

                /* Link to delete the message. */
                $url = Agora::setAgoraId($messages[$id]['forum_id'], $id, $del_url, $this->_scope);
                $messages[$id]['actions'][] = Horde::link($url, _("Delete"), '', '', '', _("Delete message")) . _("Delete") . '</a>';

                /* Link to lock/unlock the message. */
                $url = Agora::setAgoraId($this->_forum_id, $id, Horde::applicationUrl('messages/lock.php'), $this->_scope);
                $label = ($message['locked']) ? _("Unlock") : _("Lock");
                $messages[$id]['actions'][] = Horde::link($url, $label, '', '', '', $label) . $label . '</a>';

                /* Link to move thread to another forum. */
                if ($this->_scope == 'agora') {
                    if ($message['message_thread'] == $id) {
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::applicationUrl('messages/move.php'), $this->_scope);
                        $messages[$id]['actions'][] = Horde::link($url, _("Move"), '', '', '', _("Move")) . _("Move") . '</a>';

                        /* Link to merge a message thred with anoter thread. */
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::applicationUrl('messages/merge.php'), $this->_scope);
                        $messages[$id]['actions'][] = Horde::link($url, _("Merge"), '', '', '', _("Merge")) . _("Merge") . '</a>';
                    } elseif ($message['message_thread'] != 0) {

                        /* Link to split thread to two threads, from this message after. */
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::applicationUrl('messages/split.php'), $this->_scope);
                        $messages[$id]['actions'][] = Horde::link($url, _("Split"), '', '', '', _("Split")) . _("Split") . '</a>';
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Formats a message body.
     *
     * @param string $body           Text to format.
     * @param array $filters         Filters to use. Defaults to strip tags and,
     *                               highlightquotes, emoticons, and bbcode
     *                               filters.
     * @param array $filters_params  Parameters of filter used.
     */
    function _formatThreads($messages, $sort_by = 'message_modifystamp',
                            $message_view = false, $thread_root = 0)
    {
        /* Get last messages */
        if ($thread_root == 0 && !empty($messages)) {
            $last = array();
            foreach ($messages as $message_id => $message) {
                $sql = 'SELECT message_id AS last_message_id,'
                    . ' message_subject AS last_message_subject,'
                    . ' message_author AS last_message_author,'
                    . ' message_timestamp AS last_message_date'
                    . ' FROM ' . $this->_threads_table . ' WHERE '
                    . ' message_thread = ' . (int)$message_id
                    . ' ORDER BY message_id DESC';
                $sql = $this->_db->modifyLimitQuery($sql, 0, 1);
                $last_message = $this->_db->getRow($sql, null, DB_FETCHMODE_ASSOC);
                if (is_a($last_message, 'PEAR_Error')) {
                    return $last_message;
                }

                $last[$message_id] = $last_message;
            }
        }

        /* Loop through the threads and set up the array. */
        foreach ($messages as $id => $message) {
            $messages[$id]['message_id'] = $id;
            $messages[$id]['message_author'] = htmlspecialchars($message['message_author']);
            $messages[$id]['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']), ENT_COMPAT, NLS::getCharset());
            $messages[$id]['message_date'] = $this->dateFormat($message['message_timestamp']);
            if ($message_view) {
                $messages[$id]['body'] = $this->formatBody($this->convertFromDriver($message['body']));
            }

            // If we are on the top, thread id is message itself
            if ($messages[$id]['message_thread'] == 0) {
                $messages[$id]['message_thread'] = $id;
            }

            /* Get last message */
            if ($thread_root == 0 && isset($last[$id])) {
                $messages[$id] = array_merge($messages[$id], $last[$id]);
                $messages[$id]['last_message_timestamp'] = $messages[$id]['last_message_date'];
                $messages[$id]['last_message_date'] = $this->dateFormat($messages[$id]['last_message_date']);
            }

            /* Set up indenting for threads. */
            if ($sort_by == 'message_thread') {
                $indent = explode(':', $message['parents']);
                $messages[$id]['indent'] = count($indent) - 1;
                $last = array_pop($indent);
                if (!isset($messages[$last])) {
                    $messages[$id]['indent'] = 1;
                    $last = null;
                }
                $messages[$id]['parent'] = $last ? $last : null;
            }
        }

        return $messages;
    }

    /**
     * Formats a message body.
     *
     * @param string $body           Text to format.
     */
    function formatBody($body)
    {
        static $filters, $filters_params;

        if ($filters == null) {
            $filters = array('text2html', 'bbcode', 'highlightquotes', 'emoticons');
            $filters_params = array(array('parselevel' => TEXT_HTML_MICRO),
                                    array(),
                                    array(),
                                    array());

            // check bad words replacement
            $config_dir = $GLOBALS['registry']->get('fileroot', 'agora') . '/config/';
            $config_file = 'words.php';
            if (file_exists($config_dir . $config_file)) {
                if (!empty($GLOBALS['conf']['vhosts'])) {
                    $v_file = substr($config_file, 0, -4) . '-' . $GLOBALS['conf']['server']['name'] . '.php';
                    if (file_exists($config_dir . $config_file)) {
                        $config_file = $v_file;
                    }
                }

                $filters[] = 'words';
                $filters_params[] = array('words_file' => $config_dir . $config_file,
                                        'replacement' => false);
            }
        }

        if (($hasBBcode = strpos($body, '[')) !== false &&
                strpos($body, '[/', $hasBBcode) !== false) {
            $filters_params[0]['parselevel'] = TEXT_HTML_NOHTML;
        }

        return Text_Filter::filter($body, $filters, $filters_params);
    }

    /**
     * Cleans the thread cache.
     *
     * @param integer $thread_root   Thread at which to start cleaning.
     * @param integer $message_id    Speacial message to expire in a thread.
     */
    function cleanCache($thread_root = 0, $message_id = 0)
    {
        if ($message_id) {
            /* Clean message cache */
            $this->_cache->expire('agora_msg' . $message_id);
        }

        /* Prepare cache combinations */
        $_prefs = Horde::loadConfiguration('prefs.php', '_prefs', 'agora');
        $count = $this->countThreads($thread_root);
        $sort_by = array_keys($_prefs['thread_sortby']['enum']);
        $thread_per_page = $_prefs['thread_per_page']['enum'];
        array_push($thread_per_page, 0);

        $key = $this->_scope . ':' . $this->_forum_id . ':' . $thread_root;
        $this->_cleanCache($key, $thread_per_page, $sort_by, $count);

        /* Cleans the threads list cache. */
        $sort_by = array_keys($_prefs['threads_sortby']['enum']);
        $threads_per_page = $_prefs['threads_per_page']['enum'];
        $count = $this->countThreads();
        $key = $this->_forum_id . ':0';
        $this->_cleanCache($key, $threads_per_page, $sort_by, $count);
    }

    function _cleanCache($key, $threads_per_page, $sort_by, $count)
    {
        // format => scope:forum_id:thread_root:all_levels:sort_by:sort_dir:message_view:from:count
        // example => agora:5:0:1:message_thread:0:1:0:0'

        foreach ($sort_by as $by) {
            foreach ($threads_per_page as $per_page) {
                if ($per_page == 0) {
                    $pages = 0;
                } else {
                    $pages = ceil($count / $per_page);
                }
                for ($from = 0; $from <= $pages; $from++) {
                    $this->_cache->expire("$key:0:$by:0:0:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:0:1:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:1:0:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:1:1:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:0:0:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:0:1:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:1:0:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:1:1:$from:$per_page");
                }
            }
        }
    }

    /**
     * Returns true if the message is hot.
     */
    function isHot($views, $last_post)
    {
        if (!$GLOBALS['conf']['threads']['track_views']) {
            return false;
        }

        return ($views > $GLOBALS['prefs']->getValue('threads_hot')) && $last_post > (time() - 86400);
    }

    /**
     * Returns true, has new posts since user last visit
     */
    function isNew($thread_id, $last_post)
    {
        if (!isset($_COOKIE['agora_viewed_threads']) ||
            ($pos1 = strpos($_COOKIE['agora_viewed_threads'], ':' . $thread_id . '|')) === false ||
            ($pos2 = strpos($_COOKIE['agora_viewed_threads'], '|', $pos1)) === false ||
             substr($_COOKIE['agora_viewed_threads'], $pos2+1, 10) > $last_post
            ) {
            return false;
        }

        return true;
    }

    /**
     * Fetches a list of messages awaiting moderation. Selects all messages,
     * irrespective of the thread root, which have the 'moderate' flag set in
     * the attributes.
     *
     * @param string  $sort_by   The column by which to sort.
     * @param integer $sort_dir  The direction by which to sort:
     *                           0 - ascending
     *                           1 - descending
     */
    function getModerateList($sort_by, $sort_dir)
    {
        $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE forum_moderated = ?';
        $parmas = array(1);

        /* Check permissions */
        if (Auth::isAdmin('agora:admin') ||
            ($perms->exists('agora:forums:' . $this->_scope) &&
             $perms->hasPermission('agora:forums:' . $this->_scope, Auth::getAuth(), PERMS_DELETE))) {
                $sql .= ' AND scope = ? ';
                $parmas[] = $this->_scope;
        } else {
            // Get only author forums
            $sql .= ' AND scope = ? AND author = ?';
            $parmas[] = $this->_scope;
            $parmas[] = Auth::getAuth();
        }

        /* Get moderate forums and their names */
        $forums_list = $this->_db->getAssoc($sql, false, $parmas);
        if (is_a($forums_list, 'PEAR_Error') || empty($forums_list)) {
            return $forums_list;
        }

        /* Get message waiting for approval */
        $sql = 'SELECT message_id, forum_id, message_subject, message_author, '
            . 'body, message_timestamp FROM ' . $this->_threads_table . ' WHERE forum_id IN ('
            . implode(',', array_keys($forums_list)) . ')'
            . ' AND approved = ? ORDER BY ' . $sort_by . ' '
            . ($sort_dir ? 'DESC' : 'ASC');

        $messages = $this->_db->getAssoc($sql, true, array(0), DB_FETCHMODE_ASSOC);
        if (is_a($messages, 'PEAR_Error')) {
            return $messages;
        }

        /* Loop through the messages and set up the array. */
        $approve_url = Util::addParameter(Horde::applicationUrl('moderate.php'), 'approve', true);
        $del_url  = Horde::applicationUrl('messages/delete.php');
        foreach ($messages as $id => $message) {
            $messages[$id]['forum_name'] = $this->convertFromDriver($forums_list[$message['forum_id']]);
            $messages[$id]['message_id'] = $id;
            $messages[$id]['message_author'] = htmlspecialchars($message['message_author']);
            $messages[$id]['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']), ENT_COMPAT, NLS::getCharset());
            $messages[$id]['message_body'] = Text_Filter::filter($this->convertFromDriver($message['body']), 'highlightquotes');
            $messages[$id]['message_attachment'] = $this->getAttachmentLink($id);
            $messages[$id]['message_date'] = $this->dateFormat($message['message_timestamp']);
        }

        return $messages;
    }

    /**
     * Get banned users from the current forum
     */
    function getBanned()
    {
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $this->_forum_id;
        if (!$GLOBALS['perms']->exists($perm_name)) {
            return array();
        }

        $forum_perm = $GLOBALS['perms']->getPermission($perm_name);
        if (!is_a($forum_perm, 'DataTreeObject_Permission')) {
            return $forum_perm;
        }

        $permissions = $forum_perm->getUserPermissions();
        if (empty($permissions)) {
            return $permissions;
        }

        // Filter users moderators
        $filter = PERMS_EDIT | PERMS_DELETE;
        foreach ($permissions as $user => $level) {
            if ($level & $filter) {
                unset($permissions[$user]);
            }
        }

        return $permissions;
    }

    /**
     * Ban user on a specific forum.
     *
     * @param string  $user      Moderator username.
     * @param integer $forum_id  Forum to add moderator to.
     * @param string  $action    Action to peform ('add' or 'delete').
     */
    function updateBan($user, $forum_id = null, $action = 'add')
    {
        global $perms;

        if ($forum_id == null) {
            $forum_id = $this->_forum_id;
        }

        $perm_name = 'agora:forums:' . $this->_scope . ':' . $forum_id;
        if (!$perms->exists($perm_name)) {
            $forum_perm = &$perms->newPermission($perm_name);
            $perms->addPermission($forum_perm);
        } else {
            $forum_perm = $perms->getPermission($perm_name);
            if (is_a($forum_perm, 'PEAR_Error')) {
                return $forum_perm;
            }
        }

        if ($action == 'add') {
            // Allow to only read posts
            $forum_perm->removeUserPermission($user, PERMS_ALL, true);
            $forum_perm->addUserPermission($user, PERMS_READ, true);
        } else {
            // Remove all acces to user
            $forum_perm->removeUserPermission($user, PERMS_ALL, true);
        }

        return true;
    }

    /**
     * Updates forum moderators.
     *
     * @param string  $moderator  Moderator username.
     * @param integer $forum_id   Forum to add moderator to.
     * @param string  $action     Action to peform ('add' or 'delete').
     */
    function updateModerator($moderator, $forum_id = null, $action = 'add')
    {
        global $perms;

        if ($forum_id == null) {
            $forum_id = $this->_forum_id;
        }

        switch ($action) {
        case 'add':
            $sql = 'INSERT INTO agora_moderators (forum_id, horde_uid) VALUES (?, ?)';
            break;

        case 'delete':
            $sql = 'DELETE FROM agora_moderators WHERE forum_id = ? AND horde_uid = ?';
            break;
        }

        $result = $this->_write_db->query($sql, array($forum_id, $moderator));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Update permissions*/
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $forum_id;
        if (!$perms->exists($perm_name)) {
            $forum_perm = &$perms->newPermission($perm_name);
            $perms->addPermission($forum_perm);
        } else {
            $forum_perm = $perms->getPermission($perm_name);
            if (is_a($forum_perm, 'PEAR_Error')) {
                return $forum_perm;
            }
        }

        switch ($action) {
        case 'add':
            $forum_perm->addUserPermission($moderator, PERMS_DELETE, true);
            break;

        case 'delete':
            $forum_perm->removeUserPermission($moderator, PERMS_DELETE, true);
            break;
        }

        return $this->cleanForumCache($forum_id);
    }

    /**
     * Approves one or more ids.
     *
     * @param string $action  Whether to 'approve' or 'delete' messages.
     * @param array $ids      Array of message IDs.
     *
     * @return mixed  Returns true if successful or otherwise a PEAR_Error.
     */
    function moderate($action, $ids)
    {
        switch ($action) {
        case 'approve':
            $sql = 'UPDATE ' . $this->_threads_table . ' SET approved = 1'
                 . ' WHERE message_id IN (' . implode($ids) . ')';
            $this->_write_db->query($sql);
            break;

        case 'delete':
            foreach ($ids as $id) {
                $this->deleteMessage($id);
            }
            break;
        }
    }

    /**
     * Returns the number of replies on a thread, or threads in a forum
     *
     * @param integer $thread_root  Thread to count.
     *
     * @return integer  The number of messages in thread or PEAR_Error on
     *                  failure.
     */
    function countThreads($thread_root = 0)
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE message_thread = ?';
        if ($thread_root) {
            return $this->_db->getOne($sql, array($thread_root));
        } else {
            return $this->_db->getOne($sql . ' AND forum_id = ?', array(0, $this->_forum_id));
        }
    }

    /**
     * Returns the number of all messages (threads and replies) in a forum
     *
     * @return integer  The number of messages in forum or PEAR_Error on
     *                  failure.
     */
    function countMessages()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE forum_id = ?';
        return $this->_db->getOne($sql, array($this->_forum_id));
    }

    /**
     * Returns a table showing the specified message list.
     *
     * @param array $threads         A hash with the thread messages as
     *                               returned by {@link
     *                               Agora_Messages::getThreads}.
     * @param array $col_headers     A hash with the column headers.
     * @param boolean $bodies        Display the message bodies?
     * @param string $template_file  Template to use.
     *
     * @return string  The rendered message table.
     */
    function getThreadsUI($threads, $col_headers, $bodies = false,
                          $template_file = false)
    {
        if (!count($threads)) {
            return '';
        }

        /* Render threaded lists with Horde_Tree. */
        $current = key($threads);
        if (!$template_file && isset($threads[$current]['indent'])) {
            require_once 'Horde/Tree.php';
            $tree = Horde_Tree::factory('threads', 'html');
            $tree->setOption(array('multiline' => $bodies,
                                   'lines' => !$bodies));
            $tree->setHeader(array(
                array('html' => '<strong>' . $col_headers['message_thread'] . '</strong>',
                      'width' => '50%',
                      'class' => $col_headers['message_thread_class_plain']),
                array('html' => '<strong>' . $col_headers['message_author'] . '</strong>',
                      'width' => '25%',
                      'class' => $col_headers['message_author_class_plain']),
                array('html' => '<strong>' . $col_headers['message_timestamp'] . '</strong>',
                      'width' => '24%',
                      'class' => $col_headers['message_timestamp_class_plain'])));

            foreach ($threads as $thread) {
                if ($bodies) {
                    $text = '<strong>' . $thread['message_subject'] . '</strong><small>[';
                    if (isset($thread['reply'])) {
                        $text .= ' ' . $thread['reply'];
                    }
                    if (!empty($thread['actions'])) {
                        $text .= ', ' . implode(', ', $thread['actions']);
                    }
                    $text .= ']</small><br />' .
                        str_replace(array("\r", "\n"), '', $thread['body'] . ((isset($thread['message_attachment'])) ? $thread['message_attachment'] : ''));
                } else {
                    $text = '<strong>' . $thread['link'] . $thread['message_subject'] . '</a></strong> ';
                    if (isset($thread['actions'])) {
                        $text .= '<small>[' . implode(', ', $thread['actions']) . ']</small>';
                    }
                }

                $tree->addNode($thread['message_id'],
                               $thread['parent'],
                               $text,
                               $thread['indent'],
                               true,
                               array('icon' => '',
                                     'class' => 'linedRow'),
                               array($thread['message_author'],
                                     $thread['message_date']));
            }

            return $tree->getTree(true);
        }

        /* Set up the thread template tags. */
        $template = new Agora_Template();
        $template->setOption('gettext', true);
        $template->set('threads_list', $threads);
        $template->set('col_headers', $col_headers);
        $template->set('thread_view_bodies', $bodies);

        /* Render template. */
        if (!$template_file) {
            $template_file = AGORA_TEMPLATES . '/messages/threads.html';
        }

        return $template->fetch($template_file);
    }

    /**
     */
    function getThreadRoot($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread_id = $this->_db->getOne($sql, array($message_id));
        return $thread_id ? $thread_id : $message_id;
    }

    /**
     */
    function setThreadLock($message_id, $lock)
    {
        $sql = 'UPDATE ' . $this->_threads_table . ' SET locked = ? WHERE message_id = ?';
        return $this->_write_db->query($sql, array($lock, $message_id));
    }

    /**
     * @return boolean
     */
    function isThreadLocked($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread = $this->_db->getOne($sql, array($message_id));

        return $this->_db->getOne('SELECT locked FROM ' . $this->_threads_table . ' WHERE message_id = ?',
                                  array($thread));
    }

    /**
     */
    function getThreadActions()
    {
        /* Actions. */
        $actions = array();

        $url = Agora::setAgoraId($this->_forum_id, null, Horde::applicationUrl('messages/edit.php'));
        $actions[] = array('url' => $url, 'label' => _("Post message"));

        if ($this->hasPermission(PERMS_DELETE)) {
            if ($this->_scope == 'agora') {
                $url = Agora::setAgoraId($this->_forum_id, null, Horde::applicationUrl('editforum.php'));
                $actions[] = array('url' => $url, 'label' => _("Edit Forum"));
            }
            $url = Agora::setAgoraId($this->_forum_id, null, Horde::applicationUrl('deleteforum.php'), $this->_scope);
            $actions[] = array('url' => $url, 'label' => _("Delete Forum"));
            $url = Agora::setAgoraId($this->_forum_id, null, Horde::applicationUrl('ban.php'), $this->_scope);
            $actions[] = array('url' => $url, 'label' => _("Ban"));
        }

        return $actions;
    }

    /**
     */
    function getForm($vars, $title, $editing = false, $new_forum = false)
    {
        global $conf;

        require_once AGORA_BASE . '/lib/Forms/Message.php';
        $form = new MessageForm($vars, $title);
        $form->setButtons($editing ? _("Save") : _("Post"));
        $form->addHidden('', 'url', 'text', false);

        /* Figure out what to do with forum IDs. */
        if ($new_forum) {
            /* This is a new forum to be created, create the var to hold the
             * full path for the new forum. */
            $form->addHidden('', 'new_forum', 'text', false);
        } else {
            /* This is an existing forum so create the forum ID variable. */
            $form->addHidden('', 'forum_id', 'int', false);
        }

        $form->addHidden('', 'scope', 'text', false);
        $form->addHidden('', 'message_id', 'int', false);
        $form->addHidden('', 'message_parent_id', 'int', false);

        if (!Auth::getAuth()) {
            $form->addVariable(_("From"), 'posted_by', 'text', true);
        }

        /* We are replaying, so display the quote button */
        if ($vars->get('message_parent_id')) {
            $desc = '<input type="button" value="' . _("Quote") . '" class="button" '
                  . 'onClick="this.form.message_body.value=this.form.message_body.value + this.form.message_body_old.value; this.form.message_body_old.value = \'\';" />';
            $form->addVariable(_("Subject"), 'message_subject', 'text', true, false, $desc);
            $form->addHidden('', 'message_body_old', 'longtext', false);
        } else {
            $form->addVariable(_("Subject"), 'message_subject', 'text', true);
        }

        $form->addVariable(_("Message"), 'message_body', 'longtext', true);

        /* Check if an attachment is available and set variables for deleting
         * and previewing. */
        if ($vars->get('attachment_preview')) {
            $form->addVariable(_("Delete the existing attachment?"), 'attachment_delete', 'boolean', false);
            $form->addVariable(_("Current attachment"), 'attachment_preview', 'html', false);
        }

        if ($this->allowAttachments()) {
            $form->addVariable(_("Attachment"), 'message_attachment', 'file', false);
        }

        if (!empty($conf['forums']['captcha']) && !Auth::getAuth()) {
            $form->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null, array(Agora::getCAPTCHA(!$form->isSubmitted()), $conf['forums']['figlet_font']));
        }

        return $form;
    }

    /**
     * Formats time according to user preferences.
     *
     * @param int $timestamp  Message timestamp.
     *
     * @return string  Formatted date.
     */
    function dateFormat($timestamp)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp)
            . ' '
            . (date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp));
    }

    /**
     * Logs a message view.
     *
     * @return boolean True, if the view was logged, false if the message was aleredy seen
     */
    function logView($thread_id)
    {
        if (!$GLOBALS['conf']['threads']['track_views']) {
            return false;
        }

        /* We already read this thread? */
        if (isset($_COOKIE['agora_viewed_threads']) &&
            strpos($_COOKIE['agora_viewed_threads'], ':' . $thread_id . '|') !== false) {
            return false;
        }

        /* Rembember when we see a thread */
        if (!isset($_COOKIE['agora_viewed_threads'])) {
            $_COOKIE['agora_viewed_threads'] = ':';
        }
        $_COOKIE['agora_viewed_threads'] .= $thread_id . '|' . time() . ':';

        setcookie('agora_viewed_threads', $_COOKIE['agora_viewed_threads'], time()+22896000, $GLOBALS['conf']['cookie']['path'],
                  $GLOBALS['conf']['cookie']['domain'],  $GLOBALS['conf']['use_ssl'] == 1 ? 1 : 0);

        /* Update the count */
        $sql = 'UPDATE ' . $this->_threads_table . ' SET view_count = view_count + 1 WHERE message_id = ?';
        $result = $this->_write_db->query($sql, array($thread_id));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Constructs message attachments link.
     */
    function getAttachmentLink($message_id)
    {
        if (!$this->allowAttachments()) {
            return '';
        }

        $sql = 'SELECT file_id, file_name, file_size, file_type FROM agora_files WHERE message_id = ?';
        $files = $this->_db->getAssoc($sql, true, array($message_id), DB_FETCHMODE_ASSOC);
        if (is_a($files, 'PEAR_Error') || empty($files)) {
            Horde::logMessage($files, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $files;
        }

        global $mime_drivers, $mime_drivers_map;
        $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
        extract($result);
        require_once 'Horde/MIME/Part.php';
        require_once 'Horde/MIME/Viewer.php';
        require_once 'Horde/MIME/Magic.php';
        require_once 'Horde/MIME/Contents.php';

        /* Make sure we have the tooltips javascript. */
        Horde::addScriptFile('tooltip.js', 'horde', true);

        /* Constuct the link with a tooltip for further info on the download. */
        $html = '<br />';
        $view_url = Horde::applicationUrl('view.php');
        foreach ($files as $file_id => $file) {
            $mime_icon = MIME_Viewer::getIcon($file['file_type']);
            $title = _("download") . ': ' . $file['file_name'];
            $tooltip = $title . "\n" . sprintf(_("size: %s"), $this->formatSize($file['file_size'])) . "\n" . sprintf(_("type: %s"), $file['file_type']);
            $url = Util::addParameter($view_url, array('forum_id' => $this->_forum_id,
                                                       'message_id' => $message_id,
                                                       'file_id' => $file_id,
                                                       'file_name' => $file['file_name'],
                                                       'file_type' => $file['file_type']));
            $html .= Horde::linkTooltip($url, $title, '', '', '', $tooltip) .
                     Horde::img($mime_icon, $title, 'align="middle"', '') . '&nbsp;' . $file['file_name'] . '</a>&nbsp;&nbsp;<br />';
        }

        return $html;
    }

    /**
     * Formats file size.
     *
     * @param int $filesize
     *
     * @return string  Formatted filesize.
     */
    function formatSize($filesize)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $pass = 0; // set zero, for Bytes
        while($filesize >= 1024) {
            $filesize /= 1024;
            $pass++;
        }

        return round($filesize, 2) . ' ' . $units[$pass];
    }


    /**
     * Fetches a forum data.
     *
     * @param integer $forum_id  The ID of the forum to fetch.
     *
     * @return array  The forum hash or a PEAR_Error on failure.
     */
    function getForum($forum_id = 0)
    {
        if (!$forum_id) {
            $forum_id = $this->_forum_id;
        } elseif (is_a($forum_id, 'PEAR_Error')) {
            return $forum_id;
        }

        /* Check if we can read messages in this forum */
        if (!$this->hasPermission(PERMS_SHOW, $forum_id)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to access messages in forum %s."), $forum_id));
        }

        $forum = $this->_cache->get('agora_forum_' . $forum_id, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($forum) {
            return unserialize($forum);
        }

        $sql = 'SELECT forum_id, forum_name, scope, active, forum_description, '
            . 'forum_parent_id, forum_moderated, forum_attachments, author FROM '
            . '' . $this->_forums_table . ' WHERE forum_id = ?';
        $forum = $this->_db->getRow($sql, array($forum_id), DB_FETCHMODE_ASSOC);
        if (is_a($forum, 'PEAR_Error')) {
            return $forum;
        } elseif (empty($forum)) {
            return PEAR::raiseError(sprintf(_("Forum %s does not exist."), $forum_id));
        }

        $forum['forum_name'] = $this->convertFromDriver($forum['forum_name']);
        $forum['forum_description'] = $this->convertFromDriver($forum['forum_description']);

        /* Get moderators */
        $sql = 'SELECT horde_uid FROM agora_moderators WHERE forum_id = ?';
        $moderators = $this->_db->getCol($sql, 0, array($forum_id));
        if (is_a($moderators, 'PEAR_Error')) {
            return $moderators;
        } elseif (!empty($moderators)) {
            $forum['moderators'] = $moderators;
        }

        $this->_cache->set('agora_forum_' . $forum_id, serialize($forum));

        return $forum;
    }

    /**
     * Returns the number of forums.
     */
    function countForums()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_forums_table . ' WHERE active = ? AND scope = ?';
        return $this->_db->getOne($sql, array(1, $this->_scope));
    }

    /**
     * Fetches a list of forums.
     *
     * @param integer $root_forum  The first level forum.
     * @param boolean $formatted   Whether to return the list formatted or raw.
     * @param string  $sort_by     The column to sort by.
     * @param integer $sort_dir    Sort direction, 0 = ascending,
     *                             1 = descending.
     * @param boolean $add_scope   Add parent forum if forum for another
     *                             scope.
     * @param string  $from        The forum to start listing at.
     * @param string  $count       The number of forums to return.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     */
    function getForums($root_forum = 0, $formatted = true,
                       $sort_by = 'forum_name', $sort_dir = 0,
                       $add_scope = false, $from = 0, $count = 0)
    {
        /* Get messages data */
        $forums = $this->_getForums($root_forum, $formatted, $sort_by,
                                    $sort_dir, $add_scope, $from, $count);
        if (is_a($forums, 'PEAR_Error') || empty($forums) || !$formatted) {
            return $forums;
        }

        $moderate = array();
        $user = Auth::getAuth();
        $edit_url =  Horde::applicationUrl('messages/edit.php');
        $editforum_url =  Horde::applicationUrl('editforum.php');
        $delete_url = Horde::applicationUrl('deleteforum.php');

        foreach ($forums as $forum_id => $forum) {
            $forums[$forum_id]['indentn'] =  0;
            $forums[$forum_id]['indent'] = '';
            $forums[$forum_id]['url'] = Agora::setAgoraId($forum_id, null, Horde::applicationUrl('threads.php'), $forum['scope'], true);
            $forums[$forum_id]['message_count'] = number_format($forum['message_count']);
            $forums[$forum_id]['thread_count'] = number_format($forum['thread_count']);

            if (isset($forums[$forum_id]['message_timestamp'])) {
                $forums[$forum_id]['message_date'] = Agora_Messages::dateFormat($forum['message_timestamp']);
                $forums[$forum_id]['message_url'] = Agora::setAgoraId($forum_id, $forum['message_id'], Horde::applicationUrl('messages/index.php'), $forum['scope'], true);
            }

            $forums[$forum_id]['actions'] = array();

            /* Post message button. */
            $url = Agora::setAgoraId($forum_id, null, $edit_url, $forum['scope'], true);
            $forums[$forum_id]['actions'][] = Horde::link($url, _("Post message")) . _("New Post") . '</a>';

            if ($this->hasPermission(PERMS_EDIT, $forum_id, $forum['scope'])) {
                /* Edit forum button. */
                $url = Agora::setAgoraId($forum_id, null, $editforum_url, $forum['scope'], true);
                $forums[$forum_id]['actions'][] = Horde::link($url, _("Edit forum")) . _("Edit") . '</a>';
            }

            if ($this->hasPermission(PERMS_DELETE, $forum_id, $forum['scope'])) {
                /* Delete forum button. */
                $url = Agora::setAgoraId($forum_id, null, $delete_url, $forum['scope'], true);
                $forums[$forum_id]['actions'][] = Horde::link($url, _("Delete forum")) . _("Delete") . '</a>';

                /* User is a moderator */
                if (isset($forum['moderators']) && in_array($user, $forum['moderators'])) {
                    $moderate[] = $forum_id;
                }
            }
        }

        /* If needed, display moderate link */
        if (!empty($moderate)) {
            $sql = 'SELECT forum_id, COUNT(forum_id) FROM ' . $this->_threads_table
                 . ' WHERE forum_id IN (' . implode(',', $moderate) . ') AND approved = ?'
                 . ' GROUP BY forum_id';
            $unapproved = $this->_db->getAssoc($sql, false, array(0));
            if (is_a($unapproved, 'PEAR_Error')) {
                return $unapproved;
            }

            $url = Horde::link(Horde::applicationUrl('moderate.php', true), _("Moderate")) . _("Moderate") . '</a>';
            foreach ($unapproved as $forum_id => $count) {
                $forums[$forum_id]['actions'][] = $url . ' (' . $count . ')' ;
            }
        }

        return $forums;
    }

    /**
     * Fetches a list of forums.
     *
     * @param integer $root_forum  The first level forum.
     * @param boolean $formatted   Whether to return the list formatted or raw.
     * @param string  $sort_by     The column to sort by.
     * @param integer $sort_dir    Sort direction, 0 = ascending,
     *                             1 = descending.
     * @param boolean $add_scope   Add parent forum if forum for another
     *                             scopelication.
     * @param string  $from        The forum to start listing at.
     * @param string  $count       The number of forums to return.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     */
    function _getForums($root_forum = 0, $formatted = true,
                        $sort_by = 'forum_name', $sort_dir = 0,
                        $add_scope = false,  $from = 0, $count = 0)
    {
        return array();
    }

    /**
     * Fetches a list of forums.
     *
     * @param integer $forums      Frorms to format
     * @param boolean $formatted   Whether to return the list formatted or raw.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     */
    function _formatForums($forums, $formatted = true)
    {
        foreach (array_keys($forums) as $forum_id) {
            $forums[$forum_id]['forum_name'] = $this->convertFromDriver($forums[$forum_id]['forum_name']);
            if ($formatted) {
                $forums[$forum_id]['forum_description'] = $this->convertFromDriver($forums[$forum_id]['forum_description']);
            }
        }

        if ($formatted) {
            /* Get last messages */
            $last = array();
            foreach ($forums as $forum_id => $forum) {
                $sql = 'SELECT message_id, message_author,'
                    . ' message_subject, message_timestamp FROM ' . $this->_threads_table
                    . ' WHERE forum_id = ' . (int)$forum_id
                    . ' ORDER BY message_id DESC';
                $sql = $this->_db->modifyLimitQuery($sql, 0, 1);
                $last_message = $this->_db->getRow($sql, null, DB_FETCHMODE_ASSOC);
                if (is_a($last_message, 'PEAR_Error')) {
                    return $last_message;
                }
                $last[$forum_id] = $last_message;
            }

            /* Get moderators */
            $sql = 'SELECT forum_id, horde_uid'
                . ' FROM agora_moderators WHERE forum_id IN (' . implode(',', array_keys($forums)) . ')';
            $moderators = $this->_db->getAssoc($sql, false, null, DB_FETCHMODE_ASSOC, true);
            if (is_a($moderators, 'PEAR_Error')) {
                return $moderators;
            }

            foreach ($last as $forum_id => $message) {
                $message['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']));
                $forums[$forum_id] = array_merge($forums[$forum_id], $message);
                if (isset($moderators[$forum_id])) {
                    $forums[$forum_id]['moderators'] = $moderators[$forum_id];
                }
            }
        }

        return $forums;
    }

    /**
     * Get forums ids and titles
     *
     * @return array  An array of forums and form names.
     */
    function getBareForums()
    {
        return array();
    }

    /**
     * Cleans the forum cache.
     *
     * @param integer $forum_id  Special forum to expire.
     */
    function cleanForumCache($forum_id = 0)
    {
        if ($forum_id) {
            $this->_cache->expire('agora_forum_' . $forum_id);
        }

        $_prefs = Horde::loadConfiguration('prefs.php', '_prefs', 'agora');
        $count = $this->countForums();
        $sort_by = array('forum_name', 'message_count', 'forum_id');
        $forums_per_page = $_prefs['thread_per_page']['enum'];

        $key = $this->_scope . ':' . 0;
        foreach ($sort_by as $by) {
            foreach ($forums_per_page as $per_page) {
                if ($per_page == 0) {
                    $pages = 0;
                } else {
                    $pages = ceil($count / $per_page);
                }
                for ($from = 0; $from <= $pages; $from++) {
                    $this->_cache->expire("$key:0:$by:0:0:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:0:1:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:1:0:$from:$per_page");
                    $this->_cache->expire("$key:0:$by:1:1:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:0:0:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:0:1:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:1:0:$from:$per_page");
                    $this->_cache->expire("$key:1:$by:1:1:$from:$per_page");
                }
            }
        }
    }

    /**
     * Returns an ID for a given forum name.
     *
     * @param string $forum_name  The full forum name.
     *
     * @return integer  The ID of the forum.
     */
    function getForumId($forum_name)
    {
        static $ids = array();

        if (!isset($ids[$forum_name])) {
            $sql = 'SELECT forum_id FROM ' . $this->_forums_table . ' WHERE scope = ? AND forum_name = ? ';
            $params = array($this->_scope, $forum_name);
            $ids[$forum_name] = $this->_db->getOne($sql, $params);
        }

        return $ids[$forum_name];
    }

    /**
     * Creates a new forum.
     *
     * @param string $forum_name  Forum name.
     * @param string $forum_owner Forum owner.
     *
     * @return integer ID of the new generated forum.
     */
    function newForum($forum_name, $owner)
    {
        if (empty($forum_name)) {
            return PEAR::raiseError(_("Cannot create a forum with an empty name."));
        }

        $forum_id = $this->_write_db->nextId('agora_forums');
        $sql = 'INSERT INTO ' . $this->_forums_table . ' (forum_id, scope, forum_name, active, author) VALUES (?, ?, ?, ?, ?)';
        $res = $this->_write_db->query($sql, array($forum_id, $this->_scope, $this->convertToDriver($forum_name), 1, $owner));
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        return $forum_id;
    }

    /**
     * Saves a forum, either creating one if no forum ID is given or updating
     * an existing one.
     *
     * @param array $info  The forum information to save consisting of:
     *                       forum_id
     *                       forum_author
     *                       forum_parent_id
     *                       forum_name
     *                       forum_moderated
     *                       forum_description
     *                       forum_attachments
     *
     * @return mixed  The forum ID on success or PEAR_Error on failure.
     */
    function saveForum($info)
    {
        if (empty($info['forum_id'])) {
            if (empty($info['author'])) {
                $info['author'] = Auth::getAuth();
            }
            $info['forum_id'] = $this->newForum($info['forum_name'], $info['author']);
            if (is_a($info['forum_id'], 'PEAR_Error')) {
                return $info['forum_id'];
            }
        }

        $sql = 'UPDATE ' . $this->_forums_table . ' SET forum_name = ?, forum_parent_id = ?, '
             . 'forum_description = ?, forum_moderated = ?, '
             . 'forum_attachments = ? WHERE forum_id = ?';

        $params = array($this->convertToDriver($info['forum_name']),
                        (int)$info['forum_parent_id'],
                        $this->convertToDriver($info['forum_description']),
                        (int)$info['forum_moderated'],
                        isset($info['forum_attachments']) ? $info['forum_attachments'] : abs($GLOBALS['conf']['forums']['enable_attachments']),
                        $info['forum_id']);

        $result = $this->_write_db->query($sql, $params);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->cleanForumCache($info['forum_id']);
        return $info['forum_id'];
    }

    /**
     * Deletes a forum, any subforums that are present and all messages
     * contained in the forum and subforums.
     *
     * @param integer $forum_id  The ID of the forum to delete.
     *
     * @return mixed  True on success or PEAR_Error on failure.
     */
    function deleteForum($forum_id)
    {
        $result = $this->deleteThread();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Delete the forum itself. */
        $result = $this->_write_db->query('DELETE FROM ' . $this->_forums_table . ' WHERE forum_id = ? ', array($forum_id));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->cleanForumCache($forum_id);
        return true;
    }

    /**
     * Searches forums for matching threads or posts.
     *
     * @param array $filter  Hash of filter criteria:
     *          'forums'         => Array of forum IDs to search.  If not
     *                              present, searches all forums.
     *          'keywords'       => Array of keywords to search for.  If not
     *                              present, finds all posts/threads.
     *          'allkeywords'    => Boolean specifying whether to find all
     *                              keywords; otherwise, wants any keyword.
     *                              False if not supplied.
     *          'message_author' => Name of author to find posts by.  If not
     *                              present, any author.
     *          'searchsubjects' => Boolean specifying whether to search
     *                              subjects.  True if not supplied.
     *          'searchcontents' => Boolean specifying whether to search
     *                              post contents.  False if not supplied.
     * @param string  $sort_by       The column by which to sort.
     * @param integer $sort_dir      The direction by which to sort:
     *                                   0 - ascending
     *                                   1 - descending
     * @param string  $from          The thread to start listing at.
     * @param string  $count         The number of threads to return.
     */
    function search($filter, $sort_by = 'message_subject', $sort_dir = 0,
                    $from = 0, $count = 0)
    {
        if (!isset($filter['allkeywords'])) {
            $filter['allkeywords'] = false;
        }
        if (!isset($filter['searchsubjects'])) {
            $filter['searchsubjects'] = true;
        }
        if (!isset($filter['searchcontents'])) {
            $filter['searchcontents'] = false;
        }

        /* Select forums ids to search in */
        $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE ';
        if (empty($filter['forums'])) {
            $sql .= ' active = ? AND scope = ?';
            $forums = $this->_db->getAssoc($sql, false, array(1, $this->_scope));
        } else {
            $sql .= ' forum_id IN (' . implode(',', $filter['forums']) . ')';
            $forums = $this->_db->getAssoc($sql);
        }
        if (is_a($forums, 'PEAR_Error')) {
            return $forums;
        }

        /* Build query  */
        $sql = ' FROM ' . $this->_threads_table . ' WHERE forum_id IN (' . implode(',', array_keys($forums)) . ')';

        if (!empty($filter['keywords'])) {
            $sql .= ' AND (';
            if ($filter['searchsubjects']) {
                $keywords = '';
                foreach ($filter['keywords'] as $keyword) {
                    if (!empty($keywords)) {
                        $keywords .= $filter['allkeywords'] ? ' AND ' : ' OR ';
                    }
                    $keywords .= 'message_subject LIKE ' . $this->_db->quote('%' . $keyword . '%');
                }
                $sql .= '(' . $keywords . ')';
            }
            if ($filter['searchcontents']) {
                if ($filter['searchsubjects']) {
                    $sql .= ' OR ';
                }
                $keywords = '';
                foreach ($filter['keywords'] as $keyword) {
                    if (!empty($keywords)) {
                        $keywords .= $filter['allkeywords'] ? ' AND ' : ' OR ';
                    }
                    $keywords .= 'body LIKE ' . $this->_db->quote('%' . $keyword . '%');
                }
                $sql .= '(' . $keywords . ')';
            }
            $sql .= ')';
        }

        if (!empty($filter['author'])) {
            $sql .= ' AND message_author = ' . $this->_db->quote(String::lower($filter['author'], NLS::getCharset()));
        }

        /* Sort by result column. */
        $sql .= ' ORDER BY ' . $sort_by . ' ' . ($sort_dir ? 'DESC' : 'ASC');

        /* Slice directly in DB. */
        if ($count) {
            $total = $this->_db->getOne('SELECT COUNT(*) '  . $sql);
            $sql = $this->_db->modifyLimitQuery($sql, $from, $count);
        }

        $sql = 'SELECT message_id, forum_id, message_subject, message_author, message_timestamp '  . $sql;
        $messages = $this->_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
        if (is_a($messages, 'PEAR_Error')) {
            return $messages;
        }
        if (empty($messages)) {
            return array('results' => array(), 'total' => 0);
        }

        $results = array();
        $msg_url = Horde::applicationUrl('messages/index.php');
        $forum_url = Horde::applicationUrl('threads.php');
        foreach ($messages as $message) {
            if (!isset($results[$message['forum_id']])) {
                $index = array('agora' => $message['forum_id'], 'scope' => $this->_scope);
                $results[$message['forum_id']] = array('forum_id'   => $message['forum_id'],
                                                       'forum_url'  => Util::addParameter($forum_url, $index),
                                                       'forum_name' => $this->convertFromDriver($forums[$message['forum_id']]),
                                                       'messages'   => array());
            }
            $index = array('agora' => $message['forum_id']. '.' . $message['message_id'], 'scope' => $this->_scope);
            $results[$message['forum_id']]['messages'][] = array(
                'message_id' => $message['message_id'],
                'message_subject' => htmlspecialchars($this->convertFromDriver($message['message_subject'])),
                'message_author' => $message['message_author'],
                'message_date' => $this->dateFormat($message['message_timestamp']),
                'message_url' => Util::addParameter($msg_url, $index));
        }

        return array('results' => $results, 'total' => $total);
    }

    /**
     * Finds out if the user has the specified rights to the messages forum.
     *
     * @param integer $perm      The permission level needed for access.
     * @param integer $forum_id  Forum to check permissions for.
     * @param string $scope      Application scope to use.
     *
     * @return boolean  True if the user has the specified permissions.
     */
    function hasPermission($perm = PERMS_READ, $forum_id = null, $scope = null)
    {
        global $perms;

        // Allow all admins
        if (($forum_id === null && isset($this->_forum['author']) && $this->_forum['author'] == Auth::getAuth()) ||
            Auth::isAdmin('agora:admin')) {
            return true;
        }

        // Allow forum author
        if ($forum_id === null) {
            $forum_id = $this->_forum_id;
        }

        if ($scope === null) {
            $scope = $this->_scope;
        }

        if (!$perms->exists('agora:forums:' . $scope) &&
            !$perms->exists('agora:forums:' . $scope . ':' . $forum_id)) {
            return ($perm & PERMS_DELETE) ? false : true;
        }

        return $perms->hasPermission('agora:forums:' . $scope, Auth::getAuth(), $perm) ||
            $perms->hasPermission('agora:forums:' . $scope . ':' . $forum_id, Auth::getAuth(), $perm);
    }

    /**
     * Converts a value from the driver's charset to the default charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    function convertFromDriver($value)
    {
        return String::convertCharset($value, $this->_params['charset']);
    }

    /**
     * Converts a value from the default charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    function convertToDriver($value)
    {
        return String::convertCharset($value, NLS::getCharset(), $this->_params['charset']);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    function _connect()
    {
        $params = Horde::getDriverConfig('storage', 'sql');
        Horde::assertDriverConfig($params, 'storage',
                                  array('phptype', 'charset'));

        if (!isset($params['database'])) {
            $params['database'] = '';
        }
        if (!isset($params['username'])) {
            $params['username'] = '';
        }
        if (!isset($params['hostspec'])) {
            $params['hostspec'] = '';
        }
        $this->_params = $params;

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_write_db = &DB::connect($params,
                                        array('persistent' => !empty($params['persistent'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($params['splitread'])) {
            $params = array_merge($params, $params['read']);
            $this->_db = &DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        return true;
    }

}
