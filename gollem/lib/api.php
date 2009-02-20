<?php
/**
 * Gollem external API interface.
 *
 * This file defines Gollem's external API interface. Other
 * applications can interact with Gollem through this API.
 *
 * $Horde: gollem/lib/api.php,v 1.14.2.5 2008/10/09 20:54:42 jan Exp $
 *
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
 * @package Gollem
 */

$_services['browse'] = array(
    'args' => array('path' => 'string'),
    'type' => '{urn:horde}hashHash',
);

$_services['put'] = array(
    'args' => array('path' => 'string', 'content' => 'string', 'content_type' => 'string'),
    'type' => 'int',
);

$_services['mkcol'] = array(
    'args' => array('path' => 'string'),
    'type' => 'int',
);

$_services['move'] = array(
    'args' => array('path' => 'string', 'dest' => 'string'),
    'type' => 'int',
);

$_services['path_delete'] = array(
    'args' => array('path' => 'string'),
    'type' => 'int',
);

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');

$_services['selectlistLink'] = array(
    'args' => array('link_text' => 'string', 'link_style' => 'string', 'formid' => 'string', 'icon' => 'boolean', 'selectid' => 'string'),
    'type' => 'string');

$_services['selectlistResults'] = array(
    'args' => array('selectid' => 'string'),
    'type' => 'array');

$_services['returnFromSelectlist'] = array(
    'args' => array('selectid' => 'string', 'index' => 'string'),
    'type' => 'string');

$_services['setSelectList'] = array(
    'args' => array('selectid' => 'string', 'files' => 'array'),
    'type' => 'string');

$_services['getViewLink'] = array(
    'args' => array('dir' => 'string', 'file' => 'string', 'backend' => 'string'),
    'type' => 'string');

/**
 * Browses through the VFS tree.
 *
 * Each VFS backend is listed as a directory at the top level.  No modify
 * operations are allowed outside any VFS area.
 *
 * @since Gollem 1.1
 *
 * @param string $path       The level of the tree to browse.
 * @param array $properties  The item properties to return. Defaults to 'name',
 *                           'icon', and 'browseable'.
 *
 * @return array  The contents of $path.
 */
function _gollem_browse($path = '', $properties = array())
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    $GLOBALS['authentication'] = 'none';
    require_once GOLLEM_BASE . '/lib/base.php';
    require_once GOLLEM_BASE . '/lib/Session.php';
    require GOLLEM_BASE . '/config/backends.php';
    require GOLLEM_BASE . '/config/credentials.php';

    $path = Gollem::stripAPIPath($path);

    // Default properties.
    if (!$properties) {
        $properties = array('name', 'icon', 'browseable');
    }

    $results = array();
    if ($path == '') {
        // We are at the root of gollem.  Return a set of folders, one for
        // each backend available.
        foreach ($backends as $backend => $curBackend) {
            if (Gollem::checkPermissions('backend', PERMS_SHOW, $backend)) {
                $results['gollem/' . $backend]['name'] = $curBackend['name'];
                $results['gollem/' . $backend]['browseable'] = true;
            }
        }
    } else {
        // A file or directory has been requested.

        // Locate the backend_key in the path.
        if (strchr($path, '/')) {
            $backend_key = substr($path, 0, strpos($path, '/'));
        } else {
            $backend_key = $path;
        }

        // Validate and perform permissions checks on the requested backend
        if (!isset($backends[$backend_key])) {
            return PEAR::raiseError(sprintf(_("Invalid backend requested: %s"), $backend_key));
        }
        //if (!Gollem::canAutoLogin($backend_key)) {
        //    // FIXME: Is it possible to request secondary authentication
        //    // credentials here for backends that require it?
        //    return PEAR::raiseError(_("Additional authentication required."));
        //}
        if (!Gollem_Session::createSession($backend_key)) {
            return PEAR::raiseError(_("Unable to create Gollem session"));
        }
        if (!Gollem::checkPermissions('backend', PERMS_READ)) {
            return PEAR::raiseError(_("Permission denied to this backend."));
        }

        // Trim off the backend_key (and '/') to get the VFS relative path
        $fullpath = substr($path, strlen($backend_key) + 1);

        // Get the VFS-standard $name,$path pair
        list($name, $path) = Gollem::getVFSPath($fullpath);

        // Check to see if the request is a file or folder
        if ($GLOBALS['gollem_vfs']->isFolder($path, $name)) {
            // This is a folder request.  Return a directory listing.
            $list = Gollem::listFolder($path . '/' . $name);
            if (is_a($list, 'PEAR_Error')) {
                return $list;
            }

            // Iterate over the directory contents
            if (is_array($list) && count($list)) {
                $index = 'gollem/' . $backend_key . '/' . $fullpath;
                foreach ($list as $key => $val) {
                    $entry = Gollem::pathEncode($index . '/' . $val['name']);
                    $results[$entry]['name'] = $val['name'];
                    $results[$entry]['modified'] = $val['date'];
                    if ($val['type'] == '**dir') {
                        $results[$entry]['browseable'] = true;
                    } else {
                        $results[$entry]['browseable'] = false;
                        $results[$entry]['contentlength'] = $val['size'];
                    }
                }
            }
        } else {
            // A file has been requested.  Return the contents of the file.

            // Get the file meta-data
            $list = Gollem::listFolder($path);
            $i = false;
            foreach ($list as $key => $file) {
                if ($file['name'] == $name) {
                    $i = $key;
                    break;
                }
            }
            if ($i === false) {
                // File not found
                return $i;
            }

            // Read the file contents
            $data = $GLOBALS['gollem_vfs']->read($path, $name);
            if (is_a($data, 'PEAR_Error')) {
                return false;
            }

            // Send the file
            $results['name'] = $name;
            $results['data'] = $data;
            $results['contentlength'] = $list[$i]['size'];
            $results['mtime'] = $list[$i]['date'];
        }
    }

    return $results;
}

/**
 * Accepts a file for storage into the VFS
 *
 * @since Gollem 1.1
 *
 * @param string $path           Path to store file
 * @param string $content        Contents of file
 * @param string $content_type   MIME type of file
 *
 * @return mixed                 True on success; PEAR_Error on failure
 */
function _gollem_put($path, $content, $content_type)
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    // Gollem does not handle authentication
    $GLOBALS['authentication'] = 'none';

    // Include Gollem base libraries
    require_once GOLLEM_BASE . '/lib/base.php';
    require_once GOLLEM_BASE . '/lib/Session.php';
    require GOLLEM_BASE . '/config/backends.php';
    require GOLLEM_BASE . '/config/credentials.php';

    // Clean off the irrelevant portions of the path
    $path = Gollem::stripAPIPath($path);

    if ($path == '') {
        // We are at the root of gollem.  Any writes at this level are
        // disallowed.
        return PEAR::raiseError(_("Files must be written inside a VFS backend."));
    } else {
        // We must be inside one of the VFS areas.  Determine which one.
         // Locate the backend_key in the path
        if (strchr($path, '/')) {
            $backend_key = substr($path, 0, strpos($path, '/'));
        } else {
            $backend_key = $path;
        }

        // Validate and perform permissions checks on the requested backend
        if (!isset($backends[$backend_key])) {
            return PEAR::raiseError(sprintf(_("Invalid backend requested: %s"), $backend_key));
        }
        //if (!Gollem::canAutoLogin($backend_key)) {
        //    // FIXME: Is it possible to request secondary authentication
        //    // credentials here for backends that require it?
        //    return PEAR::raiseError(_("Additional authentication required."));
        //}
        if (!Gollem_Session::createSession($backend_key)) {
            return PEAR::raiseError(_("Unable to create Gollem session"));
        }
        if (!Gollem::checkPermissions('backend', PERMS_EDIT)) {
            return PEAR::raiseError(_("Permission denied to this backend."));
        }

        // Trim off the backend_key (and '/') to get the VFS relative path
        $fullpath = substr($path, strlen($backend_key) + 1);

        // Get the VFS-standard $name,$path pair
        list($name, $path) = Gollem::getVFSPath($fullpath);

        return $GLOBALS['gollem_vfs']->writeData($path, $name, $content);
    }
}

/**
 * Creates a directory ("collection" in WebDAV-speak) within the VFS
 *
 * @since Gollem 1.1
 *
 * @param string $path           Path of directory to create
 *
 * @return mixed                 True on success; PEAR_Error on failure
 */
function _gollem_mkcol($path)
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    // Gollem does not handle authentication
    $GLOBALS['authentication'] = 'none';

    // Include Gollem base libraries
    require_once GOLLEM_BASE . '/lib/base.php';
    require_once GOLLEM_BASE . '/lib/Session.php';
    require GOLLEM_BASE . '/config/backends.php';
    require GOLLEM_BASE . '/config/credentials.php';

    // Clean off the irrelevant portions of the path
    $path = Gollem::stripAPIPath($path);

    if ($path == '') {
        // We are at the root of gollem.  Any writes at this level are
        // disallowed.
        return PEAR::raiseError(_('Folders must be created inside a VFS backend.'));
    } else {
        // We must be inside one of the VFS areas.  Determine which one.
        // Locate the backend_key in the path
        if (!strchr($path, '/')) {
            // Disallow attempts to create a share-level directory.  
            return PEAR::raiseError(_('Folders must be created inside a VFS backend.'));
        } else {
            $backend_key = substr($path, 0, strpos($path, '/'));
        }

        // Validate and perform permissions checks on the requested backend
        if (!isset($backends[$backend_key])) {
            return PEAR::raiseError(sprintf(_("Invalid backend requested: %s"), $backend_key));
        }
        //if (!Gollem::canAutoLogin($backend_key)) {
        //    // FIXME: Is it possible to request secondary authentication
        //    // credentials here for backends that require it?
        //    return PEAR::raiseError(_("Additional authentication required."));
        //}
        if (!Gollem_Session::createSession($backend_key)) {
            return PEAR::raiseError(_("Unable to create Gollem session"));
        }
        if (!Gollem::checkPermissions('backend', PERMS_EDIT)) {
            return PEAR::raiseError(_("Permission denied to this backend."));
        }

        // Trim off the backend_key (and '/') to get the VFS relative path
        $fullpath = substr($path, strlen($backend_key) + 1);

        // Get the VFS-standard $name,$path pair
        list($name, $path) = Gollem::getVFSPath($fullpath);

        return $GLOBALS['gollem_vfs']->createFolder($path, $name);
    }
}

/**
 * Renames a file or directory
 *
 * @since Gollem 1.1
 *
 * @param string $path           Path to source object to be renamed
 * @param string $dest           Path to new name
 *
 * @return mixed                 True on success; PEAR_Error on failure
 */
function _gollem_move($path, $dest)
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    // Gollem does not handle authentication
    $GLOBALS['authentication'] = 'none';

    // Include Gollem base libraries
    require_once GOLLEM_BASE . '/lib/base.php';
    require_once GOLLEM_BASE . '/lib/Session.php';
    require GOLLEM_BASE . '/config/backends.php';
    require GOLLEM_BASE . '/config/credentials.php';

    // Clean off the irrelevant portions of the path
    $path = Gollem::stripAPIPath($path);
    $dest = Gollem::stripAPIPath($dest);

    if ($path == '') {
        // We are at the root of gollem.  Any writes at this level are
        // disallowed.
        return PEAR::raiseError(_('Folders must be created inside a VFS backend.'));
    } else {
        // We must be inside one of the VFS areas.  Determine which one.
        // Locate the backend_key in the path
        if (!strchr($path, '/')) {
            // Disallow attempts to rename a share-level directory.  
            return PEAR::raiseError(_('Renaming of backends is not allowed.'));
        } else {
            $backend_key = substr($path, 0, strpos($path, '/'));
        }

        // Ensure that the destination is within the same backend
        if (!strchr($dest, '/')) {
            // Disallow attempts to rename a share-level directory.  
            return PEAR::raiseError(_('Renaming of backends is not allowed.'));
        } else {
            $dest_backend_key = substr($path, 0, strpos($path, '/'));
            if ($dest_backend_key != $backend_key) {
                return PEAR::raiseError(_('Renaming across backends is not supported.'));
            }
        }

        // Validate and perform permissions checks on the requested backend
        if (!isset($backends[$backend_key])) {
            return PEAR::raiseError(sprintf(_("Invalid backend requested: %s"), $backend_key));
        }
        //if (!Gollem::canAutoLogin($backend_key)) {
        //    // FIXME: Is it possible to request secondary authentication
        //    // credentials here for backends that require it?
        //    return PEAR::raiseError(_("Additional authentication required."));
        //}
        if (!Gollem_Session::createSession($backend_key)) {
            return PEAR::raiseError(_("Unable to create Gollem session"));
        }
        if (!Gollem::checkPermissions('backend', PERMS_EDIT)) {
            return PEAR::raiseError(_("Permission denied to this backend."));
        }

        // Trim off the backend_key (and '/') to get the VFS relative path
        $srcfullpath = substr($path, strlen($backend_key) + 1);
        $dstfullpath = substr($dest, strlen($backend_key) + 1);

        // Get the VFS-standard $name,$path pair
        list($srcname, $srcpath) = Gollem::getVFSPath($srcfullpath);
        list($dstname, $dstpath) = Gollem::getVFSPath($dstfullpath);

        return $GLOBALS['gollem_vfs']->rename($srcpath, $srcname, $dstpath, $dstname);
    }
}

/**
 * Removes a file or folder from the VFS
 *
 * @since Gollem 1.1
 *
 * @param string $path           Path of file or folder to delete
 *
 * @return mixed                 True on success; PEAR_Error on failure
 */
function _gollem_path_delete($path)
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    // Gollem does not handle authentication
    $GLOBALS['authentication'] = 'none';

    // Include Gollem base libraries
    require_once GOLLEM_BASE . '/lib/base.php';
    require_once GOLLEM_BASE . '/lib/Session.php';
    require GOLLEM_BASE . '/config/backends.php';
    require GOLLEM_BASE . '/config/credentials.php';

    // Clean off the irrelevant portions of the path
    $path = Gollem::stripAPIPath($path);

    if ($path == '') {
        // We are at the root of gollem.  Any writes at this level are
        // disallowed.
        return PEAR::raiseError(_("The application folder can not be deleted."));
    } else {
        // We must be inside one of the VFS areas.  Determine which one.
        // Locate the backend_key in the path
        if (strchr($path, '/')) {
            $backend_key = substr($path, 0, strpos($path, '/'));
        } else {
            $backend_key = $path;
        }

        // Validate and perform permissions checks on the requested backend
        if (!isset($backends[$backend_key])) {
            return PEAR::raiseError(sprintf(_("Invalid backend requested: %s"), $backend_key));
        }
        //if (!Gollem::canAutoLogin($backend_key)) {
        //    // FIXME: Is it possible to request secondary authentication
        //    // credentials here for backends that require it?
        //    return PEAR::raiseError(_("Additional authentication required."));
        //}
        if (!Gollem_Session::createSession($backend_key)) {
            return PEAR::raiseError(_("Unable to create Gollem session"));
        }
        if (!Gollem::checkPermissions('backend', PERMS_EDIT)) {
            return PEAR::raiseError(_("Permission denied to this backend."));
        }

        // Trim off the backend_key (and '/') to get the VFS relative path
        $fullpath = substr($path, strlen($backend_key) + 1);

        // Get the VFS-standard $name,$path pair
        list($name, $path) = Gollem::getVFSPath($fullpath);

        // Apparently Gollem::verifyDir() (called by deleteF* next) needs to
        // see a path with a leading '/' 
        $path = $backends[$backend_key]['root'] . $path;
        if ($GLOBALS['gollem_vfs']->isFolder($path, $name)) {
            return Gollem::deleteFolder($path, $name);
        } else {
            return Gollem::deleteFile($path, $name);
        }
    }
}

function _gollem_perms()
{
    static $perms = array();
    if (!empty($perms)) {
        return $perms;
    }

    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    require GOLLEM_BASE . '/config/backends.php';

    $perms['tree']['gollem']['backends'] = false;
    $perms['title']['gollem:backends'] = _("Backends");

    // Run through every backend.
    foreach ($backends as $backend => $curBackend) {
        $perms['tree']['gollem']['backends'][$backend] = false;
        $perms['title']['gollem:backends:' . $backend] = $curBackend['name'];
    }

    return $perms;
}

/**
 * Returns a link to the gollem file preview interface
 *
 * @since Gollem 1.1
 *
 * @param string $dir       File absolute path
 * @param string $file      File basename
 * @param string $backend   Backend key. Defaults to Gollem::getPreferredBackend()
 *
 * @return string  The URL string.
 */
function _gollem_getViewLink($dir, $file, $backend = '')
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    require_once GOLLEM_BASE . '/lib/base.php';

    if (empty($backend)) {
        $backend = Gollem::getPreferredBackend();
    }

    $url = Util::addParameter(
        Horde::applicationUrl('view.php'),
        array('actionID' => 'view_file',
              'type' => substr($file, strrpos($file, '.') + 1),
              'file' => $file,
              'dir' => $dir,
              'driver' => $_SESSION['gollem']['backends'][$backend]['driver']));

    return $url;
}

/**
 * Creates a link to the gollem file selection window.
 *
 * The file section window will return a cache ID value which should be used
 * (along with the selectListResults and returnFromSelectList functions below)
 * to obtain the data from a list of selected files.
 *
 * There MUST be a form field named 'selectlist_selectid' in the calling
 * form. This field will be populated with the selection ID when the user
 * completes file selection.
 *
 * There MUST be a form parameter named 'actionID' in the calling form.
 * This form will be populated with the value 'selectlist_process' when
 * the user completes file selection.  The calling form will be submitted
 * after the window closes (i.e. the calling form must process the
 * 'selectlist_process' actionID).
 *
 * @param string $link_text   The text to use in the link.
 * @param string $link_style  The style to use for the link.
 * @param string $formid      The formid of the calling script.
 * @param boolean $icon       Create the link with an icon instead of text?
 * @param string $selectid    Selection ID.
 *
 * @return string  The URL string.
 */
function _gollem_selectlistLink($link_text, $link_style, $formid,
                                $icon = false, $selectid = '')
{
    Horde::addScriptFile('popup.js', 'gollem');
    $link = Horde::link('#', $link_text, $link_style, '_blank', "popup_gollem('" . Horde::applicationUrl(Util::addParameter('selectlist.php', array('formid' => $formid, 'cacheid' => $selectid))) . "', 300, 500); return false;");
    if ($icon) {
        $link_text = Horde::img('gollem.png', $link_text);
    }
    return '<script type="text/javascript">document.write(\''
        . addslashes($link . $link_text) . '<\' + \'/a>\');</script>';
}

/**
 * Returns the list of files selected by the user for a given selection ID.
 *
 * @param string $selectid  The selection ID.
 *
 * @param array  An array with each file entry stored in its own array, with
 *               the key as the directory name and the value as the filename.
 */
function _gollem_selectlistResults($selectid)
{
    if (!isset($_SESSION['gollem']['selectlist'][$selectid]['files'])) {
        return null;
    } else {
        $list = array();
        foreach ($_SESSION['gollem']['selectlist'][$selectid]['files'] as $val) {
            list($dir, $filename) = explode('|', $val);
            $list[] = array($dir => $filename);
        }
        return $list;
    }
}

/**
 * Returns the data for a given selection ID and index.
 *
 * @param string $selectid  The selection ID.
 * @param integer $index    The index of the file data to return.
 *
 * @return string  The file data.
 */
function _gollem_returnFromSelectlist($selectid, $index)
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    require_once GOLLEM_BASE . '/lib/base.php';

    if (!isset($_SESSION['gollem']['selectlist'][$selectid]['files'][$index])) {
        return null;
    }

    list($dir, $filename) = explode('|', $_SESSION['gollem']['selectlist'][$selectid]['files'][$index]);
    return $GLOBALS['gollem_vfs']->read($dir, $filename);
}

/**
 * Sets the files selected for a given selection ID.
 *
 * @param string $selectid  The selection ID to use.
 * @param array $files      An array with each file entry stored in its own
 *                          array, with the key as the directory name and the
 *                          value as the filename.
 *
 * @return string  The selection ID.
 */
function _gollem_setSelectlist($selectid = '', $files = array())
{
    @define('GOLLEM_BASE', dirname(__FILE__) . '/..');
    require_once GOLLEM_BASE . '/lib/base.php';

    if (empty($selectid)) {
        $selectid = uniqid(mt_rand(), true);
    }

    if (count($files) > 0) {
        $list = array();
        foreach ($files as $file) {
            $list[] = key($file) . '|' . current($file);
        }
        $_SESSION['gollem']['selectlist'][$selectid]['files'] = $list;
    }

    return $selectid;
}