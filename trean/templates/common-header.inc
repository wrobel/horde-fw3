<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . NLS::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Trean: Copyright 2000-2008 The Horde Project.  Trean is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Trean: http://www.horde.org/trean/    -->
<!--              Horde Licenses: http://www.horde.org/licenses/                    -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

Horde::includeScriptFiles();

$rss = Horde::applicationUrl('rss.php', true, -1);
if (Util::getFormData('f')) {
    $rss = Util::addParameter($rss, 'f', Util::getFormData('f'), false);
}
echo '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars(_("Bookmarks Feed")) . '" href="' . $rss . '" />';

$bc = Util::nonInputVar('bodyClass');
if ($prefs->getValue('show_folder_actions')) {
    if ($bc) {
        $bc .= ' ';
    }
    $bc .= 'folderActions';
}

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir()?>/favicon.ico" rel="SHORTCUT ICON" />
<?php echo Horde::stylesheetLink('trean') ?>
<link rel="stylesheet" href="<?php echo $registry->get('themesuri') ?>/grids-min.css" type="text/css" />
</head>

<body<?php if ($bc) echo ' class="' . $bc . '"' ?><?php if ($bi = Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
