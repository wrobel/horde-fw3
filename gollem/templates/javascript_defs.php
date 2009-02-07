<?php

require_once GOLLEM_BASE . '/lib/JSON.php';
$charset = NLS::getCharset();

/* Variables used in core javascript files. */
$var = array(
    'empty_input' => intval($GLOBALS['browser']->hasQuirk('empty_file_input_value')),
    'prefs_api' => Horde::applicationUrl('pref_api.php', true),
);

/* Gettext strings used in core javascript files. */
$gettext = array_map('addslashes', array(
    /* Strings used in popup.js */
    'popup_block' => _("A popup window could not be opened. Perhaps you have set your browser to block popup windows?"),

    /* Strings used in login.js */
    'login_username' => _("Please provide your username."),
    'login_password' => _("Please provide your password."),

    /* Strings used in manager.js */
    'select_item' => _("Please select an item before this action."),
    'delete_confirm_1' => _("The following items will be permanently deleted:"),
    'delete_confirm_2' => _("Are you sure?"),
    'delete_recurs_1' => _("The following item(s) are folders:"),
    'delete_recurs_2' => _("Are you sure you wish to continue?"),
    'specify_upload' => _("Please specify at least one file to upload."),
    'file' => _("File"),

    /* Strings used in selectlist.js */
    'opener_window' => _("The original opener window has been closed. Exiting."),
));

?>
<script type="text/javascript">//<![CDATA[
var GollemVar = <?php echo Gollem_Serialize_JSON::encode(String::convertCharset($var, $charset, 'UTF-8')) ?>;
var GollemText = <?php echo Gollem_Serialize_JSON::encode(String::convertCharset($gettext, $charset, 'UTF-8')) ?>;
//]]></script>
