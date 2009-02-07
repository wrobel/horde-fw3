<?php
/**
 * The SyncML_Device_Nokia:: class provides functionality that is
 * specific to the Nokia SyncML clients.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * $Horde: framework/SyncML/SyncML/Device/Nokia.php,v 1.2.2.10 2009/01/06 15:23:39 jan Exp $
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncML
 */
class SyncML_Device_Nokia extends SyncML_Device {

    /**
     * Converts the content received from the client for the backend.
     *
     * @param string $content      The content to convert.
     * @param string $contentType  The content type of the content.
     *
     * @return array  Two-element array with the converted content and the
     *                (possibly changed) new content type.
     */
    function convertClient2Server($content, $contentType)
    {
        list($content, $contentType) =
            parent::convertClient2Server($content, $contentType);

        /* At least the Nokia E51 seems to prefix category values with X-, see
         * bug #6849. */
        $di = $_SESSION['SyncML.state']->deviceInfo;
        if ($di->Mod == 'E51') {
            $content = preg_replace('/(\r\n|\r|\n)CATEGORIES:X-/',
                                    '\1CATEGORIES:', $content, 1);
        }

        return array($content, $contentType);
    }

    function handleTasksInCalendar()
    {
        return true;
    }

    /**
     * Some devices accept datetimes only in local time format:
     * DTSTART:20061222T130000
     * instead of the more robust (and default) UTC time:
     * DTSTART:20061222T110000Z
     */
    function useLocalTime()
    {
        return true;
    }

}
