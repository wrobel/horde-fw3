<?php
/**
 * $Horde: jonah/scripts/delivery_email.php,v 1.4 2006/09/10 17:50:40 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

/* No auth. */
@define('AUTH_HANDLER', true);

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once 'Horde/CLI.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';

/* Make sure no one runs this from the web. */
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

/* Load the CLI environment - make sure there's no time limit, init
 * some variables, etc. */
Horde_CLI::init();

/* Make sure there's no compression. */
@ob_end_clean();

$news = Jonah_News::factory();
$delivery = &Jonah_Delivery::singleton('email');

$channels = $news->getChannels();
$delivery->deliver();
