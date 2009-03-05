<?php
/**
 * $Horde: gollem/lib/Gollem.php,v 1.172.2.27 2009/03/02 22:16:03 slusarz Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Gollem
 */

/** Sort by file type. */
define('GOLLEM_SORT_TYPE', 0);

/** Sort by file name. */
define('GOLLEM_SORT_NAME', 1);

/** Sort by file date. */
define('GOLLEM_SORT_DATE', 2);

/** Sort by file size. */
define('GOLLEM_SORT_SIZE', 3);

/** Sort ascending. */
define('GOLLEM_SORT_ASCEND', 0);

/** Sort descending. */
define('GOLLEM_SORT_DESCEND', 1);

/**
 * Gollem Base Class.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Gollem
 */
class Gollem {

    /**
     * Check Gollem authentication and change to the currently active
     * directory. Redirects to login page on authentication/session failure.
     *
     * @param string $mode       The authentication mode we are using.
     * @param boolean $redirect  Redirect to the logout page if authentication
     *                           is unsuccessful?
     *
     * @return boolean  True on success, false on failure.
     */
    function checkAuthentication($mode = null, $redirect = true)
    {
        $auth_gollem = &Auth::singleton(array('gollem', 'gollem'));
        $reason = $auth_gollem->authenticate();

        if ($reason !== true) {
            if ($redirect) {
                if ($mode = 'selectlist') {
                    $url = Util::addParameter($GLOBALS['registry']->get('webroot', 'gollem') . '/login.php', 'selectlist_login', 1, false);
                } else {
                    $url = Auth::addLogoutParameters(Gollem::logoutUrl());
                }
                $url = Util::addParameter($url, 'url', Horde::selfUrl(true, true, true), false);
                header('Location: ' . $url);
                exit;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Can we log in without a login screen for the requested backend key?
     *
     * @param string $key     The backend key to check. Defaults to
     *                        Gollem::getPreferredBackend().
     * @param boolean $force  If true, check the backend key even if there is
     *                        more than one backend.
     *
     * @return boolean  True if autologin possible, false if not.
     */
    function canAutoLogin($key = null, $force = false)
    {
        $auto_server = Gollem::getPreferredBackend();
        if ($key === null) {
            $key = $auto_server;
        }

        return (((count($auto_server) == 1) || $force) &&
                Auth::getAuth() &&
                empty($GLOBALS['gollem_backends'][$key]['loginparams']) &&
                !empty($GLOBALS['gollem_backends'][$key]['hordeauth']));
    }

    /**
     * Changes the current directory of the Gollem session to the supplied
     * value.
     *
     * @param string $dir  Directory name.
     *
     * @return mixed  True on Success, PEAR_Error on failure.
     */
    function setDir($dir)
    {
        $dir = Gollem::realPath($dir);

        if (!Gollem::verifyDir($dir)) {
            return PEAR::raiseError(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['gollem_be']['dir'] = $dir;

        Gollem::_setLabel();

        return true;
    }

    /**
     * Changes the current directory of the Gollem session based on the
     * 'dir' form field.
     *
     * @return mixed  True on Success, PEAR_Error on failure.
     */
    function changeDir()
    {
        $dir = Util::getFormData('dir');
        if ($dir !== null) {
            if (strpos($dir, '/') !== 0) {
                $dir = $GLOBALS['gollem_be']['dir'] . '/' . $dir;
            }
            return Gollem::setDir($dir);
        } else {
            Gollem::_setLabel();
            return true;
        }
    }

    /**
     * Get the root directory of the Gollem session.
     *
     * @return string  The root directory.
     */
    function getRoot()
    {
        return $GLOBALS['gollem_be']['root'];
    }

    /**
     * Get the home directory of the Gollem session.
     *
     * @return string  The home directory.
     */
    function getHome()
    {
        return $GLOBALS['gollem_be']['home'];
    }

    /**
     * Get the current directory of the Gollem session.
     *
     * @return mixed  Current dir on success or null on failure.
     */
    function getDir()
    {
        return $GLOBALS['gollem_be']['dir'];
    }

    /**
     * Set the lable to use for the current page.
     *
     * @access private
     */
    function _setLabel()
    {
        $GLOBALS['gollem_be']['label'] = Gollem::getDisplayPath($GLOBALS['gollem_be']['dir']);
        if (empty($GLOBALS['gollem_be']['label'])) {
            $GLOBALS['gollem_be']['label'] = '/';
        }
    }

    /**
     * Internal helper to sort directories first if pref set.
     *
     * @access private
     */
    function _sortDirs($a, $b)
    {
        /* Sort symlinks to dirs as dirs */
        $dira = ($a['type'] === '**dir') ||
            (($a['type'] === '**sym') && ($a['linktype'] === '**dir'));
        $dirb = ($b['type'] === '**dir') ||
            (($b['type'] === '**sym') && ($b['linktype'] === '**dir'));

        if ($GLOBALS['prefs']->getValue('sortdirsfirst')) {
            if ($dira && !$dirb) {
                return -1;
            } elseif (!$dira && $dirb) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Internal sorting function for 'date'.
     *
     * @access private
     */
    function _sortDate($a, $b)
    {
        $dirs = Gollem::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['date'] > $b['date']) {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } elseif ($a['date'] === $b['date']) {
            return Gollem::_sortName($a, $b);
        } else {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        }
    }

    /**
     * Internal sorting function for 'size'.
     *
     * @access private
     */
    function _sortSize($a, $b)
    {
        $dirs = Gollem::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['size'] > $b['size']) {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } elseif ($a['size'] === $b['size']) {
            return 0;
        } else {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        }
    }

    /**
     * Internal sorting function for 'type'.
     *
     * @access private
     */
    function _sortType($a, $b)
    {
        $dirs = Gollem::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['type'] === $b['type']) {
            return Gollem::_sortName($a, $b);
        } elseif ($a['type'] === '**dir') {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        } elseif ($b['type'] === '**dir') {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } else {
            $res = strcasecmp($a['type'], $b['type']);
            return $GLOBALS['prefs']->getValue('sortdir') ? ($res * -1) : $res;
        }
    }

    /**
     * Internal sorting function for 'name'.
     *
     * @access private
     */
    function _sortName($a, $b)
    {
        $dirs = Gollem::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        $res = strcasecmp($a['name'], $b['name']);
        return $GLOBALS['prefs']->getValue('sortdir') ? ($res * -1) : $res;
    }

    /**
     * List the current folder.
     *
     * @param string $dir  The directory name.
     *
     * @return array  The sorted list of files.
     */
    function listFolder($dir)
    {
        global $conf;

        if (!empty($conf['foldercache']['use_cache']) &&
            !empty($conf['cache']['driver']) &&
            ($conf['cache']['driver'] != 'none')) {
            require_once 'Horde/Cache.php';
            require_once 'Horde/Serialize.php';
            $key = Gollem::_getCacheID($dir);

            $cache = &Horde_Cache::singleton($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));
            $res = $cache->get($key, $conf['foldercache']['lifetime']);
            if ($res !== false) {
                $res = Horde_Serialize::unserialize($res, Horde_Serialize::BASIC);
                if (is_array($res)) {
                    return $res;
                }
            }
        }

        $files = $GLOBALS['gollem_vfs']->listFolder($dir, isset($GLOBALS['gollem_be']['filter']) ? $GLOBALS['gollem_be']['filter'] : null, $GLOBALS['prefs']->getValue('show_dotfiles'));
        if (!is_a($files, 'PEAR_Error')) {
            $sortcols = array(
                GOLLEM_SORT_TYPE => '_sortType',
                GOLLEM_SORT_NAME => '_sortName',
                GOLLEM_SORT_DATE => '_sortDate',
                GOLLEM_SORT_SIZE => '_sortSize',
            );
            usort($files, array('Gollem', $sortcols[$GLOBALS['prefs']->getValue('sortby')]));
        }

        if (isset($cache)) {
            require_once 'Horde/Serialize.php';
            $cache->set($key, Horde_Serialize::serialize($files, Horde_Serialize::BASIC), $conf['foldercache']['lifetime']);
        }

        return $files;
    }

    /**
     * Generate the Cache ID for a directory.
     *
     * @access private
     * @since Gollem 1.1
     *
     * @param string $dir  The directory name.
     */
    function _getCacheID($dir)
    {
        global $prefs;
        return implode('|', array(Auth::getAuth(), $_SESSION['gollem']['backend_key'], $prefs->getValue('show_dotfiles'), $prefs->getValue('sortdirsfirst'), $prefs->getValue('sortby'), $prefs->getValue('sortdir'), $dir));
    }

    /**
     * Expire a folder cache entry.
     *
     * @since Gollem 1.1
     *
     * @param string $dir  The directory name.
     */
    function expireCache($dir)
    {
        global $conf;

        if (!empty($conf['foldercache']['use_cache']) &&
            !empty($conf['cache']['driver']) &&
            ($conf['cache']['driver'] != 'none')) {
            require_once 'Horde/Cache.php';
            $cache = &Horde_Cache::singleton($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));
            $cache->expire(Gollem::_getCacheID($dir));
        }
    }

    /**
     * Generate correct subdirectory links.
     *
     * @param string $base  The base directory.
     * @param string $dir   The directory string.
     *
     * @return string  The correct subdirectoy string.
     */
    function subdirectory($base, $dir)
    {
        if (empty($base)) {
            return $dir;
        }

        if (substr($base, -1) == '/') {
            return $base . $dir;
        }

        return $base . '/' . $dir;
    }

    /**
     * Generate an URL to the logout screen that includes any known
     * information, such as username, server, etc., that can be filled
     * in on the login form.
     *
     * @return string  The logout URL with parameters added.
     */
    function logoutUrl()
    {
        $params = array();
        $url = 'login.php';

        if (!empty($GLOBALS['gollem_be']['params']['username'])) {
            $params['username'] = $GLOBALS['gollem_be']['params']['username'];
        } elseif (Util::getFormData('username')) {
            $params['username'] = Util::getFormData('username');
        }

        if (!empty($GLOBALS['gollem_be']['params']['port'])) {
            $params['port'] = $GLOBALS['gollem_be']['params']['port'];
        }

        foreach ($params as $key => $val) {
            if (!empty($val)) {
                $url = Util::addParameter($url, $key, $val);
            }
        }

        return Horde::applicationUrl($url, true);
    }

    /**
     * Create a folder using the current Gollem session settings.
     *
     * @param string $dir   The directory path.
     * @param string $name  The folder to create.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function createFolder($dir, $name)
    {
        $totalpath = Gollem::realPath($dir . '/' . $name);
        if (!Gollem::verifyDir($totalpath)) {
            return PEAR::raiseError(sprintf(_("Access denied to folder \"%s\"."), $totalpath));
        }

        /* The $name parameter may contain additional directories so we
         * need to pass autocreatePath everything but the base filename. */
        $pos = strrpos($totalpath, '/');
        $dir = substr($totalpath, 0, $pos);
        $name = substr($totalpath, $pos + 1);

        /* Unfortunately, autocreatePath() is broken in VFS versions prior to
         * Horde 3.0.5.  We can work around this by simply clipping the root
         * '/' off the path. */
        require_once HORDE_BASE . '/lib/version.php';
        if ((version_compare(HORDE_VERSION, '3.0.5') == -1) &&
            (strpos($dir, '/') === 0)) {
            $autodir = substr($dir, 1);
        } else {
            $autodir = $dir;
        }
        $res = $GLOBALS['gollem_vfs']->autocreatePath($autodir);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $GLOBALS['gollem_vfs']->createFolder($dir, $name);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (!empty($GLOBALS['gollem_be']['params']['permissions'])) {
            $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $GLOBALS['gollem_be']['params']['permissions']);
        }

        return true;
    }

    /**
     * Rename files using the current Gollem session settings.
     *
     * @param string $oldDir  Old directory name.
     * @param string $old     Old file name.
     * @param string $newDir  New directory name.
     * @param string $old     New file name.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function renameItem($oldDir, $old, $newDir, $new)
    {
        return $GLOBALS['gollem_vfs']->rename($oldDir, $old, $newDir, $new);
    }

    /**
     * Delete a folder using the current Gollem session settings.
     *
     * @param string $dir   The subdirectory name.
     * @param string $name  The folder name to delete.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($dir, $name)
    {
        if (!Gollem::verifyDir($dir)) {
            return PEAR::raiseError(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        if ($GLOBALS['prefs']->getValue('recursive_deletes') != 'disabled') {
            return $GLOBALS['gollem_vfs']->deleteFolder($dir, $name, true);
        }
        else {
            return $GLOBALS['gollem_vfs']->deleteFolder($dir, $name, false);
        }
    }

    /**
     * Delete a file using the current Gollem session settings.
     *
     * @param string $dir   The directory name.
     * @param string $name  The filename to delete.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFile($dir, $name)
    {
        if (!Gollem::verifyDir($dir)) {
            return PEAR::raiseError(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        return $GLOBALS['gollem_vfs']->deleteFile($dir, $name);
    }

    /**
     * Change permissions on files using the current Gollem session settings.
     *
     * @param string $dir         The directory name.
     * @param string $name        The filename to change permissions on.
     * @param string $permission  The permission mode to set.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function changePermissions($dir, $name, $permission)
    {
        if (!Gollem::verifyDir($dir)) {
            return PEAR::raiseError(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        return $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $permission);
    }

    /**
     * Write an uploaded file to the VFS backend.
     *
     * @param string $dir       The directory name.
     * @param string $name      The filename to create.
     * @param string $filename  The local file containing the file data.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function writeFile($dir, $name, $filename)
    {
        $res = $GLOBALS['gollem_vfs']->write($dir, $name, $filename);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (!empty($GLOBALS['gollem_be']['params']['permissions'])) {
            $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $GLOBALS['gollem_be']['params']['permissions']);
        }

        return true;
    }

    /**
     * Moves a file using the current Gollem session settings.
     *
     * @param string $backend_f The backend to move the file from.
     * @param string $dir       The directory name of the original file.
     * @param string $name      The original filename.
     * @param string $backend_t The backend to move the file to.
     * @param string $newdir    The directory to move the file to.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function moveFile($backend_f, $dir, $name, $backend_t, $newdir)
    {
        return Gollem::_copyFile('move', $backend_f, $dir, $name, $backend_t, $newdir);
    }

    /**
     * Copies a file using the current Gollem session settings.
     *
     * @param string $backend_f The backend to copy the file from.
     * @param string $dir       The directory name of the original file.
     * @param string $name      The original filename.
     * @param string $backend_t The backend to copy the file to.
     * @param string $newdir    The directory to copy the file to.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function copyFile($backend_f, $dir, $name, $backend_t, $newdir)
    {
        return Gollem::_copyFile('copy', $backend_f, $dir, $name, $backend_t, $newdir);
    }

    /**
     * Private function that copies/moves files.
     *
     * @access private
     */
    function _copyFile($mode, $backend_f, $dir, $name, $backend_t, $newdir)
    {
        /* If the from/to backends are the same, we can just use the built-in
           VFS functions. */
        if ($backend_f == $backend_t) {
            if ($backend_f == $_SESSION['gollem']['backend_key']) {
                $ob = &$GLOBALS['gollem_vfs'];
            } else {
                $ob = &Gollem::getVFSOb($backend_f);
                $valid = $ob->checkCredentials();
                if (is_a($valid, 'PEAR_Error')) {
                    return $valid;
                }
            }
            return ($mode == 'copy') ? $ob->copy($dir, $name, $newdir) : $ob->move($dir, $name, $newdir);
        }

        /* Else, get the two VFS objects and copy/move the files. */
        if ($backend_f == $_SESSION['gollem']['backend_key']) {
            $from_be = &$GLOBALS['gollem_vfs'];
        } else {
            $from_be = &Gollem::getVFSOb($backend_f);
            $valid = $from_be->checkCredentials();
            if (is_a($valid, 'PEAR_Error')) {
                return $valid;
            }
        }

        if ($backend_t == $_SESSION['gollem']['backend_key']) {
            $to_be = &$GLOBALS['gollem_vfs'];
        } else {
            $from_be = &Gollem::getVFSOb($backend_t);
            $valid = $to_be->checkCredentials();
            if (is_a($valid, 'PEAR_Error')) {
                return $valid;
            }
        }

        /* Read the source data. */
        $data = $from_be->read($dir, $name);
        if (is_a($data, 'PEAR_Error')) {
            return $data;
        }

        /* Write the target data. */
        $res = $to_be->writeData($newdir, $name, $data);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        /* If moving, delete the source data. */
        if ($mode == 'move') {
            $from_be->deleteFile($dir, $name);
        }

        return true;
    }

    /**
     * Get the current preferred backend key.
     *
     * @return string  The preferred backend key.
     */
    function getPreferredBackend()
    {
        $backend_key = null;

        if (!empty($_SESSION['gollem']['backend_key'])) {
            $backend_key = $_SESSION['gollem']['backend_key'];
        } else {
            /* Determine the preferred backend. */
            foreach ($GLOBALS['gollem_backends'] as $key => $val) {
                if (empty($backend_key) && (substr($key, 0, 1) != '_')) {
                    $backend_key = $key;
                }
                if (!empty($val['preferred'])) {
                    $preferred = false;
                    if (!is_array($val['preferred'])) {
                        $val['preferred'] = array($val['preferred']);
                    }
                    foreach ($val['preferred'] as $backend) {
                        if (($backend == $_SERVER['SERVER_NAME']) ||
                            ($backend == $_SERVER['HTTP_HOST'])) {
                            $preferred = true;
                            break;
                        }
                    }
                    if ($preferred) {
                        $backend_key = $key;
                        break;
                    }
                }
            }

        }

        return $backend_key;
    }

    /**
     * This function verifies whether a given directory is below the root.
     *
     * @param string $dir  The directory to check.
     *
     * @return boolean  True if the directory is below the root.
     */
    function verifyDir($dir)
    {
        $rootdir = Gollem::getRoot();
        return (String::substr(Gollem::realPath($dir), 0, String::length($rootdir)) == $rootdir);
    }


    /**
     * Parse the 'columns' preference.
     *
     * @return array  The list of columns to be displayed.
     */
    function displayColumns()
    {
        $ret = array();
        $lines = explode("\n", $GLOBALS['prefs']->getValue('columns'));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $columns = explode("\t", $line);
                if (count($columns) > 1) {
                    $source = array_splice($columns, 0, 1);
                    $ret[$source[0]] = $columns;
                }
            }
        }

        return $ret;
    }

    /**
     * Checks if a user has the specified permissions on the selected backend.
     *
     * @param string $filter       What are we checking for.
     * @param integer $permission  What permission to check for.
     * @param string $backend      The backend to check.  If empty, check
     *                             the current backend.
     *
     * @return boolean  Returns true if the user has permission, false if
     *                  they do not.
     */
    function checkPermissions($filter, $permission = PERMS_READ,
                              $backend = null)
    {
        $userID = Auth::getAuth();
        if ($backend === null) {
            $backend = $_SESSION['gollem']['backend_key'];
        }

        switch ($filter) {
        case 'backend':
            $backendTag = 'gollem:backends:' . $backend;
            return (!$GLOBALS['perms']->exists($backendTag) ||
                    $GLOBALS['perms']->hasPermission($backendTag, $userID, $permission));
        }

        return false;
    }

    /**
     * Produces a directory link used for navigation.
     *
     * @param string $currdir  The current directory string.
     * @param string $url      The URL to link to.
     *
     * @return string  The directory navigation string.
     */
    function directoryNavLink($currdir, $url)
    {
        $label = array();
        $root_dir = Gollem::getRoot();
        $root_dir_name = $_SESSION['gollem']['backends'][$_SESSION['gollem']['backend_key']]['name'];

        if ($currdir == $root_dir) {
            $label[] = '[' . $root_dir_name . ']';
        } else {
            $parts = explode('/', $currdir);
            $parts_count = count($parts);

            $label[] = Horde::link(Util::addParameter($url, 'dir', $root_dir), sprintf(_("Up to %s"), $root_dir_name)) . '[' . $root_dir_name . ']</a>';

            for ($i = 1; $i <= $parts_count; $i++) {
                $part = array_slice($parts, 0, $i);
                $dir = implode('/', $part);
                if ((strstr($dir, $root_dir) !== false) &&
                    ($root_dir != $dir)) {
                    if ($i == $parts_count) {
                        $label[] = $parts[($i - 1)];
                    } else {
                        $label[] = Horde::link(Util::addParameter($url, 'dir', $dir), sprintf(_("Up to %s"), $dir)) . $parts[($i - 1)] . '</a>';
                    }
                }
            }
        }

        return implode('/', $label);
    }

    /**
     * Build Gollem's list of menu items.
     *
     * @param string $returnType  Either 'object' or 'string'.
     *
     * @return mixed  Either a Horde_Menu object or the rendered menu text.
     */
    function getMenu($returnType = 'object')
    {
        require_once 'Horde/Menu.php';

        $menu = new Menu();
        $menu->add(Util::addParameter(Horde::applicationUrl('manager.php'), 'dir', Gollem::getHome()), _("_My Home"), 'folder_home.png');

        if (!empty($_SESSION['gollem'])) {
            $backend_key = $_SESSION['gollem']['backend_key'];
            if (Auth::isAdmin()) {
                $menu->add(Util::addParameter(Horde::applicationUrl('permissions.php'), 'backend', $backend_key), _("_Permissions"), 'perms.png', $GLOBALS['registry']->getImageDir('horde'));
            }

            if ($_SESSION['gollem']['hasquota'] &&
                ($_SESSION['gollem']['backends'][$backend_key]['quota_val'] != -1)) {
                if ($GLOBALS['browser']->hasFeature('javascript')) {
                    Horde::addScriptFile('popup.js');
                    $quota_url = "javascript:popup_gollem('" . Horde::applicationUrl('quota.php') . "',300,300,'" . addslashes('backend=' . $backend_key) . "');";
                } else {
                    $quota_url = Util::addParameter(Horde::applicationUrl('quota.php'), 'backend', $backend_key);
                }
                $menu->add($quota_url, _("Check Quota"), 'info_icon.png', $GLOBALS['registry']->getImageDir('horde'));
            }
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Outputs Gollem's menu to the current output stream.
     *
     * @since Gollem 1.0.2
     */
    function menu()
    {
        $t = new Gollem_Template();

        $t->set('forminput', Util::formInput());
        $t->set('be_select', Gollem::backendSelect(), true);
        if ($t->get('be_select')) {
        $t->set('accesskey', $GLOBALS['prefs']->getValue('widget_accesskey') ? Horde::getAccessKey(_("_Change Server")) : '');
            $menu_view = $GLOBALS['prefs']->getValue('menu_view');
            $link = Horde::link('#', _("Change Server"), '', '', 'serverSubmit(true);return false;');
            $t->set('slink', sprintf('<ul><li>%s%s<br />%s</a></li></ul>', $link, ($menu_view != 'text') ? Horde::img('gollem.png') : '', ($menu_view != 'icon') ? Horde::highlightAccessKey(_("_Change Server"), $t->get('accesskey')) : ''));
        }
        $t->set('menu_string', Gollem::getMenu('string'));

        echo $t->fetch(GOLLEM_TEMPLATES . '/menu.html');
    }

    /**
     * Outputs Gollem's status/notification bar.
     */
    function status()
    {
        $GLOBALS['notification']->notify(array('listeners' => 'status'));
    }

    /**
     * Generate the backend selection list for use in the menu.
     *
     * @return string  The backend selection list.
     */
    function backendSelect()
    {
        $text = '';

        if (($GLOBALS['conf']['backend']['backend_list'] == 'shown') &&
            (count($GLOBALS['gollem_backends']) > 1)) {
            foreach ($GLOBALS['gollem_backends'] as $key => $val) {
                $sel = ($_SESSION['gollem']['backend_key'] == $key) ? ' selected="selected"' : '';
                $text .= sprintf('<option value="%s"%s>%s</option>%s', (empty($sel)) ? $key : '', $sel, $val['name'], "\n");
            }
        }

        return $text;
    }

    /**
     * Load the backends list into the global $gollem_backends variable.
     */
    function loadBackendList()
    {
        if (!empty($_SESSION['gollem']['be_list'])) {
            $GLOBALS['gollem_backends'] = $_SESSION['gollem']['be_list'];
        } else {
            require GOLLEM_BASE . '/config/backends.php';
            $GLOBALS['gollem_backends'] = array();
            foreach ($backends as $key => $val) {
                if (Gollem::checkPermissions('backend', PERMS_SHOW, $key)) {
                    $GLOBALS['gollem_backends'][$key] = $val;
                }
            }
        }
    }

    /**
     * Get the authentication ID to use for autologins based on the value of
     * the 'hordeauth' parameter.
     *
     * @param string $backend  The backend to login to.
     *
     * @return string  The ID string to use for logins.
     */
    function getAutologinID($backend)
    {
        if (!empty($GLOBALS['gollem_backends'][$backend]['hordeauth']) &&
            (strcasecmp($GLOBALS['gollem_backends'][$backend]['hordeauth'], 'full') === 0)) {
            return Auth::getAuth();
        } else {
            return Auth::getBareAuth();
        }
    }

    /**
     * Returns the canonical path of the string.  Like PHP's built-in
     * realpath() except the directory need not exist on the local server.
     *
     * Algorithim loosely based on code from the Perl File::Spec::Unix module
     * (version 1.5).
     *
     * TODO: Remove once Gollem requires a minimum version of Horde that
     * containts Util::realPath().
     *
     * @param string $path  A file path.
     *
     * @return string  The canonicalized file path.
     */
    function realPath($path)
    {
        /* Standardize on UNIX directory separators. */
        if (!strncasecmp(PHP_OS, 'WIN', 3)) {
            $path = str_replace('\\', '/', $path);
        }

        /* xx////xx -> xx/xx
         * xx/././xx -> xx/xx */
        $path = preg_replace(array("|/+|", "@(/\.)+(/|\Z(?!\n))@"), array('/', '/'), $path);

        /* ./xx -> xx */
        if ($path != './') {
            $path = preg_replace("|^(\./)+|", '', $path);
        }

        /* /../../xx -> xx */
        $path = preg_replace("|^/(\.\./?)+|", '/', $path);

        /* xx/ -> xx */
        if ($path != '/') {
            $path = preg_replace("|/\Z(?!\n)|", '', $path);
        }

        /* /xx/.. -> / */
        while (strpos($path, '/..') !== false) {
            $path = preg_replace("|/[^/]+/\.\.|", '', $path);
        }

        return empty($path) ? '/' : $path;
    }

    /**
     * Return a Horde_VFS object for the given backend.
     *
     * @param string $backend_key  The backend_key VFS object to return.
     *
     * @return object  The Horde_VFS object requested.
     */
    function &getVFSOb($backend_key, $params = array())
    {
        if (isset($_SESSION['gollem']['backends'][$backend_key])) {
            $be_config = &$_SESSION['gollem']['backends'][$backend_key];
        } else {
            $be_config = $GLOBALS['gollem_backends'][$backend_key];
        }
        if (!count($params)) {
            $params = $be_config['params'];
            if (!empty($params['password'])) {
                $params['password'] = Secret::read(Secret::getKey('gollem'), $params['password']);
            }
        }

        // Create VFS object
        $ob = &VFS::singleton($be_config['driver'], $params);
        if (is_a($ob, 'PEAR_Error')) {
            return $ob;
        }

        // Enable logging within VFS
        $logger = &Horde::getLogger();
        if ($logger !== false) {
            // TODO: No need to do this check as of Horde 3.2
            if (!is_a($logger, 'Log')) {
                Horde::fatal(PEAR::raiseError('An error has occurred. Furthermore, Horde encountered an error attempting to log this error. Please check your Horde logging configuration in horde/config/conf.php.'), __FILE__, __LINE__, false);
            }
            $ob->setLogger($logger, $GLOBALS['conf']['log']['priority']);
        }

        if (!isset($be_config['quota_val'])) {
            /* Only set quota information when we are finished with session
             * initialization (a VFS object is created in
             * Gollem_Session::createSession before all session variables -
             * e.g. 'hasquota' - are set.) */
            $sess_setup = isset($_SESSION['gollem']['hasquota']);
            if ($sess_setup) {
                $be_config['quota_val'] = -1;
            }
            if ((!$sess_setup || !empty($_SESSION['gollem']['hasquota'])) &&
                !empty($be_config['quota'])) {
                $quota_metric = array(
                    'B' => VFS_QUOTA_METRIC_BYTE,
                    'KB' => VFS_QUOTA_METRIC_KB,
                    'MB' => VFS_QUOTA_METRIC_MB,
                    'GB' => VFS_QUOTA_METRIC_GB
                );
                $quota_str = explode(' ', $be_config['quota'], 2);
                if (is_numeric($quota_str[0])) {
                    $metric = trim(strtoupper($quota_str[1]));
                    if (!isset($quota_metric[$metric])) {
                        $metric = 'B';
                    }
                    $ob->setQuota($quota_str[0], $quota_metric[$metric]);
                    $ob->setQuotaRoot(Gollem::getRoot());
                    if ($sess_setup) {
                        $be_config['quota_val'] = $quota_str[0];
                        $be_config['quota_metric'] = $quota_metric[$metric];
                    }
                }
            }
        } elseif ($be_config['quota_val'] > -1) {
            $ob->setQuota($be_config['quota_val'], $be_config['quota_metric']);
            $ob->setQuotaRoot($be_config['root']);
        }

        return $ob;
    }

    /**
     * Generate the display path (the path with any root information stripped
     * out).
     *
     * @param string $path  The path to display.
     *
     * @return string  The display path.
     */
    function getDisplayPath($path)
    {
        $path = Gollem::realPath($path);
        $rootdir = Gollem::getRoot();
        if (($rootdir != '/') && (strpos($path, $rootdir) === 0)) {
            $path = substr($path, String::length($rootdir));
        }
        return $path;
    }


    /**
     * Get a list of the available backends for permissions setup.
     *
     * @param string $perms  'all' - Return all backends.
     *                       'perms' - Return backends which have perms set.
     *                       'noperms' - Return backends which have no perms
     *                                   set.
     *
     * @return array  The requested backend list.
     */
    function &getBackends($perms = 'all')
    {
        $backends = $_SESSION['gollem']['backends'];
        $perms = strtolower($perms);

        if ($perms != 'all') {
            foreach (array_keys($backends) as $key) {
                $exists = $GLOBALS['perms']->exists('gollem:backends:' . $key);
                /* Don't list if the perms don't exist for this backend and we
                 * want backends with perms only OR if the perms exist for
                 * this backend and we only want backends which have none. */
                if ((!$exists && ($perms == 'perms')) ||
                    ($exists && ($perms == 'noperms'))) {
                    unset($backends[$key]);
                }
            }
        }

        return $backends;
    }

    /**
     * Add inline javascript to the output buffer.
     *
     * @since Gollem 1.1
     *
     * @param string $script  The script text to add.
     *
     * @return string  The javascript text to output, or empty if the page
     *                 headers have not yet been sent.
     */
    function addInlineScript($script)
    {
        $script = trim($script);
        if (empty($script)) {
            return;
        }

        if (!isset($GLOBALS['__gollem_inline_script'])) {
            $GLOBALS['__gollem_inline_script'] = array();
        }
        $GLOBALS['__gollem_inline_script'][] = $script;

        // If headers have already been sent, we need to output a
        // <script> tag directly.
        if (ob_get_length() || headers_sent()) {
            Gollem::outputInlineScript();
        }
    }

    /**
     * Print inline javascript to the output buffer.
     *
     * @since Gollem 1.1
     *
     * @return string  The javascript text to output.
     */
    function outputInlineScript()
    {
        if (!empty($GLOBALS['__gollem_inline_script'])) {
            echo '<script type="text/javascript">//<![CDATA[' . "\n";
            foreach ($GLOBALS['__gollem_inline_script'] as $val) {
                echo $val . "\n";
            }
            echo "//]]></script>\n";
        }

        $GLOBALS['__gollem_inline_script'] = array();
    }

    /**
     * Cleans a path presented to Gollem's browse API call.
     *
     * This will remove:
     * - leading '/'
     * - leading 'gollem'
     * - trailing '/'
     * The desired end result is the path including VFS backend.
     *
     * @param string $path  Path as presented to Gollem API.
     *
     * @return string  Cleaned path as described above.
     */
    function stripAPIPath($path)
    {
        // Strip leading '/'
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        // Remove 'gollem' from path
        if (substr($path, 0, 6) == 'gollem') {
            $path = substr($path, 6);
        }
        // Remove leading '/'
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        // Remove trailing '/'
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }

    /**
     * Convert a Gollem path into a URL encoded string, but keep '/'.
     * This allows for proper PATH_INFO path parsing.
     * Special care is taken to handle "+" and " ".
     *
     * @since Gollem 1.1
     *
     * @param string $path  Path to be urlencode()d.
     *
     * @return string  URL-encoded string with '/' preserved.
     */
    function pathEncode($path)
    {
         // TODO: Must use str_replace here instead of str_ireplace which is
         // PHP5-only.
         return str_replace(array('%2F', '%2f'), '/', rawurlencode($path));
     }

     /**
      * Take a fully qualified and break off the file or directory name.
      * This pair is used for the input to many VFS library functions.
      *
      * @param string $fullpath   Path to be split.
      *
      * @return array  Array of ($path, $name)
      */
     function getVFSPath($fullpath)
     {
        // Convert the path into VFS's ($path, $name) convention
        $i = strrpos($fullpath, '/');
        if ($i !== false) {
            $path = substr($fullpath, 0, $i);
            $name = substr($fullpath, $i + 1);
        } else {
            $name = $fullpath;
            $path = '';
        }
        return array($name, $path);
     }
}
