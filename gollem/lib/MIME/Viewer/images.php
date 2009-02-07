<?php

require_once 'Horde/MIME/Viewer/images.php';

/**
 * The Gollem_MIME_Viewer_images class allows images to be displayed
 * inline in a message.
 *
 * $Horde: gollem/lib/MIME/Viewer/images.php,v 1.22.2.4 2009/01/06 15:23:55 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_MIME_Viewer
 */
class Gollem_MIME_Viewer_images extends MIME_Viewer_images {

    /**
     * Render out the currently set contents.
     *
     * @param array $params  Not used.
     *
     * @return string  The rendered information.
     */
    function render($params = null)
    {
        if ($GLOBALS['browser']->isViewable($this->mime_part->getType())) {
            $url = Util::addParameter(Horde::applicationUrl('view.php'), array('actionID' => 'download_file', 'file' => $this->mime_part->getName(), 'dir' => Util::getFormData('dir'), 'driver' => Util::getFormData('driver')));
            $title = $this->mime_part->getName(false, true);
            return parent::_popupImageWindow($url, $title);
        } else {
            return '<html><body><em>' . _("Your browser does not support inline display of this image type") . '</em>.</body></html>';
        }
    }

    /**
     * Return the content-type
     *
     * @return string  The content-type of the output from render().
     */
    function getType()
    {
        return 'text/html';
    }

}
