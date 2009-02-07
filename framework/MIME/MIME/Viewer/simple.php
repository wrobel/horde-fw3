<?php
/**
 * The MIME_Viewer_simple class renders out plain text without any
 * modifications.
 *
 * $Horde: framework/MIME/MIME/Viewer/simple.php,v 1.1.6.9 2009/01/06 15:23:21 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @since   Horde 3.0
 * @package Horde_MIME_Viewer
 */
class MIME_Viewer_simple extends MIME_Viewer {

    /**
     * Return the MIME type of the rendered content.
     *
     * @return string  MIME-type of the output content.
     */
    function getType()
    {
        return 'text/plain';
    }

}
