<?php

/**
 * The virtual path to use for VFS data.
 */
define('AGORA_VFS_PATH', '.horde/agora/attachments/');
define('AGORA_AVATAR_PATH', '.horde/agora/avatars/');

/**
 * The Agora:: class provides basic Agora functionality.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: agora/lib/Agora.php,v 1.104.2.1 2009/01/06 15:22:13 jan Exp $
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Agora
 */
class Agora {

    /**
     * Determines the requested forum_id, message_id and application by
     * checking first if they are passed as the single encoded var or
     * individual vars.
     *
     * @return array  Forum, message id and application.
     */
    function getAgoraId()
    {
        if (($id = Util::getFormData('agora')) !== null) {
            if (strstr($id, '.')) {
                list($forum_id, $message_id) = explode('.', $id, 2);
            } else {
                $forum_id = $id;
                $message_id = 0;
            }
        } else {
            $forum_id = Util::getFormData('forum_id');
            $message_id = Util::getFormData('message_id');
        }
        $scope = basename(Util::getFormData('scope', 'agora'));

        return array($forum_id, $message_id, $scope);
    }

    /**
     * Creates the Agora id.
     *
     * @return string  If passed with the $url parameter, returns a completed
     *                 url with the agora_id tacked on at the end, otherwise
     *                 returns the simple agora_id.
     */
    function setAgoraId($forum_id, $message_id, $url = '', $scope = null, $encode = false)
    {
        $agora_id = $forum_id . '.' . $message_id;

        if (!empty($url)) {
            if ($scope) {
                $url = Util::addParameter($url, 'scope', $scope, $encode);
            } else {
                $url = Util::addParameter($url, 'scope', Util::getGet('scope', 'agora'), $encode);
            }
            return Util::addParameter($url, 'agora', $agora_id, $encode);
        }

        return $agora_id;
    }

    /**
     * Function that works out the forum ID from a given DataTreen name for a
     * message.
     *
     * @param string $category_name  A ':' separated DataTree name.
     *
     * @return int
     */
    function getForumId($category_name)
    {
        list($forum_id) = explode(':', $category_name);
        return $forum_id;
    }

    /**
     * Returns a new or the current CAPTCHA string.
     *
     * @param boolean $new  If true, a new CAPTCHA is created and returned.
     *                      The current, to-be-confirmed string otherwise.
     *
     * @return string  A CAPTCHA string.
     */
    function getCAPTCHA($new = false)
    {
        if ($new || empty($_SESSION['agora']['CAPTCHA'])) {
            $_SESSION['agora']['CAPTCHA'] = '';
            for ($i = 0; $i < 5; $i++) {
                $_SESSION['agora']['CAPTCHA'] .= chr(rand(65, 90));
            }
        }
        return $_SESSION['agora']['CAPTCHA'];
    }

    /**
     * Formats a list of forums, showing each child of a parent with
     * appropriate indent using '.. ' as a leader.
     *
     * @param array $forums  The list of forums to format.
     *
     * @return array  Formatted forum list.
     */
    function formatCategoryTree($forums)
    {
        foreach ($forums as $id => $forum) {
            $levels = explode(':', $forum);
            $forums[$id] = str_repeat('.. ', count($levels) - 1) . array_pop($levels);
        }
        return $forums;
    }

    /**
     * Returns the column to sort by, checking first if it is specified in the
     * URL, then returning the value stored in prefs.
     *
     * @param string $view  The view name, used to identify preference settings
     *                      for sorting.
     *
     * @return string  The column to sort by.
     */
    function getSortBy($view)
    {
        global $prefs;

        if (($sortby = Util::getFormData($view . '_sortby')) !== null) {
            $prefs->setValue($view . '_sortby', $sortby);
        }
        $sort_by = $prefs->getValue($view . '_sortby');

        /* BC check for now invalid sort criteria. */
        if ($sort_by == 'message_date' || substr($sort_by, 0, 1) == 'l') {
            $sort_by = $prefs->getDefault($view . '_sortby');
            $prefs->setValue($view . '_sortby', $sortby);
        }

        return $sort_by;
    }

    /**
     * Returns the sort direction, checking first if it is specified in the URL,
     * then returning the value stored in prefs.
     *
     * @param string $view  The view name, used to identify preference settings
     *                      for sorting.
     *
     * @return integer  The sort direction, 0 = ascending, 1 = descending.
     */
    function getSortDir($view)
    {
        global $prefs;
        if (($sortdir = Util::getFormData($view . '_sortdir')) !== null) {
            $prefs->setValue($view . '_sortdir', $sortdir);
        }
        return $prefs->getValue($view . '_sortdir');
    }

    /**
     * Formats column headers have sort links and sort arrows.
     *
     * @param array  $columns   The columns to format.
     * @param string $sort_by   The current 'sort-by' column.
     * @param string $sort_dir  The current sort direction.
     * @param string $view      The view name, used to identify preference
     *                          settings for sorting.
     *
     * @return array  The formated column headers to be displayed.
     */
    function formatColumnHeaders($columns, $sort_by, $sort_dir, $view)
    {
        /* Get the current url, remove any sorting parameters. */
        $url = Horde::selfUrl(true);
        $url = Util::removeParameter($url, array($view . '_sortby', $view . '_sortdir'));

        /* Go through the column headers to format and add sorting links. */
        $headers = array();
        foreach ($columns as $col_name => $col_title) {
            $extra = array();
            /* Is this a column with two headers? */
            if (is_array($col_title)) {
                $keys = array_keys($col_title);
                $extra_name = $keys[0];
                if ($sort_by == $keys[1]) {
                    $extra = array($keys[0] => $col_title[$keys[0]]);
                    $col_name = $keys[1];
                    $col_title = $col_title[$keys[1]];
                } else {
                    $extra = array($keys[1] => $col_title[$keys[1]]);
                    $col_name = $keys[0];
                    $col_title = $col_title[$keys[0]];
                }
            }
            if ($sort_by == $col_name) {
                /* This column is currently sorted by, plain title and
                 * add sort direction arrow. */
                $sort_img = ($sort_dir ? 'za.png' : 'az.png');
                $sort_title = ($sort_dir ? _("Sort Ascending") : _("Sort Descending"));
                $col_arrow = Horde::link(Util::addParameter($url, array($view . '_sortby' => $col_name, $view . '_sortdir' => $sort_dir ? 0 : 1)), $sort_title) .
                    Horde::img($sort_img, $sort_title, null, $GLOBALS['registry']->getImageDir('horde')) . '</a> ';
                $col_class = 'selected';
            } else {
                /* Column not currently sorted, add link to sort by
                 * this one and no sort arrow. */
                $col_arrow = '';
                $col_title = Horde::link(Util::addParameter($url, $view . '_sortby', $col_name), sprintf(_("Sort by %s"), $col_title)) . $col_title . '</a>';
                $col_class = 'item';
            }
            $col_class .= ' leftAlign';
            if (count($extra)) {
                list($name, $title) = each($extra);
                $col_title .= '&nbsp;<small>[' .
                    Horde::link(Util::addParameter($url, $view . '_sortby', $name), sprintf(_("Sort by %s"), $title)) . $title . '</a>' .
                    ']</small>';
                $col_name = $extra_name;
            }
            $headers[$col_name] = $col_arrow . $col_title;
            $headers[$col_name . '_class_plain'] = $col_class;
            $headers[$col_name . '_class'] = empty($col_class) ? '' : ' class="' . $col_class . '"';
        }

        return $headers;
    }

    /**
     * Returns a {@link VFS} instance.
     *
     * @return VFS  A VFS instance.
     */
    function &getVFS()
    {
        global $conf;

        if (!isset($conf['vfs']['type'])) {
            return PEAR::raiseError(_("The VFS backend needs to be configured to enable attachment uploads."));
        }

        require_once 'VFS.php';
        return VFS::singleton($conf['vfs']['type'], Horde::getDriverConfig('vfs'));
    }

    function getMenu($returnType = 'object')
    {
        require_once 'Horde/Menu.php';

        $menu = &new Menu();
        $img_dir = $GLOBALS['registry']->getImageDir();
        $scope = Util::getGet('scope', 'agora');

        /* Agora Home. */
        $url = Util::addParameter(Horde::applicationUrl('forums.php'), 'scope', $scope);
        $menu->add($url, _("_Forums"), 'forums.png', $img_dir, null, null,
                   dirname($_SERVER['PHP_SELF']) == $GLOBALS['registry']->get('webroot') && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        /* Thread list, if applicable. */
        if (isset($GLOBALS['forum_id'])) {
            $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::applicationUrl('threads.php')), _("_Threads"), 'threads.png', $GLOBALS['registry']->getImageDir());
            if ($scope == 'agora' && Auth::getAuth()) {
                $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::applicationUrl('messages/edit.php')), _("New Thread"), 'newmessage.png', $GLOBALS['registry']->getImageDir());
            }
        }

        if ($scope == 'agora' && Agora_Messages::hasPermission(PERMS_DELETE, 0, $scope)) {
            $menu->add(Horde::applicationUrl('editforum.php'), _("_New Forum"), 'newforum.png', $img_dir, null, null, Util::getFormData('agora') ? '__noselection' : null);
        }

        if (Agora_Messages::hasPermission(PERMS_DELETE, 0, $scope)) {
            $url = Util::addParameter(Horde::applicationUrl('moderate.php'), 'scope', $scope);
            $menu->add($url, _("_Moderate"), 'moderate.png', $img_dir);
        }

        if (Auth::isAdmin()) {
            $menu->add(Horde::applicationUrl('moderators.php'), _("_Moderators"), 'hot.png', $img_dir);
        }

        $url = Util::addParameter(Horde::applicationUrl('search.php'), 'scope', $scope);
        $menu->add($url, _("_Search"), 'search.png', $GLOBALS['registry']->getImageDir('horde'));

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    function validateAvatar($avatar_path)
    {
        if (!$GLOBALS['conf']['avatar']['allow_avatars'] || !$avatar_path) {
            return false;
        }

        preg_match('/^(http|vfs):\/\/(.*)\/(gallery|uploaded|.*)\/(.*\..*)/i',
                   $avatar_path, $matches);

        switch ($matches[1]) {
        case 'http':
            if (!$GLOBALS['conf']['avatar']['enable_external']) {
                /* Avatar is external and external avatars have been
                 * disabled. */
                return false;
            }
            $dimensions = @getimagesize($avatar_path);
            if (($dimensions === false) ||
                ($dimensions[0] > $GLOBALS['conf']['avatar']['max_width']) ||
                ($dimensions[1] > $GLOBALS['conf']['avatar']['max_height'])) {
                /* Avatar is external and external avatars are
                 * enabled, but the image is too wide or high. */
                return false;
            } else {
                $avatar = null;

                $flock = fopen($avatar_path, 'r');
                while (!feof($flock)) {
                    $avatar .= fread($flock, 2048);
                }
                fclose($flock);

                if (strlen($avatar) > ($GLOBALS['conf']['avatar']['max_size'] * 1024)) {
                    /* Avatar is external and external avatars have
                     * been enabled, but the file is too large. */
                    return false;
                }
            }
            return true;

        case 'vfs':
            switch ($matches[3]) {
            case 'gallery':
                /* Avatar is within the gallery. */
                return $GLOBALS['conf']['avatar']['enable_gallery'];

            case 'uploaded':
                /* Avatar is within the uploaded avatar collection. */
                return $GLOBALS['conf']['avatar']['enable_uploads'];

            default:
                /* Malformed URL. */
                return false;
            }
            break;

        default:
            /* Malformed URL. */
            return false;
        }

        return false;
    }

    function getAvatarUrl($avatar_path, $scopeend_sid = true)
    {
        if (!$avatar_path) {
            return PEAR::raiseError(_("Malformed avatar."));
        }

        preg_match('/^(http|vfs):\/\/(.*)\/(gallery|uploaded|.*)\/(.*\..*)/i',
                   $avatar_path, $matches);

        switch ($matches[1]) {
        case 'http':
            /* HTTP URL's are already "real" */
            break;

        case 'vfs':
            /* We need to do some re-writing to VFS paths. */
            switch ($matches[3]) {
            case 'gallery':
                $avatar_collection_id = '1';
                break;

            case 'uploaded':
                $avatar_collection_id = '2';
                break;

            default:
                return PEAR::raiseError(_("Malformed database entry."));
            }

            $avatar_path = Horde::applicationUrl('avatars/?id=' . urlencode($matches[4]) . ':' . $avatar_collection_id, true, $scopeend_sid);
            break;
        }

        return $avatar_path;
    }
}