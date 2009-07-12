<?php
/**
 * $Horde: ansel/map_edit.php,v 1.1.2.17 2009/07/12 00:06:37 mrubinsk Exp $
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
require_once ANSEL_BASE . '/lib/XRequest.php';
require_once 'Horde/Serialize.php';

/* Script includes */
Horde::addScriptFile('prototype.js', 'horde');

// Need to include autocomplete *here* in FW_3 code, since it's not a horde
// level js file here. In H4, it's in horde proper, and therefore loaded before
// googlemap_edit.js which overrides some of it's methods.
Horde::addScriptFile('autocomplete.js');
Horde::addScriptFile('googlemap.js');
Horde::addScriptFile('googlemap_edit.js');

$image_id = Util::getFormData('image');

/* Sanity checks, perms etc... */
if (empty($image_id)) {
    Horde::fatal(_("An error has occured retrieving the image. Details have been logged."), __FILE__, __LINE__, true);
}
$image = $ansel_storage->getImage($image_id);
if (is_a($image, 'PEAR_Error')) {
    Horde::fatal(_("An error has occured retrieving the image. Details have been logged."), __FILE__, __LINE__, true);
}
$gallery = $ansel_storage->getGallery($image->gallery);
if (is_a($gallery, 'PEAR_Error')) {
    Horde::fatal(_("An error has occured retrieving the image. Details have been logged."), __FILE__, __LINE__, true);
}
if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    Horde::fatal(_("Not Authorized. Details have been logged for the server administrator."), __FILE__, __LINE__, true);
}

/* Determine if we already have a geotag or are we tagging it for the 1st time */
if (empty($image->lat)) {
    $geodata = array('image_id' => $image->id,
                     'image_latitude' => "20",
                     'image_longitude' => "40",
                     'image_location' => '',
                     'icon' => Ansel::getImageUrl($image->id, 'mini', true),
                     'markerOnly' => true,
                     'draggable' => true);
    $isNew = 1;
} else {
    $geodata = array('image_id' => $image->id,
                     'image_latitude' => $image->lat,
                     'image_longitude' => $image->lng,
                     'image_location' => $image->location,
                     'icon' => Ansel::getImageUrl($image->id, 'mini', true),
                     'markerOnly' => true,
                     'draggable' => true);
    $isNew = 0;
}

/* JSON representation of the image's geotag */
$json = Horde_Serialize::serialize(array($geodata), SERIALIZE_JSON);

/* Gettext strings */
$save = _("Save");
$returnText = _("Return to Image View");
$findText = _("Find");
$fetchingText = _("Fetching location");
$locateText = _("Locate image at:");
$errorText = _("Unable to find location. Error code:");

/* Links, img src etc...  */
$returnLink = Ansel::getUrlFor('view', array('view' => 'Image',
                                             'image' => $image_id,
                                             'gallery' => $gallery->id));
$image_tag = '<img src="' . Ansel::getImageUrl($image_id, 'thumb', true) . '" />';
$xrequestUrl = Horde::applicationUrl('xrequest.php', true);
$loadingImg = Horde::img('loading.gif', _("Loading..."), '', $registry->getImageDir());

/* Obtain other geotagged images to possibly locate this image at */
$imgs = $GLOBALS['ansel_storage']->getRecentImagesGeodata(Auth::getAuth());
if (count($imgs) > 0) {
    $other_images = '<div class="ansel_location_sameas">' . _("Click on a thumbnail to locate at the same point.") . '<br />';
    foreach ($imgs as $id => $data) {
        if ($id != $image_id) {
            if (!empty($data['image_location'])) {
                $title = $data['image_location'];
            } else {
                $title = _point2Deg($data['image_latitude'], true) . ' ' . _point2Deg($data['image_longitude']);
            }
            $other_images .= Horde::link('#', $title, '', '', "mapEdit.setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "', '" . $data['image_location'] . "');return false") . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" /></a>';
        }
    }
    $other_images .= '</div>';
} else {
    $other_images = '';
}

/* Include the google scripts */
$sfiles = &Ansel_Script_Files::singleton();
$sfiles->addExternalScript('http://maps.google.com/maps?file=api&v=2.x&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps']);

/* Build the HTML */
$html = <<<EOT
<div id="status">&nbsp;</div>
<div style="width:450px;float:left;">
  <div id="ansel_map" style="width:450px;height:450px;"></div>
</div>
<div style="float:left;width:250px;min-height:450px;">
 <div class="control">
  <h4>{$locateText}</h4>
  <form>
   <input type="text" id="locationInput" name="locationInput" />
   <div id="locationInput_results" class="autocomplete"></div>
   <span id="locationInput_loading_img" style="display:none;">{$loadingImg}</span>
   <input id="locationAction" value="{$findText}" class="button" type="submit" />
  </form>
 </div>
 {$other_images}
 <div class="control" style="vertical-align:bottom;">
  <div style="text-align:center;margin-top:6px;">{$image_tag}</div>
  <div class="ansel_geolocation">
   <div id="ansel_locationtext">&nbsp;</div>
   <div id="ansel_latlng">&nbsp;</div>
  </div>
 </div>
</div>
<div class="clear"></div>
<div class="control">
 <input class="button" id="saveButton" type="submit" value="{$save}" /><input class="button" type="submit" onclick="window.close();" value="{$returnText}" />
</div>
<script type="text/javascript">
    var mapEdit;
    Event.observe(window, "load", function() {
        var options = {
            mainMap:  'ansel_map',
            xurl: '{$xrequestUrl}',
            image_id: {$image_id},
            gettext: {fetching: '{$fetchingText}', errortext: '{$errorText}'},
            points:  {$json},
            isNew: {$isNew},
            saveId: 'saveButton'
        };

        mapEdit = new Ansel_MapEdit(options);
        $('locationInput').focus();
    });
</script>
EOT;
/* Autocompleter for locations we already have in our DB */
$ac = Ansel_XRequest::factory('LocationAutoCompleter',
                              array('triggerId' => 'locationInput',
                                    'resultsId' => 'locationInput_results',
                                    'map' => 'mapEdit'));
$html .= Util::bufferOutput(array($ac, 'attach'));

/* Start the output */
include ANSEL_TEMPLATES . '/common-header.inc';
echo '<div class="header">' . sprintf(_("Update position of %s"), $image->filename) . '</div>';
echo $html;
require $registry->get('templates', 'horde') . '/common-footer.inc';

// Helper function for displaying Lat/Lng values
function _point2Deg($value, $lat = false)
{
    $letter = $lat ? ($value > 0 ? "N" : "S") : ($value > 0 ? "E" : "W");
    $value = abs($value);
    $deg = floor($value);
    $min = floor(($value - $deg) * 60);
    $sec = ($value - $deg - $min / 60) * 3600;
    return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
}
