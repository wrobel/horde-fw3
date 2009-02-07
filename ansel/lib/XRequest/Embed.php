<?php
/**
 * Ansel_XRequest_Embed:: Class for embedding a small gallery widget in external
 * websites. Meant to be called via a single script tag, therefore this will
 * always return nothing but valid javascript.
 *
 * $Horde: ansel/lib/XRequest/Embed.php,v 1.3.2.2 2009/01/06 15:22:32 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_XRequest_Embed extends Ansel_XRequest {

    function _attach()
    {
    }

    /**
     * Handles the output of the embedded widget. This must always be valid
     * javascript.
     *
     * @see Ansel_View_Embedded for parameters.
     *
     * @param array $args  Arguments for this view.
     */
    function handle($args)
    {
        /* First, determine the type of view we are asking for */
        $view = empty($args['gallery_view']) ? 'Mini' : $args['gallery_view'];

        require_once ANSEL_BASE . '/lib/Views/EmbeddedRenderers/' . basename($view) . '.php';
        $class = 'Ansel_View_EmbeddedRenderer_' . basename($view);
        if (!class_exists($class)) {
            return '';
        }

        $view = call_user_func(array($class, 'makeView'), $args);
        $return = $view->html();
        header('Content-Type: text/javascript');
        echo $return;
    }

}
