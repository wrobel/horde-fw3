<?php
/**
 * Gollem Credentials File
 *
 * This file contains the credentials that Gollem understands and
 * might have to pass to a VFS backend. It may be safely edited by
 * hand. Use credentials.php.dist as a reference.
 *
 * General configuration is in 'conf.php'.
 * File stores are defined in 'backends.php'.
 * Default user preferences are defined in 'prefs.php'.
 *
 * $Horde: gollem/config/credentials.php.dist,v 1.3.2.1 2008/10/09 20:54:40 jan Exp $
 */

$credentials['username'] = array(
    'type' => 'text',
    'desc' => _("Username")
);

$credentials['password'] = array(
    'type' => 'password',
    'desc' => _("Password")
);

$credentials['email'] = array(
    'type' => 'email',
    'desc' => _("Email")
);
