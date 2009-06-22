<?php
/**
 * Ansel_XRequest_EditCaption:: class for performing Ajax setting of image
 * captions
 *
 * $Horde: ansel/lib/XRequest/EditCaption.php,v 1.12.2.4 2009/06/19 17:03:11 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_EditCaption extends Ansel_XRequest {

    function Ansel_XRequest_EditCaption($params)
    {
        /* Set up some defaults */
        if (empty($params['rows'])) {
            $params['rows'] = 2;
        }
        if (empty($params['cols'])) {
            $params['cols'] = 20;
        }
        parent::Ansel_XRequest($params);
    }

    function _attach()
    {
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('controls.js', 'horde', true);
        Horde::addScriptFile('editcaption.js', 'ansel', true);

        $js = array();
        $url = Horde::applicationUrl('xrequest.php');

        $js[] = "document.observe('dom:loaded', function() { "
                . "  ipe" . $this->_params['id'] . " = new Ajax.InPlaceEditor('" . $this->_params['domid'] . "', '" . $url . "', {"
                . "    callback: function(form, value) {"
                . "      return 'requestType=EditCaption/input=value/id=" . $this->_params['id'] . "&value=' + encodeURIComponent(value);},"
                . "   loadTextURL: '". $url . "?requestType=EditCaption/action=load/id=" . $this->_params['id'] . "',"
                . "   rows:" . $this->_params['rows'] . ","
                . "   cols:" . $this->_params['cols'] . ","
                . "   highlightcolor:'none',"
                . "   emptyText: '" . _("Click to add caption...") . "',"
                . "   onComplete: function(transport, element) {tileExit(this);}"
                . "  });});";

        $this->_outputJS($js);
    }

    function handle($args)
    {
        if (Auth::getAuth()) {
            /* Are we requesting the unformatted text? */
            if (!empty($args['action']) && $args['action'] == 'load') {
                $id = $args['id'];
                $image = $GLOBALS['ansel_storage']->getImage($id);
                $caption = $image->caption;
                echo $caption;
                exit;
            }
            if (empty($args['input']) ||
                is_null($pref_value = Util::getPost($args['input'], null)) ||
                empty($args['id']) || !is_numeric($args['id'])) {
                    exit;
            }
            $id = $args['id'];
            $image = $GLOBALS['ansel_storage']->getImage($id);
            $g = $GLOBALS['ansel_storage']->getGallery($image->gallery);
            if ($g->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
                $image->caption = $pref_value;
                $result = $image->save();
                if (is_a($result, 'PEAR_Error')) {
                    exit;
                }
            }
            require_once 'Horde/Text/Filter.php';
            $imageCaption = Text_Filter::filter(
                $image->caption, 'text2html',
                array('parselevel' => TEXT_HTML_MICRO));
            echo $imageCaption;
        }
    }

}
