<?php
/**
 * @package Jonah
 */

/**
 * Internal Jonah channel.
 */
define('JONAH_INTERNAL_CHANNEL', 0);

/**
 * External channel.
 */
define('JONAH_EXTERNAL_CHANNEL', 1);

/**
 * Aggregated channel.
 */
define('JONAH_AGGREGATED_CHANNEL', 2);

/**
 * Composite channel.
 */
define('JONAH_COMPOSITE_CHANNEL', 3);

/**
 */
define('JONAH_ORDER_PUBLISHED', 0);
define('JONAH_ORDER_READ', 1);
define('JONAH_ORDER_COMMENTS', 2);


/**
 * Jonah Base Class.
 *
 * $Horde: jonah/lib/Jonah.php,v 1.129 2007/08/30 13:08:23 jan Exp $
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Eric Rechlin <eric@hpcalc.org>
 * @package Jonah
 */
class Jonah {

    /**
     * Standardizes the setting up of the action bar for various displays.
     *
     * @param array $action_items  An array of actions to be formatted in
     *                             the format array('text label' => 'url').
     *
     * @return array               A array of actions containing the text
     *                             label, the url and an already set up
     *                             link for each action.
     */
    function setupActions($action_items)
    {
        $actions = array();
        foreach ($action_items as $text => $url) {
            $action = array('text' => $text,
                            'url' => $url,
                            'link' => Horde::link($url, $text, 'smallheader'));
            $actions[] = $action;
        }

        return $actions;
    }

    /**
     */
    function _readURL($url)
    {
        global $conf;

        $options['method'] = 'GET';
        $options['timeout'] = 5;
        $options['allowRedirects'] = true;

        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $conf['http']['proxy']);
        } elseif (!empty($conf['proxy']['proxy_host'])) {
            $options = array_merge($options, $conf['proxy']);
        }

        require_once 'HTTP/Request.php';
        $http = new HTTP_Request($url, $options);
        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            return PEAR::raiseError(sprintf(_("Could not open %s."), $url));
        }

        $result = array('body' => $http->getResponseBody());
        $content_type = $http->getResponseHeader('Content-Type');
        if (preg_match('/.*;\s?charset="?([^"]*)/', $content_type, $match)) {
            $result['charset'] = $match[1];
        } elseif (preg_match('/<\?xml[^>]+encoding=["\']?([^"\'\s?]+)[^?].*?>/i', $result['body'], $match)) {
            $result['charset'] = $match[1];
        }

        return $result;
    }

    /**
     * Returns a drop-down select box to choose which view to display.
     *
     * @param name Name to assign to select box.
     * @param selected Currently selected item. (optional)
     * @param onchange JavaScript onchange code. (optional)
     *
     * @return string Generated select box code
     */
    function buildViewWidget($name, $selected = 'standard', $onchange = '')
    {
        require JONAH_BASE . '/config/templates.php';

        if ($onchange) {
            $onchange = ' onchange="' . $onchange . '"';
        }

        $html = '<select name="' . $name . '"' . $onchange . '>' . "\n";
        foreach ($templates as $key => $tinfo) {
            $select = ($selected == $key) ? ' selected="selected"' : '';
            $html .= '<option value="' . $key . '"' . $select . '>' . $tinfo['name'] . "</option>\n";
        }
        return $html . '</select>';
    }

    /**
     */
    function getChannelTypeLabel($type)
    {
        switch ($type) {
        case JONAH_INTERNAL_CHANNEL:
            return _("Local Feed");

        case JONAH_EXTERNAL_CHANNEL:
            return _("External Feed");

        case JONAH_AGGREGATED_CHANNEL:
            return _("Aggregated Feed");

        case JONAH_COMPOSITE_CHANNEL:
            return _("Composite Feed");
        }
    }

    /**
     */
    function checkPermissions($filter, $permission = PERMS_READ, $in = array())
    {
        if (Auth::isAdmin('jonah:admin', $permission)) {
            if (empty($in)) {
                return true;
            } else {
                return $in;
            }
        }

        global $perms;

        $out = array();

        switch ($filter) {
        case 'internal_channels':
        case 'external_channels':
            if (empty($in) || !$perms->exists('jonah:news:' . $filter . ':' . $in)) {
                return $perms->hasPermission('jonah:news:' . $filter, Auth::getAuth(), $permission);
            } elseif (!is_array($in)) {
                return $perms->hasPermission('jonah:news:' . $filter . ':' . $in, Auth::getAuth(), $permission);
            } else {
                foreach ($in as $key => $val) {
                    if ($perms->hasPermission('jonah:news:' . $filter . ':' . $val, Auth::getAuth(), $permission)) {
                        $out[$key] = $val;
                    }
                }
            }
            break;

        case 'channels':
            foreach ($in as $key => $val) {
                $perm_name = Jonah::typeToPermName($val['channel_type']);
                if ($perms->hasPermission('jonah:news:' . $perm_name,  Auth::getAuth(), $permission) ||
                    $perms->hasPermission('jonah:news:' . $perm_name . ':' . $val['channel_id'], Auth::getAuth(), $permission)) {
                    $out[$key] = $in[$key];
                }
            }
            break;

        default:
            return $perms->hasPermission($filter, Auth::getAuth(), PERMS_EDIT);
        }

        return $out;
    }

    /**
     */
    function typeToPermName($type)
    {
        if ($type == JONAH_INTERNAL_CHANNEL) {
            return 'internal_channels';
        } elseif ($type == JONAH_EXTERNAL_CHANNEL) {
            return 'external_channels';
        }
    }

    /**
     * Returns an array of configured body types from Jonah's $conf array.
     *
     * @return array  An array of body types.
     */
    function getBodyTypes()
    {
        static $types = array();
        if (!empty($types)) {
            return $types;
        }

        if (in_array('richtext', $GLOBALS['conf']['news']['story_types'])) {
            $types['richtext'] = _("Rich Text");
        }

        /* Other than checking if text is enabled, it is inserted by default if
         * no other body type has been enabled in the config. */
        if (in_array('text', $GLOBALS['conf']['news']['story_types']) ||
            empty($types)) {
            $types['text'] = _("Text");
        }

        return $types;
    }

    /**
     * Tries to figure out a default body type. Used when none has been
     * specified and a types is needed to fall back on to.
     *
     * @return string  A default type.
     */
    function getDefaultBodyType()
    {
        $types = Jonah::getBodyTypes();
        if (isset($types['text'])) {
            return 'text';
        } elseif (isset($types['richtext'])) {
            return 'richtext';
        }
        /* The two most common body types have not been found, so just return
         * the first one that is in the array. */
        $tmp = array_keys($types);
        return array_shift($tmp);
    }

    /**
     * Build Jonah's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $registry, $conf;

        require_once 'Horde/Menu.php';
        $menu = new Menu();

        /* Jonah Home. */
        $menu->addArray(array('url' => Horde::applicationUrl('content.php'), 'text' => _("_My News"), 'icon' => 'jonah.png', 'class' => (basename($_SERVER['PHP_SELF']) == 'content.php' || basename($_SERVER['PHP_SELF']) == 'index.php') && strstr($_SERVER['PHP_SELF'], 'channels/') === false && strstr($_SERVER['PHP_SELF'], 'delivery/') === false && strstr($_SERVER['PHP_SELF'], 'stories/') === false ? 'current' : ''));

        /* If authorized, show admin link. */
        if (Jonah::checkPermissions('jonah:news', PERMS_EDIT) && !empty($conf['news']['enable'])) {
            $menu->addArray(array('url' => Horde::applicationUrl('channels/index.php'), 'text' => _("_Feeds"), 'icon' => 'jonah.png'));
            $menu->addArray(array('url' => Horde::applicationUrl('channels/edit.php'), 'text' => _("_New Feed"), 'icon' => 'new.png'));
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
