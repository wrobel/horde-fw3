<?php
/**
 * This file contains all Horde_UI_VarRenderer extensions for Whups specific
 * form fields.
 *
 * $Horde: whups/lib/Forms/VarRenderer.php,v 1.1.2.1 2009/01/06 15:28:20 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_UI
 */

/** Horde_UI_VarRenderer */
require_once 'Horde/UI/VarRenderer.php';

/** Horde_UI_VarRenderer_html */
require_once 'Horde/UI/VarRenderer/html.php';

/** Imple */
require_once WHUPS_BASE . '/lib/Imple.php';

/**
 * The Horde_UI_VarRenderer_whups class provides additional methods for
 * rendering Horde_Form_Type_whups_email fields.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_UI
 */
class Horde_UI_VarRenderer_whups extends Horde_UI_VarRenderer_html {

    function _renderVarInput_whups_email($form, &$var, &$vars)
    {
        $name = $var->getVarName();
        Imple::factory('ContactAutoCompleter', array('triggerId' => $name));

        return sprintf('<input type="text" name="%s" id="%s" value="%s" autocomplete="off"%s />',
                       $name,
                       $name,
                       @htmlspecialchars($var->getValue($vars)),
                       $this->_getActionScripts($form, $var))
            . '<span id="' . $name . '_loading_img" style="display:none;">'
            . Horde::img('loading.gif', _("Loading..."), '',
                         $GLOBALS['registry']->getImageDir('horde'))
            . '</span><div id="' . $name
            . '_results" class="autocomplete"></div>';
    }

}
