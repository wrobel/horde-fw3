<?php
/**
 * Ansel_XRequest_ToggleOtherGalleries:: class for performing Ajax setting of
 * the gallery show_actions user pref.
 *
 * $Horde: ansel/lib/XRequest/ToggleOtherGalleries.php,v 1.1.2.3 2009/06/29 04:17:09 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_ToggleOtherGalleries extends Ansel_XRequest {

    function Ansel_XRequest_ToggleOtherGalleries($params)
    {
        // Setup the variables the script will need, if we have any.
            $this->_jsVars['otherGalleriesWidget'] = array(
                'bindTo' => $params['bindTo']
            );

        parent::Ansel_XRequest($params);
    }

    function _attach()
    {
        // Include the js
        Horde::addScriptFile('togglewidget.js');

        $js = array();
        $js[] = "Event.observe(window, 'load', function() {Event.observe(otherGalleriesWidget.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo']  . "','ToggleOtherGalleries'); Event.stop(event)});});";
        $js[] = "if (typeof anselToggleUrl == 'undefined') { anselToggleUrl = '" . Horde::url('xrequest.php', true) . "';}";
        $this->_outputJS($js);
    }

    function handle($args)
    {
        $pref_value = $args['pref_value'];
        $GLOBALS['prefs']->setValue('show_othergalleries', $pref_value);
        header('Content-Type: text/plain');
        echo 1;
    }

}
