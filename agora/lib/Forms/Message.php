<?php
/**
 * $Horde: agora/lib/Forms/Message.php,v 1.6 2007/05/07 17:35:12 chuck Exp $
 *
 * @package Agora
 */

require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

/**
 * Message form class.
 *
 * @package Agora
 */
class MessageForm extends Horde_Form {

    function validate(&$vars, $canAutoFill = false)
    {
        global $conf;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!Auth::getAuth() && !empty($conf['forums']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->insertVariableBefore('newcomment', _("Spam protection"), 'captcha', 'figlet', true, null, null, array(Agora::getCAPTCHA(true), $conf['forums']['figlet_font']));
            }
            return false;
        }

        return true;
    }

    function &getRenderer($params = array())
    {
        $renderer = new Horde_Form_Renderer_MessageForm($params);
        return $renderer;
    }

}

/**
 * Message renderer class.
 *
 * @package Agora
 */
class Horde_Form_Renderer_MessageForm extends Horde_Form_Renderer {

    function _renderVarInputEnd(&$form, &$var, &$vars)
    {
        if ($var->hasDescription()) {
            // The description is actually the quote button
            echo ' ' . $var->getDescription();
        }
    }

    function close($focus = false)
    {
        echo '</form>' . "\n";

        if (Util::getGet('reply_focus')) {
            echo '<script type="text/javascript">document.getElementById("message_body").focus()</script>';
        }
    }

}
