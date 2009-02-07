<?php
/**
 * $Horde: whups/imple.php,v 1.1.2.1 2009/01/06 15:28:14 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

@define('WHUPS_BASE', dirname(__FILE__));
require_once WHUPS_BASE . '/lib/base.php';
require_once WHUPS_BASE . '/lib/Imple.php';

$path = Util::getFormData('imple');
if (!$path) {
    exit;
}
if ($path[0] == '/') {
    $path = substr($path, 1);
}
$path = explode('/', $path);
$impleName = array_shift($path);

$imple = Imple::factory($impleName);
if (!$imple) {
    exit;
}

$args = array();
foreach ($path as $pair) {
    if (strpos($pair, '=') === false) {
        $args[$pair] = true;
    } else {
        list($name, $val) = explode('=', $pair);
        $args[$name] = $val;
    }
}

$result = $imple->handle($args);

if (!empty($_SERVER['Content-Type'])) {
    $ct = $_SERVER['Content-Type'];
} else {
    $ct = is_string($result) ? 'plain' : 'json';
}

switch ($ct) {
case 'json':
    header('Content-Type: text/x-json');
    require_once 'Horde/Serialize.php';
    echo Horde_Serialize::serialize(
        String::convertCharset($result, NLS::getCharset(), 'utf-8'),
        SERIALIZE_JSON);
    break;

case 'plain':
    header('Content-Type: text/plain');
    echo $result;
    break;

case 'html':
    header('Content-Type: text/html');
    echo $result;
    break;

default:
    echo $result;
}
