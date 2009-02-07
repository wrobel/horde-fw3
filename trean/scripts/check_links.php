#!/usr/bin/php
<?php
/**
 * $Horde: trean/scripts/check_links.php,v 1.29 2008/01/02 11:14:02 jan Exp $
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Ben Chavet <ben@horde.org>
 */

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Find the base file path of Trean.
@define('TREAN_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_CLI::init();

// Now load the Registry and setup conf, etc.
$registry = &Registry::singleton();
$registry->pushApp('trean', false);

// Include needed libraries.
require_once TREAN_BASE . '/lib/Trean.php';
require_once TREAN_BASE . '/lib/Bookmarks.php';
require_once 'VFS.php';

// Create Trean objects.
$trean_db = Trean::getDb();
$trean_shares = new Trean_Bookmarks();

// Initialize VFS
$vfs_params = Horde::getVFSConfig('favicons');
if (!is_a($vfs_params, 'PEAR_Error')) {
    $vfs = &VFS::singleton($vfs_params['type'], $vfs_params['params']);
}

/**
 */
function _getHeaders($url, $format = 0)
{
    $url_info = @parse_url($url);
    $port = isset($url_info['port']) ? $url_info['port'] : 80;
    $fp = @fsockopen($url_info['host'], $port, $errno, $errstr, 30);

    if ($fp) {
        // Generate HTTP/1.0 HEAD request.
        $head = 'HEAD ' .
            (empty($url_info['path']) ? '/' : $url_info['path']) .
            (empty($url_info['query']) ? '' : '?' . $url_info['query']) .
            " HTTP/1.0\r\nHost: " . $url_info['host'] . "\r\n\r\n";

        $headers = array();
        fputs($fp, $head);

        stream_set_timeout($fp, 10);
        while (!feof($fp)) {
            $info = stream_get_meta_data($fp);
            if ($info['timed_out']) {
                return false;
            }
            if ($header = trim(fgets($fp, 1024))) {
                if ($format == 1) {
                    $tmp = explode(':', $header);
                    $key = array_shift($tmp);
                    if ($key == $header) {
                        $headers[] = $header;
                    } else {
                        $headers[$key] = substr($header, strlen($key) + 2);
                    }
                } else {
                    $headers[] = $header;
                }
            }
        }
        return $headers;
    } else {
        return false;
    }
}

/**
 * get_favicon html parsing helper function
 */
function startElement($parser, $name, $attrs)
{
    global $favicon;

    if (strtoupper($name) == 'LINK' && is_array($attrs)) {
        $use = false;
        $href = '';
        foreach ($attrs as $key => $val) {
            if (strtoupper($key) == 'REL' &&
                (strtoupper($val) == 'SHORTCUT ICON' || strtoupper($val) == 'ICON')) {
                $use = true;
            }
            if (strtoupper($key) == 'HREF') {
                $href = $val;
            }
        }
        if ($use && $href) {
            $favicon = $href;
        }
    }
}

/**
 * get_favicon html parsing helper function
 */
function endElement($parser, $name)
{
}

/**
 * Attempts to retrieve a favicon for the given bookmark.  If
 * successful, the favicon is stored in the vfs for later use.
 */
function get_favicon($bookmark)
{
    global $favicon, $vfs;
    $favicon = '';

    if ($fp = @fopen($bookmark->url, 'r')) {
        // Attempt to parse a favicon.
        $error = false;
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, 'startElement', 'endElement');
        while (!$error && !$favicon && $data = @fread($fp, 1024)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                $error = true;
            }
        }
        xml_parser_free($xml_parser);
        fclose($fp);

        $url = parse_url($bookmark->url);

        // If parsing a favicon failed, look for favicon.ico.
        if (!$favicon) {
            $headers = @_getHeaders($url['scheme'] . '://' . $url['host'] . '/favicon.ico', 1);
            if ($headers) {
                $status = explode(' ', $headers[0]);
                if ($status[1] == '200') {
                    $favicon = $url['scheme'] . '://' . $url['host'] . '/favicon.ico';
                } else {
                    if (isset($url['path'])) {
                        $path = pathinfo($url['path']);
                    } else {
                        $path = array('dirname' => '');
                    }
                    $headers = @_getHeaders($url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/favicon.ico', 1);
                    if ($headers) {
                        $status = explode(' ', $headers[0]);
                        if ($status[1] == '200') {
                            $favicon = $url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/favicon.ico';
                        }
                    }
                }
            }
        }

        // If a favicon was found, try to get it.
        if ($favicon) {
            // Make sure $favicon is a full URL.
            if (false && substr(strtolower($favicon), 0, 7) != 'http://') {
                if (substr($favicon, 0, 1) == '/') {
                    $favicon = $url['scheme'] . '://' . $url['host'] . $favicon;
                } else {
                    $path = pathinfo($url['path']);
                    $favicon = $url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/' . $favicon;
                }
            }

            // Attempt to read and store $favicon.
            if ($data = @file_get_contents($favicon)) {
                $info = pathinfo($favicon);
                $result = $vfs->writeData('.horde/trean/favicons/', $bookmark->id . '.' . $info['extension'], $data, true);
                if (!is_a($result, 'PEAR_Error')) {
                    return $bookmark->id . '.' . $info['extension'];
                }
            }
        }
    }

    return false;
}

// Get all bookmark ids. Loading them one by one is slow, but also
// avoids loading them ALL into memory at once.
$ids = $trean_db->queryCol('SELECT bookmark_id FROM trean_bookmarks');
foreach ($ids as $bookmark_id) {
    $bookmark = $trean_shares->getBookmark($bookmark_id);
    $check = @_getHeaders($bookmark->url, 1);
    if ($check) {
        // Set the http status and the number of times it has been
        // seen in a row.
        $status = explode(' ', $check[0]);
        if ($status[1] != $bookmark->http_status) {
            $bookmark->http_status = $status[1];
        }

        if (($bookmark->http_status == '200'
             || $bookmark->http_status == '302')
            && isset($vfs)) {
            if ($favicon = get_favicon($bookmark)) {
                $bookmark->favicon = $favicon;
            }
        }

        // If we've been redirected, update the bookmark's URL.
        /*
        if (isset($check['Location']) && $check['Location'] != $bookmark->url) {
            $location = @parse_url($check['Location']);
            if ($location && !empty($location['scheme'])) {
                $bookmark->url = $check['Location'];
                $bookmark->http_status = '';
            }
        }
        */
    } else {
        if ($bookmark->http_status != 'error') {
            $bookmark->http_status = 'error';
        }
    }

    $bookmark->save();
}
