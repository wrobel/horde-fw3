<?php
/* Tagger */
require_once ANSEL_BASE . '/lib/Tags.php';

/**
 * Autocompleter for tags.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_TagAutoCompleter extends Ansel_XRequest {

    function Ansel_XRequest_TagAutoCompleter($params)
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
        if (!isset($_SESSION['ansel']['tag_ajaxac'])) {
            $results = $GLOBALS['ansel_storage']->searchLocations();
            if (is_a($results, 'PEAR_Error')) {
                Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                // @TODO: This should be a config param?
                if (count($results) > 100) {
                    $_SESSION['ansel']['tag_ajaxac'] = true;
                } else {
                    $_SESSION['ansel']['tag_ajaxac'] = false;
                }
            }
        }

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"'
        );

        $js_params = array(
            'tokens: [","]',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"'
        );
        $js = array();
        $js[] = 'var ansel__tagac;';
        if ($_SESSION['ansel']['tag_ajaxac']) {
            $params[] = '"' . Horde::url($GLOBALS['registry']->get('webroot', 'ansel') . '/xrequest.php?requestType=TagAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true) . '"';
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'document.observe(\'dom:loaded\', function() {ansel_tagac = new Ajax.Autocompleter(' . implode(',', $params) . ');});';
        } else {
            if (empty($results)) {
                $results = Ansel_Tags::listTags();
            }
            $jsparams[] = 'ignoreCase: true';
            $params[] = Horde_Serialize::serialize($results, SERIALIZE_JSON, NLS::getCharset());
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'document.observe(\'dom:loaded\', function() {ansel_tagac = new Autocompleter.Local(' . implode(',', $params) . ');});';
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
        $results = Ansel_Tags::listTags($input, 10);
        if (is_a($results, 'PEAR_Error')) {
            echo 0;
            exit;
        }

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
