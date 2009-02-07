<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . NLS::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--  Gollem: Copyright 2001-2009 The Horde Project.  Gollem is under the GPL.   -->
<!-- Horde Project: http://www.horde.org/ | Gollem: http://www.horde.org/gollem/ -->
<!--          GNU Public License: http://www.fsf.org/copyleft/gpl.html           -->
<?php
echo (!empty($language)) ? '<html lang="' . strtr($language, '_', '-') . '"' : '<html';
echo (!empty($ie_clientcaps)) ? ' xmlns:IE>' : '>';
?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

Horde::includeScriptFiles();
require GOLLEM_TEMPLATES . '/javascript_defs.php';

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir()?>/favicon.ico" rel="SHORTCUT ICON" />
<?php echo Horde::stylesheetLink('gollem') ?>
<?php if (!empty($ie_clientcaps)): ?><style>IE\:clientcaps {behavior:url(#default#clientcaps)}</style><?php endif; ?>
</head>

<body<?php if ($bc = Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
<?php if (!empty($ie_clientcaps)): ?><IE:clientcaps ID="objCCaps" /><?php endif; ?>
