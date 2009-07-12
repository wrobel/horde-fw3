<?php
/**
 * Autocompleter for textual location data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_LocationAutoCompleter extends Ansel_XRequest {

    function Ansel_XRequest_LocationAutoCompleter($params)
    {
        if (!empty($params['triggerId'])) {
            if (empty($params['resultsId'])) {
                $params['resultsId'] = $params['triggerId'] . '_results';
            }
        }

        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('autocomplete.js');

        parent::Ansel_XRequest($params);
    }

    function _attach()
    {
        /* Use ajax? */
        if (!isset($_SESSION['ansel']['ajaxac'])) {
            $results = $GLOBALS['ansel_storage']->searchLocations();
            if (is_a($results, 'PEAR_Error')) {
                Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                // @TODO: This should be a config param?
                if (count($results) > 50) {
                    $_SESSION['ansel']['ajaxac'] = true;
                } else {
                    $_SESSION['ansel']['ajaxac'] = false;
                }
            }
        }

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"'
        );

        $js_params = array(
            'tokens: []',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"',
            'afterUpdateElement: function(e, v) {' . $this->_params['map'] . '.ll = ansel_ac.geocache[v.collectTextNodesIgnoreClass(\'informal\')];}',
            'afterUpdateChoices: function(c, l) {if (!c.size()) {' . $this->_params['map'] . '.ll = null;}}'
        );
        $js = array();
        $js[] = 'var ansel_ac;';
        if ($_SESSION['ansel']['ajaxac']) {
            $params[] = '"' . Horde::url($GLOBALS['registry']->get('webroot', 'ansel') . '/xrequest.php?requestType=LocationAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true) . '"';
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'document.observe(\'dom:loaded\', function() {ansel_ac = new Ajax.Autocompleter(' . implode(',', $params) . ');});';
        } else {
            if (empty($results)) {
                $results = $GLOBALS['ansel_storage']->searchLocations();
            }
            $jsparams[] = 'ignoreCase: true';
            $params[] = Horde_Serialize::serialize($results, SERIALIZE_JSON, NLS::getCharset());
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'document.observe(\'dom:loaded\', function() {ansel_ac = new Autocompleter.Local(' . implode(',', $params) . ');});';
        }
        $this->_outputJS($js);
    }

    function handle($args)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Util::getFormData($args['input']))) {
            return array();
        }
        $locs = $GLOBALS['ansel_storage']->searchLocations($input);
        if (is_a($locs, 'PEAR_Error')) {
            echo 0;
            exit;
        }

        $results = $locs;

        if (count($results) == 0) {
            $results = '{}';
        } else {
            $results = Horde_Serialize::serialize($results, SERIALIZE_JSON, NLS::getCharset());
        }
        header('Content-Type: application/json');
        echo $results;
        exit;
    }

}
