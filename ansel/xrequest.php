<?php
/**
 * $Horde: ansel/xrequest.php,v 1.35.2.1 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
require_once ANSEL_BASE . '/lib/XRequest.php';

if (!($path = Util::getFormData('requestType'))) {
    exit;
}

$args = array();

// url parameters are treated special
$url = Util::getFormData('url');
if (!empty($url)) {
    $args['url'] = $url;
}

if ($path[0] == '/') {
    $path = substr($path, 1);
}
$path = explode('/', $path);
$request = array_shift($path);
if (!($xrequest = Ansel_XRequest::factory($request))) {
    exit;
}

foreach ($path as $pair) {
    if (strpos($pair, '=') === false) {
        $args[$pair] = true;
    } else {
        list($name, $val) = explode('=', $pair);
        $args[$name] = $val;
    }
}

$xrequest->handle($args);
