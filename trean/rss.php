<?php
/**
 * $Horde: trean/rss.php,v 1.3 2007/03/03 20:59:15 chuck Exp $
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('AUTH_HANDLER', true);
@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';
require_once 'Horde/Cache.php';

// Handle HTTP Authentication
function _requireAuth()
{
    $auth = &Auth::singleton($GLOBALS['conf']['auth']['driver']);
    if (!isset($_SERVER['PHP_AUTH_USER'])
        || !$auth->authenticate($_SERVER['PHP_AUTH_USER'],
                                array('password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null))) {
        header('WWW-Authenticate: Basic realm="Trean RSS Interface"');
        header('HTTP/1.0 401 Unauthorized');
        echo '401 Unauthorized';
        exit;
    }

    return true;
}

// Show a specific folder?
if (($folderId = Util::getGet('f')) !== null) {
    $folder = &$trean_shares->getFolder($folderId);
    // Try guest permissions, if acccess is not granted, login and
    // retry.
    if ($folder->hasPermission('', PERMS_READ) ||
        (_requireAuth() && $folder->hasPermission(Auth::getAuth(), PERMS_READ))) {
        $folders = array($folderId);
    }
} else {
    // Get all folders. Try guest permissions, if no folders are
    // accessible, login and retry.
    $folders = $trean_shares->listFolders('', PERMS_READ);
    if (empty($folders) && _requireAuth()) {
        $folders = $trean_shares->listFolders(Auth::getAuth(), PERMS_READ);
    }
}

// No folders to display
if (empty($folders)) {
    exit;
}

// Cache object
$cache = &Horde_Cache::singleton($conf['cache']['driver'],
                                 Horde::getDriverConfig('cache', $conf['cache']['driver']));

// Get folders to display
$cache_key = 'trean_rss_' . Auth::getAuth() . '_' . ($folderId === null ? 'all' : $folderId);
$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (!$rss) {
    $rss = '<?xml version="1.0" encoding="' . NLS::getCharset() . '" ?>
    <rss version="2.0">
        <channel>
        <title>' . htmlspecialchars($folderId == null ? $registry->get('name') : $folder->get('name')) . '</title>
        <language>' . NLS::select() . '</language>
        <charset>' . NLS::getCharset() . '</charset>
        <lastBuildDate>' . date('Y-m-d H:i:s') . '</lastBuildDate>
        <image>
            <url>http://' . $_SERVER['SERVER_NAME'] . $registry->get('webroot') . '/themes/graphics/favicon.ico</url>
        </image>
        <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    foreach ($folders as $folderId) {
        $folder = &$trean_shares->getFolder($folderId);
        $bookmarks = $folder->listBookmarks($prefs->getValue('sortby'),
                                            $prefs->getValue('sortdir'));
        foreach ($bookmarks as $bookmark) {
            if (!$bookmark->url) {
                continue;
            }
            $rss .= '
            <item>
                <title>' . htmlspecialchars($bookmark->title) . ' </title>
                <link>' . htmlspecialchars($bookmark->url) . '</link>
                <description>' . htmlspecialchars($bookmark->description) . '</description>
            </item>';
        }
    }

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: application/rss+xml');
echo $rss;
