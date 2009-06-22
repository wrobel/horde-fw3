/**
 * Google maps implementation for Ansel
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: ansel/js/src/googlemap.js,v 1.1.2.3 2009/06/19 17:03:10 mrubinsk Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
var Ansel_GMap = Class.create();

Ansel_GMap.prototype = {
    // Main google map handle
    mainMap: undefined,

    // GLatLngBounds obejct for calculating proper center and zoom
    bounds: undefined,

    // Geocoder
    geocoder: undefined,

    // MarkerManager, if we are browsing the map.
    // Note we need <script src="http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/release/src/markermanager.js">
    //manager: undefined,

    // Smaller overview map handle
    smallMap: undefined,

    // Can override via options array
    tilePrefix: 'imagetile_',
    locationId: 'ansel_locationtext',
    maxZoom: 15,
    defaultZoom: 15,
    options: {},

    // const'r
    // options.smallMap = [id: 80px];
    //        .mainMap [id: xx]
    //        .viewType (Gallery, Image)
    initialize: function(options) {
        this.mainMap = new GMap2($(options.mainMap));
        this.mainMap.setMapType(G_HYBRID_MAP);
        this.mainMap.setUIToDefault();
        this.bounds = new GLatLngBounds();
        this.geocoder = new GClientGeocoder();
        this.options = options;
        if (options.useManager == true) {
            this.manager = new MarkerManager(this.mainMap);
        }
        if (options.tilePrefix) {
            this.tilePrefix = options.tilePrefix;
        }

        // Eventually we will requery Ansel for images to display in the new
        // location.
        //GEvent.addListener(this,mainMap, 'moveend', this._moveCallback);
        if (options.smallMap) {
            this.smallMap = new GMap2($(options.smallMap));
        }

        // Clean up
        document.observe('unload', function() {GUnload();});
    },
    addPoint: function(lat, lng, image_data, options) {
        if (!options) {
            var options = {};
        }
        var ll = new GLatLng(lat, lng);
        this.bounds.extend(ll);
        if (options.markerOnly == true) {
            var marker = new GMarker(ll, options);
        } else {
            var marker = new anselGOverlay(ll, image_data);
        }

        // Add click handler so markers can link to image view.
        if (!options.markerOnly) {
            GEvent.addDomListener(marker.div_, 'click', function() {
                a = $$('#' + this.tilePrefix + image_data.id + ' a')[0];
                if (!a.onclick || a.onclick() != false) {
                    location.href = a.href;
                }
            }.bind(this));
        }

        if (this.options.smallMap) {
            var marker2 = new GMarker(ll);
            this.smallMap.addOverlay(marker2);
        }

        if (this.options.useManager) {
            // Let the marker manager handle this
        } else {
            this.mainMap.addOverlay(marker);
            // TODO: image_data will indicate if we already have reverse geocode
            //       data or not...and options will indicate if/how we want it
            //       displayed. For now, dump all this functionality into the
            //       callback.
            this.geocoder.getLocations(ll, function(address) {this._locationCallBack(address, marker, image_data)}.bind(this));
        }
    },

    // Callback to parse and attach location data the the points on the map.
    // In whatever way we are configured.
    _locationCallBack: function(points, marker, image_data) {
        for (var i = 0; i < points.Placemark.length; i++) {
            var place = points.Placemark[i];
            if (place.AddressDetails.Accuracy <= 4) {
                // @TODO: Need to rework this to allow the click events to
                //        work for other thumbnails in the Image view (so we
                //       can display other, closeby images on the map in Image
                //        view as well. For now, just don't support it.
                if (!this.options.markerOnly && this.options.viewType != 'Image') {
                    GEvent.addDomListener(marker.div_, 'mouseover', function() {
                        $(this.locationId).update(place.address);
                        $$('#' + this.tilePrefix + image_data.id + ' img')[0].toggleClassName('image-tile-highlight');
                        marker.focus();
                    }.bind(this));
                    GEvent.addDomListener(marker.div_, 'mouseout', function() {
                        $(this.locationId).update('<br />');
                        $$('#' + this.tilePrefix + image_data.id + ' img')[0].toggleClassName('image-tile-highlight');
                        marker.focus();
                    }.bind(this));
                }
                if (options.viewType != 'Image') {
                    // Handlers for the image tiles that have locations.
                    $$('#' + this.tilePrefix + image_data.id + ' img')[0].observe('mouseover', function() {
                        $(this.locationId).update(place.address);
                        $$('#' + this.tilePrefix + image_data.id + ' img')[0].toggleClassName('image-tile-highlight');
                        marker.focus();
                    }.bind(this));

                    // Handlers for the image tiles that have locations.
                    $$('#' + this.tilePrefix + image_data.id + ' img')[0].observe('mouseout', function() {
                        $(this.locationId).update('<br />');
                        $$('#' + this.tilePrefix + image_data.id + ' img')[0].toggleClassName('image-tile-highlight');
                        marker.div_.style.border = '1px solid white';
                        marker.focus();
                    }.bind(this));

                    return;
                } else {
                    // No need for events in Image view (only one marker)
                    $(this.locationId).update(place.address);
                    return;
                }
            } else {
                // Parse less detail, or just move on to the next hit??
            }
        }
    },

    display: function() {
        if (this.options.viewType == 'Gallery') {
            var maxZoomAvailable = 0;
            this.mainMap.getCurrentMapType().getMaxZoomAtLatLng(this.bounds.getCenter(), function(response) {
                this.mainMap.setCenter(this.bounds.getCenter(), Math.min(this.mainMap.getBoundsZoomLevel(this.bounds), Math.max(this.maxZoom, response.zoom)));
                if (this.options.smallMap) {
                    this.smallMap.setCenter(this.mainMap.getCenter(), 1);
                }
            }.bind(this));
        } else {
            this.mainMap.setCenter(this.bounds.getCenter(), this.defaultZoom);
            if (this.options.smallMap) {
                this.smallMap.setCenter(this.mainMap.getCenter(), 1);
            }
        }
    },

    // Event callback for (eventually) requerying Ansel for a list of images
    // that fall within the current visible rectangle
    //_moveCallback: function() {}
}

// Define our custom GOverlay to display thumbnails of images on the map.
// Use an Image object to get the exact dimensions of the image. Need this
// wrapped in an onload handler to be sure GOverlay() is defined.
Event.observe(window,'dom:loaded', function() {
    anselGOverlay = function(latlng, image_data) {
        this.src_ = image_data.icon;
        this.latlng_ = latlng;
        var img = new Image();
        img.src = image_data.icon;
        this.width_ = img.width;
        this.height_ = img.height;
        var z = GOverlay.getZIndex(this.latlng_.lat());
        this.div_ = new Element('div', {style: 'position:absolute;border:1px solid white;width:' + (this.width_ - 2) + 'px; height:' + (this.height_ - 2) + 'px;zIndex:' + z});
        this.img_ = new Element('img', {src: this.src_, style: 'width:' + (this.width_ - 2) + 'px;height:' + (this.height_ - 2) + 'px'});
        this.div_.appendChild(this.img_);
        this.selected_ = false;
    };
   anselGOverlay.prototype = new GOverlay();
   anselGOverlay.prototype.initialize =  function(map) {
        map.getPane(G_MAP_MARKER_PANE).appendChild(this.div_);
        this.map_ = map;
    };
    //Remove the main DIV from the map pane
   anselGOverlay.prototype.remove = function() {
      this.div_.parentNode.removeChild(this.div_);
    };
    // Copy our data to a new GOverlay
   anselGOverlay.prototype.copy = function() {
      return new Ansel_GOverlay(this.latlng_, this.src_);
    };
    anselGOverlay.prototype.redraw = function(force) {
        // We only need to redraw if the coordinate system has changed
        if (!force) return;
        var coords = this.map_.fromLatLngToDivPixel(this.latlng_);
        this.div_.style.left = coords.x + "px";
        this.div_.style.top  = coords.y + "px";
    };
    anselGOverlay.prototype.focus = function()
    {
        if (this.selected_ == false) {
            this.div_.style.border = '1px solid red';
            this.div_.style.left = (parseInt(this.div_.style.left) - 1) + "px";
            this.div_.style.top = (parseInt(this.div_.style.top) - 1) + "px";
            this.div_.style.zIndex = GOverlay.getZIndex(-90.0);
            this.selected_ = true;
        } else {
            this.div_.style.border = '1px solid white';
            this.div_.style.left = (parseInt(this.div_.style.left) + 1) + "px";
            this.div_.style.top = (parseInt(this.div_.style.top) + 1) + "px";
            this.div_.style.zIndex = GOverlay.getZIndex(this.latlng_.lat());
            this.selected_ = false;
        }
    };

});
