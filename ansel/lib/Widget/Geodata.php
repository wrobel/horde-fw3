<?php
/**
 * Ansel_Widget_Geodata:: class to wrap the display of various feed links etc...
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Geodata extends Ansel_Widget {

    var $_supported_views = array('Image', 'Gallery');
    var $_params = array('default_zoom' => 15,
                         'max_auto_zoom' => 15);

    function Ansel_Widget_Geodata($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("Location");
    }

    function attach($view)
    {
         // Don't even try if we don't have an api key
        if (empty($GLOBALS['conf']['api']['googlemaps'])) {
            return false;
        }
        parent::attach($view);
        Horde::addScriptFile('googlemap.js');

        return true;
    }

    function html()
    {
        global $registry;

        // For now, center map on the first available data set. Need to figure
        // out how to display the map when multiple points exist in very distant
        // areas.
        $geodata = $GLOBALS['ansel_storage']->getImagesGeodata($this->_params['images']);
        if (count($geodata) == 0) {
            return '';
        }

        $type = $this->_view->viewType();
        $html = $this->_htmlBegin();
        $html .= '<script src="http://maps.google.com/maps?file=api&v=2&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps'] . '" type="text/javascript"></script>';
        $html .= '<div id="ansel_map"></div><div class="clear"></div><div id="ansel_locationtext"><br /></div>';
        if ($this->_view->viewType() == 'Image') {
            $html .= '<div id="ansel_latlong"></div><div id="ansel_relocate"></div>';
        }
        $html .= '<div id="ansel_map_small"></div><div class="clear"></div>';
        $html .= <<<EOT
        <script type="text/javascript">
            options = {
                smallMap: 'ansel_map_small',
                mainMap:  'ansel_map',
                viewType: '{$type}'
            };

            map = new Ansel_GMap(options);
            Event.observe(window, 'dom:loaded', function() {
EOT;
        foreach ($geodata as $id => $loc) {
            $miniurl = Ansel::getImageUrl($id, 'mini', true);
            if (!empty($loc['image_latitude']) && !empty($loc['image_longitude'])) {
                $options = '{' . ($this->_view->viewType() == 'Image' ? 'markerOnly:true' : '') . '}';
                $html .= 'map.addPoint(' . $loc['image_latitude'] . ', '
                                         . $loc['image_longitude'] . ', '
                                         . '{id:'. $id . ', icon: "' . $miniurl . '"}' . ','
                                         . $options . ');';
            }
        }
        $html .= 'map.display();';
        $html .= '});';
        $html .= '</script>';
        $html .= $this->_htmlEnd();

        return $html;
    }

}
