<?php
/**
 * Ansel_Widget_OwnerFaces:: class to display a widget containing mini
 * thumbnails of faces that have been tagged by the gallery owner.
 *
 * @author Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Widget_OwnerFaces extends Ansel_Widget {

    var $_faces;
    var $_count;
    var $_owner;

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_ImageFaces
     */
    function Ansel_Widget_OwnerFaces($params)
    {
        parent::Ansel_Widget($params);

        require_once ANSEL_BASE . '/lib/Faces.php';
        $this->_faces = Ansel_Faces::factory();
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    function html()
    {
        if (!$GLOBALS['conf']['faces']['driver']) {
            return '';
        }

        $this->_owner = $this->_view->gallery->get('owner');
        $this->_count = $this->_faces->countOwnerFaces($this->_owner);
        if (is_a($this->_count, 'PEAR_error')) {
            $this->_count = 0;
        }

        $this->_title = '<a href="' . Util::addParameter(Horde::applicationUrl('faces/search/owner.php'), 'owner', $this->_owner) . '">'
            . sprintf(_("People in galleries of %s (%d of %d)"),
                      $this->_owner, min(12, $this->_count), number_format($this->_count))
            . '</a>';
        $html = $this->_htmlBegin();

        if (empty($this->_count)) {
            return null;
        }

        $results = $this->_faces->ownerFaces($this->_owner, 0, 12, true);
        foreach ($results as $face_id => $face) {
            $html .= '<a href="' . $this->_faces->getLink($face) . '" title="' . $face['face_name'] . '">'
                    . '<img src="' . $this->_faces->getFaceUrl($face['image_id'], $face_id, 'mini')
                    . '" style="padding-bottom: 5px; padding-left: 5px" /></a>';
        }

        return $html . $this->_htmlEnd();
    }
}