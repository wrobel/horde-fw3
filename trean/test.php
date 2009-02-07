<?php
/**
 * $Horde: trean/test.php,v 1.2 2008/01/02 11:14:01 jan Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
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
$horde_test = new Horde_Test;

/* Trean version. */
$module = 'Trean';
require_once './lib/version.php';
$module_version = TREAN_VERSION;

/* Jonah configuration files. */
$file_list = array(
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You probably just forgot to generate it using the Horde config system - see docs/INSTALL for details. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/prefs.php' => 'The file <code>./config/prefs.php</code> appears to be missing. You probably just forgot to copy <code>./config/prefs.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.'
);

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* PEAR */
$pear_list = array('MDB2' => array(
                        'path' => 'MDB2.php',
                        'required' => true,
                        'error' => 'You do not have the MDB2 package installed on your system. In addition to this package, you will need the appropriate MDB2_Driver package for your database backend.')
);

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

?>
<h1>Trean Configuration Files</h1>
<ul>
    <?php echo $horde_test->requiredFileCheck($file_list) ?>
</ul>

<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
