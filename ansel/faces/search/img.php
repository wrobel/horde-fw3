<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/search/img.php,v 1.4.2.2 2009/03/19 15:49:36 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../../lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';

/* Face search is allowd only to  */
if (!Auth::isauthenticated()) {
    exit;
}

$thumb = Util::getGet('thumb');
$tmp = Horde::getTempDir();
$path = $tmp . '/search_face_' . ($thumb ? 'thumb_' : '') .  Auth::getAuth() . Ansel_Faces::getExtension();

header('Content-type: image/' . $conf['image']['type']);
readfile($path);