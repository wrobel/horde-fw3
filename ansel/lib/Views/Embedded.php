<?php
/**
 * Ansel_View_Embedded Class wraps output of a javascript embedded gallery/image
 * widget. This view is responsible only for outputting the <script> tags that
 * will embed the view.  Provided as a way to output these views via the
 * renderViews api call. The actual rendering is done via the
 * EmbeddedRenderers/*.php files which are called from the XRequest_Embedded
 * class when it handles the request.
 *
 * $Horde: ansel/lib/Views/Embedded.php,v 1.12.2.3 2009/06/16 00:38:23 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_Embedded {

    /**
     * Initialize the view
     *
     * This view can take the following parameters:
     *
     *  container    The DOM id of the element to contain the embedded view.
     *               This parameter is required.
     *
     *  gallery_id   The gallery id
     *
     *  gallery_slug The gallery slug
     *
     *  gallery_view The specific view to embed this must match an existing
     *               EmbeddedRenderer (Mini, Slideshow, Carousel etc..)
     *               Defaults to 'Mini'
     *
     *  start        Start displaying at this image offset. Defaults to the start
     *               of the gallery.
     *
     *  count        Only return this many images. Defaults to returning the
     *               entire gallery (minus any subgalleries).
     *
     *  perpage      Some embedded views support paging. This sets the number of
     *               images per page. Note that all images are still returned.
     *               The paging is done via javascript only.
     *
     *  images       An array of image ids, not necessarily from the same
     *               gallery, to be displayed in this view. The gallery parameter
     *               will be ignored if present.
     *
     *  thumbsize    Which type of thumbnail images to display in the view.
     *               (mini, thumb, prettythumb etc...) Defaults to mini.
     * @static
     * @param array $params  Parameters for this view
     */
    function makeView($params)
    {
        global $ansel_storage;

        $view = new Ansel_View_Embedded();
        $view->_params = $params;
        return $view;
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     *
     */
    function html()
    {
        $src = Horde::applicationUrl('xrequest.php', true);
        $params = '?requestType=Embed';

        /* Are we displaying a gallery or a group of images? */
        if (!empty($this->_params['images']) && count($this->_params['images'])) {
            $params .= '/images=' .implode(':', $this->_params['images']);
        } elseif (!empty($this->_params['gallery_slug'])) {
            $params .= '/gallery_slug=' . $this->_params['gallery_slug'];
        } elseif (!empty($this->_params['gallery_id'])) {
            $params .= '/gallery_id=' . $this->_params['gallery_id'];
        }

        /* The container parameter is required */
        if (!empty($this->_params['container'])) {
            $params .= '/container=' . $this->_params['container'];
        } else {
            $err = PEAR::raiseError(_("Missing container parameter."));
            Horde::logMessage($err, __LINE__, __FILE__, PEAR_LOG_ERR);
            return $err;
        }

        /* Image to start at and number of images to include */
        if (!empty($this->_params['start'])) {
            $params .= '/start=' . (int)$this->_params['start'];
        }
        if (!empty($this->_params['count'])) {
            $params .= '/count=' . (int)$this->_params['count'];
        }

        /* The size of the images */
        if (!empty($this->_params['thumbsize'])) {
            $params .= '/thumbsize=' . $this->_params['thumbsize'];
        }

        /* perpage */
        if (!empty($this->_params['perpage'])) {
            $params .= '/perpage=' . (int)$this->_params['perpage'];
        }

        /* The specific view */
        $view = (!empty($this->_params['gallery_view'])) ? $this->_params['gallery_view'] : 'Mini';
        $params .= '/gallery_view=' . $view;

        /* Show links? */
        if (!empty($this->_params['hidelinks'])) {
            $params .= '/hidelinks=1';
        }

        $html = '<script type="text/javascript" src="' . $src . $params . '"></script>';
        $html .= '<div id="' . $this->_params['container'] . '"></div>';
        return $html;
    }

    function viewType()
    {
        return 'Embedded';
    }
}
