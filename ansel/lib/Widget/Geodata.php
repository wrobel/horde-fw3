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

    function html()
    {
        global $registry;

        // Don't even try if we don't have an api key
        if (empty($GLOBALS['conf']['api']['googlemaps'])) {
            return '';
        }

        // For now, center map on the first available data set. Need to figure
        // out how to display the map when multiple points exist in very distant
        // areas.
        $geodata = $GLOBALS['ansel_storage']->getImagesGeodata($this->_params['images']);
        if (count($geodata) == 0) {
            return '';
        }

        $html = $this->_htmlBegin();
        $html .= '<script src="http://maps.google.com/maps?file=api&sensor=false&v=2&key=' . $GLOBALS['conf']['api']['googlemaps'] . '" type="text/javascript"></script>';
        $html .= '<div id="map" style="width:100%; height: 200px; float:left;overflow:hidden;"></div><div class="clearer">&nbsp;</div><div id="ansel_locationtext"><br /></div>';
        $html .= <<<EOT
        <script type="text/javascript">
        function doGeoCode(points, marker, image_id)
        {
            for (var i = 0; i < points.Placemark.length; i++) {
                var place = points.Placemark[i];
                // We are at a low enough accuracy to use as-is.
                if (place.AddressDetails.Accuracy <= 4) {
                    if (viewType == 'Gallery') {
                        GEvent.addListener(marker, "mouseover", function() {
                            $('ansel_locationtext').update(place.address);
                            //$(image_id + 'caption').toggleClassName('image-tile-hilite');
                            new Effect.Highlight('imagetile_' + image_id);
                        });
                        GEvent.addListener(marker, "click", function() {
                            a = $$('#imagetile_' + image_id + ' a')[0];
                            if (!a.onclick || a.onclick() != false) {
                                location.href = $$('#imagetile_' + image_id + ' a')[0].href;
                            }
                        });
                        GEvent.addListener(marker, "mouseout", function() {
                            $('ansel_locationtext').update('<br />');
                            //$(image_id + 'caption').toggleClassName('image-tile-hilite');
                        });
                    } else {
                        //$('ansel_locationtext').update(place.address);
                    }
                    return;
                } else {
                    // Need to exclude street-level detail or maybe just use
                    // the next returned Placemark?
                }
            }
        }

        function addPoint(ll, image_id)
        {
            var marker = new GMarker(ll, {draggable: false});
            map.addOverlay(marker, {draggable: false});
            geo.getLocations(ll, function(address) {doGeoCode(address, marker, image_id)});
        }

        var map = new GMap2($('map'));
        map.setUIToDefault();
        map.setMapType(G_HYBRID_MAP);
        var latlngbounds = new GLatLngBounds();
        var geo = new GClientGeocoder();
EOT;
        $html .= 'var viewType = "' . $this->_view->viewType() . '";';
        foreach ($geodata as $id => $loc) {
            if (!empty($loc['image_latitude']) && !empty($loc['image_longitude'])) {
                $html .= 'var latlng = new GLatLng(' . $loc['image_latitude'] . ', ' . $loc['image_longitude'] . ');';
                $html .= 'latlngbounds.extend(latlng);';
                $html .= 'addPoint(latlng, ' . $id . ');';
            }
        }

        if (count($geodata) > 1) {
            $html .= 'map.setCenter(latlngbounds.getCenter(), Math.min(map.getBoundsZoomLevel(latlngbounds), ' . $this->_params["max_auto_zoom"] . '));';
        } elseif (count($geodata) == 1) {
            $html .= 'map.setCenter(latlng, ' . $this->_params['default_zoom'] . ');';
        }
        
        $html .= 'document.observe("unload", function() {GUnload();});';
        $html .= '</script>';
        $html .= $this->_htmlEnd();

        return $html;
    }

}
?>
