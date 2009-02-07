<?php

require_once 'Horde/MIME/Viewer/zip.php';

/**
 * The MIME_Viewer_zip class renders out the contents of ZIP files in HTML
 * format and allows downloading of extractable files.
 *
 * $Horde: whups/lib/MIME/Viewer/zip.php,v 1.13.2.1 2009/01/06 15:28:24 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_MIME_Viewer
 */
class Whups_MIME_Viewer_zip extends MIME_Viewer_zip {

    /**
     * The mime type of the rendered content.
     *
     * @var string
     */
    var $_mime_type;

    /**
     * Renders the currently set content.
     *
     * @return string  Either the list of zip files or the data of an
     *                 individual zip file.
     */
    function render()
    {
        $data = $this->mime_part->getContents();
        $text = '';

        /* Send the requested file. Its position in the zip archive is
           located in 'zip_attachment'. */
        if (Util::getFormData('zip_attachment')) {
            require_once 'Horde/Compress.php';
            $zip = &Horde_Compress::singleton('zip');
            $fileKey = Util::getFormData('zip_attachment') - 1;
            $zipInfo = $zip->decompress($data, array('action' => HORDE_COMPRESS_ZIP_LIST));
            /* Verify that the requested file exists. */
            if (isset($zipInfo[$fileKey])) {
                $text = $zip->decompress($data, array('action' => HORDE_COMPRESS_ZIP_DATA, 'info' => &$zipInfo, 'key' => $fileKey));
                if (empty($text)) {
                    $text = '<pre>' . _("Could not extract the requested file from the Zip archive.") . '</pre>';
                } else {
                    $this->_mime_type = 'application/octet-stream';
                    $this->mime_part->setName($zipInfo[$fileKey]['name']);
                }
            } else {
                $text = '<pre>' . _("The requested file does not exist in the Zip attachment.") . '</pre>';
            }
        } else {
            $text = parent::_render($data, array($this, '_callback'));
            $this->_name = $this->mime_part->getName();
        }

        return $text;
    }

    /**
     * The function to use as a callback to parent::_render().
     *
     * @access private
     *
     * @param integer $key  The position of the file in the zip archive.
     * @param array $val    The information array for the archived file.
     *
     * @return string  The content-type of the output.
     */
    function _callback($key, $val)
    {
        $name = str_replace('&nbsp;', '', $val['name']);
        if (!empty($val['size']) && (strstr($val['attr'], 'D') === false) &&
            ((($val['_method'] == 0x8) && Util::extensionExists('zlib')) ||
            ($val['_method'] == 0x0))) {
            $val['name'] = str_replace($name, Horde::link(Util::addParameter(Horde::applicationUrl('view.php'), array('actionID' => 'view_file', 'type' => Util::getFormData('type'), 'file' => Util::getFormData('file'), 'ticket' => Util::getFormData('ticket'), 'zip_attachment' => $key + 1))) . $name . '</a>', $val['name']);
        }

        return $val;
    }

    /**
     * Returns the content type.
     *
     * @return string  The content type of the output.
     */
    function getType()
    {
        if (isset($this->_mime_type)) {
            return $this->_mime_type;
        }

        return parent::getType();
    }

}
