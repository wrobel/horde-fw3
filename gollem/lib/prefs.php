<?php
/**
 * $Horde: gollem/lib/prefs.php,v 1.2.2.4 2009/01/06 15:23:54 jan Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 */

function handle_columnselect($updated)
{
    $columns = Util::getFormData('columns');
    if (!empty($columns)) {
        $GLOBALS['prefs']->setValue('columns', $columns);
        return true;
    }
    return false;
}
