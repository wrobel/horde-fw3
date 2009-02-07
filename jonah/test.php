<?php
/**
 * $Horde: jonah/test.php,v 1.27 2008/01/02 11:13:16 jan Exp $
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/* Include Horde's core.php file. */
include_once '../lib/core.php';

/* We should have loaded the String class, from the Horde_Util
 * package, in core.php. If String:: isn't defined, then we're not
 * finding some critical libraries. */
if (!class_exists('String')) {
    echo '<br /><h2 style="color:red">The Horde_Util package was not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to get the <a href="http://cvs.horde.org/cvs.php/framework">framework</a> module from <a href="http://www.horde.org/source/">Horde CVS</a>, and install the packages in it with the install-packages.php script.</h2>';
    exit;
}

/* Initialize the Horde_Test:: class. */
if (!is_readable('../lib/Test.php')) {
    echo 'ERROR: You must install Horde before running this script.';
    exit;
}
require_once '../lib/Test.php';
$horde_test = new Horde_Test();

/* Jonah definitions. */
$module = 'Jonah';
require_once './lib/version.php';
$module_version = JONAH_VERSION;

/* PHP module capabilities. */
$module_list = array(
    'gettext'  =>  array(
        'descrip' => 'Gettext Support',
        'error' => 'Jonah will not run without gettext support. Compile php <code>--with-gettext</code> before continuing.'
    ),
    'xml'  =>  array(
        'descrip' => 'XML Support',
        'error' => 'Without XML support, Jonah WILL NOT WORK. You must fix this before going any further.'
    )
);

/* Jonah configuration files. */
$file_list = array(
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You probably just forgot to copy <code>./config/conf.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.'
);

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

?>

<h1>PHP Modules</h1>
<ul>
    <?php echo $horde_test->phpModuleCheck($module_list) ?>
</ul>

<h1>Jonah Configuration Files</h1>
<ul>
    <?php echo $horde_test->requiredFileCheck($file_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
