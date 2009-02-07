<?php
/**
 * Agora_Messages_sql:: provides the functions to access both threads and
 * individual messages in one table for all scopes
 *
 * $Horde: agora/lib/Messages/sql.php,v 1.6.2.1 2009/01/06 15:22:14 jan Exp $
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
class Agora_Messages_sql extends Agora_Messages {

    /**
     * Get forums ids and titles
     *
     * @return array  An array of forums and form names.
     */
    function getBareForums()
    {
        if ($this->_scope == 'agora') {
            $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE scope = ?';
        } else {
            $sql = 'SELECT forum_id, forum_description FROM ' . $this->_forums_table . ' WHERE scope = ?';
        }

        return $this->_db->getAssoc($sql, false, array($this->_scope));
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
        $key = $this->_scope . ':' . $root_forum . ':' . $formatted . ':'
            . $sort_by . ':' . $sort_dir . ':' . $add_scope . ':' . $from
            . ':' . $count;
        $forums = $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($forums) {
            return unserialize($forums);
        }

        $sql = 'SELECT forum_id, forum_name';

        if ($formatted) {
            $sql .= ', scope, active, forum_description, forum_parent_id, '
                . 'forum_moderated, forum_attachments, message_count, '
                . 'thread_count';
        }

        $sql .= ' FROM ' . $this->_forums_table . ' WHERE active = ? ';
        $params = array(1);

        if ($root_forum != 0) {
            $sql .= ' AND forum_parent_id = ? ';
            $params[] = $root_forum;
        }

        if ($add_scope) {
            $sql .= ' AND scope = ? ';
            $params[] = $this->_scope;
        }

        /* Sort by result colomn if possible */
        $sql .= ' ORDER BY ';
        if ($sort_by == 'forum_name' || $sort_by == 'message_count') {
            $sql .= $sort_by;
        } else {
            $sql .= 'forum_id';
        }
        $sql .= ' ' . ($sort_dir ? 'DESC' : 'ASC');

        /* Slice direcly in DB. */
        if ($count) {
            $sql = $this->_db->modifyLimitQuery($sql, $from, $count);
        }

        $forums = $this->_db->getAssoc($sql, $formatted, $params, DB_FETCHMODE_ASSOC);
        if (is_a($forums, 'PEAR_Error') || empty($forums)) {
            return $forums;
        }

        $forums = $this->_formatForums($forums, $formatted);

        $this->_cache->set($key, serialize($forums));

        return $forums;
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
     * @param string  $from          The thread to start listing at.
     * @param string  $count         The number of threads to return.
     */
    function _getThreads($thread_root = 0,
                         $all_levels = false,
                         $sort_by = 'message_modifystamp',
                         $sort_dir = 0,
                         $message_view = false,
                         $from = 0,
                         $count = 0)
    {
        /* Cache */
        $key = $this->_scope . ':' . $this->_forum_id . ':' . $thread_root . ':' . intval($all_levels) . ':'
             . $sort_by . ':' . $sort_dir . ':' . intval($message_view) . ':' . intval($from) . ':' . intval($count);
        $messages = $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($messages) {
            return unserialize($messages);
        }

        /* Select threads */
        $sql = 'SELECT m.message_id AS message_id, m.forum_id AS forum_id, m.message_thread AS message_thread, m.parents AS parents, m.message_author AS message_author, '
             . 'm.message_subject AS message_subject, m.message_timestamp AS message_timestamp, m.locked AS locked, m.view_count AS view_count, '
             . 'm.message_seq AS message_seq';
        if ($message_view) {
            $sql .= ', m.body AS body';
        }
        $sql .= ', m.attachments AS attachments FROM ' . $this->_threads_table . ' m, ' . $this->_forums_table  . ' AS f ';

        $params = array('f.scope' => $this->_scope);

        /* Get messages form a specific forums */
        if ($this->_forum_id) {
            $params['m.forum_id'] = $this->_forum_id;
        }

        /* Get all levels? */
        if (!$all_levels) {
            $params['m.parents'] = '';
        }

        /* Get only approved messages. */
        if ($this->_forum['forum_moderated']) {
            $params['m.approved'] = 1;
        }

        /* Add params */
        $sql .= ' WHERE f.forum_id = m.forum_id ';
        if (!empty($params)) {
            $sql .= ' AND ' . implode(' = ? AND ', array_keys($params)) . ' = ? ';
        }

        if ($thread_root) {
            $sql .= ' AND (m.message_id = ? OR m.message_thread = ?)';
            $params[] = $thread_root;
            $params[] = $thread_root;
        }

        /* Sort by result column. */
        $sql .= ' ORDER BY m.' . $sort_by . ' ' . ($sort_dir ? 'DESC' : 'ASC');

        /* Slice direcly in DB. */
        if ($sort_by != 'message_thread' && $count) {
            $sql = $this->_db->modifyLimitQuery($sql, $from, $count);
        }

        $messages = $this->_db->getAssoc($sql, true, $params, DB_FETCHMODE_ASSOC);
        if (is_a($messages, 'PEAR_Error')) {
            Horde::logMessage($messages, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $messages;
        }

        $messages = $this->_formatThreads($messages, $sort_by, $message_view, $thread_root);

        $this->_cache->set($key, serialize($messages));

        return $messages;
    }

}
