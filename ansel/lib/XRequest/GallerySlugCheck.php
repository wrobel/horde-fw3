<?php
/**
 * Ansel_XRequest_GallerySlugCheck:: class for performing Ajax validation of
 * gallery slugs.
 *
 * $Horde: ansel/lib/XRequest/GallerySlugCheck.php,v 1.5.2.1 2009/01/06 15:22:32 jan Exp $
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_GallerySlugCheck extends Ansel_XRequest {

    function Ansel_XRequest_GallerySlugCheck($params)
    {
        // Setup the variables the script will need, if we have any.
        if (count($params)) {
            $this->_jsVars['slugs'] = array(
                'slugText' => $params['slug'],
                'url' => Horde::url('xrequest.php', true),
                'bindTo' => $params['bindTo']);

            parent::Ansel_XRequest($params);
        }
    }

    function _attach()
    {
        // Include the js
        Horde::addScriptFile('slugcheck.js');
        $js = array();
        $js[] = "Event.observe(window, 'load', function() {Event.observe($('gallery_slug'), 'change', checkSlug);});";
        $js[] = "var slugText = slugs.slugText;";
        $this->_outputJS($js);
    }

    function handle($args)
    {
        $slug = $args['slug'];
        if (empty($slug)) {
            echo 1;
            exit;
        }
        $valid = preg_match('/^[a-zA-Z0-9_-]*$/', $slug);
        if (!$valid) {
            echo 0;
            exit;
        }

        echo $GLOBALS['ansel_storage']->slugExists($slug) ? 0 : 1;
    }

}
