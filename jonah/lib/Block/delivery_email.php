<?php

$block_name = _("News by Email");

/**
 * This class extends Horde_Block:: to provide the api to embed an email
 * delivery signup box for news.
 *
 * $Horde: jonah/lib/Block/delivery_email.php,v 1.23 2008/02/05 11:07:11 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Block
 */
class Horde_Block_Jonah_delivery_email extends Horde_Block {

    var $_app = 'jonah';

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $params['channel'] = array('name'     => _("News Channel"),
                                   'type'     => 'enum',
                                   'required' => false,
                                   'values'   => array());

        $params['channel']['values'] = Horde_Block_Jonah_delivery_email::_getChannelSelection();

        return $params;
    }

    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $news = Jonah_News::factory();
        if (empty($this->_params['channel'])) {
            $name = $GLOBALS['registry']->get('name', 'horde');
        } else {
            $channel = $news->getChannel($this->_params['channel']);
            $name = $channel['channel_name'];
        }

        return sprintf(_("%s by Email"), @htmlspecialchars($name, ENT_COMPAT, NLS::getCharset()));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';
        require_once 'Horde/Form.php';
        require_once 'Horde/Variables.php';
        require_once 'Horde/Form/Renderer.php';

        $channel_select = $this->_getChannelSelection();
        if (!$channel_select) {
            return _("No channels available.");
        }

        $vars = array('action'     => 'join',
                      'channel_id' =>  $this->_params['channel'],
                      'url'        => $_SERVER['REQUEST_URI'],
                      'external'   => 1);

        if (isset($_SERVER['QUERY_STRING'])) {
            $vars['url'] .= '?' . $_SERVER['QUERY_STRING'];
        }

        $vars = new Variables($vars);

        $form = new Horde_Form($vars);
        $form->setButtons(_("Save"), true);

        $form->addHidden('', 'action', 'text', false);
        $form->addHidden('', 'url', 'text', false);
        if (empty($this->_params['channel'])) {
            $form->addVariable(_("Channel"), 'channel_id', 'enum', true, false, null, array($channel_select));
        } else {
            $form->addHidden('', 'channel_id', 'int', false);
        }

        /* External makes sure the receiving form knows that the submission is
         * coming in from another external form. */
        $form->addHidden('', 'external', 'text', false);

        $form->addVariable(_("Email address"), 'email', 'email', true);
        $form->addVariable(_("Name"), 'name', 'text', false, false, null, array('', 20));


        $url = Horde::applicationUrl('delivery/email.php');
        return Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, $url, 'post');
    }

    function _getChannelSelection()
    {
        $news = Jonah_News::factory();
        $channels = $news->getChannels();
        if (is_a($channels, 'PEAR_Error')) {
            Horde::logMessage('Could not fetch channels: ' . $channels->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
        $values = array();
        foreach ($channels as $channel) {
            $values[$channel['channel_id']] = $channel['channel_name'];
        }
        natcasesort($values);
        return $values;
    }

}
