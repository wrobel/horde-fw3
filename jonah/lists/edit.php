<?php
/**
 * Script to add or edit recipients for a list.
 *
 * $Horde: jonah/lists/edit.php,v 1.15 2008/05/31 21:15:00 chuck Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Delivery.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Action.php';
require_once 'Horde/Variables.php';

$news = Jonah_News::factory();

/* Set up the form variables. */
$vars = Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');
$delivery_type = $vars->get('delivery_type');

/* Get requested channel. */
$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, __FILE__, __LINE__, PEAR_LOG_ERR);
    $notification->push(_("Invalid channel."), 'horde.error');
    $url = Horde::applicationUrl('delivery/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check if allowed */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Set up the form. */
$form = new Horde_Form($vars);
$title = sprintf(_("Email subscription to \"%s\" stories"), $channel['channel_name']);
$form->setTitle($title);
$form->setButtons(_("Save"), true);

$form->addHidden('', 'channel_id', 'int', false);
$delivery_drivers = Jonah_Delivery::getDrivers();
if (count($delivery_drivers) > 1) {
    $v = &$form->addVariable(_("Delivery"), 'delivery_type', 'enum', true, false, null, array(Jonah_Delivery::getDrivers(), true));
    $v->setAction(Horde_Form_Action::factory('submit'));
    $v->setOption('trackchange', true);
} else {
    $delivery_type = key($delivery_drivers);
    $v = &$form->addVariable(_("Delivery"), 'delivery_type', 'text', false, true);
    $v->setDefault($delivery_drivers[$delivery_type]);
}

if (!empty($delivery_type)) {
    $delivery_params = Jonah_Delivery::getDeliveryParams($delivery_type);
    foreach ($delivery_params as $id => $param) {
        $param['required'] = isset($param['required']) ? $param['required']
                                                       : true;
        $param['readonly'] = isset($param['readonly']) ? $param['readonly']
                                                       : false;
        $param['desc'] = isset($param['desc']) ? $param['desc']
                                               : null;
        $param['params'] = isset($param['params']) ? $param['params']
                                                   : null;

        $form->addVariable($param['label'], $id, $param['type'], $param['required'], $param['readonly'], $param['desc'], $param['params']);
    }
}

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $delivery = &Jonah_Delivery::singleton($delivery_type);
    $delivery->saveRecipient($info);
    $notification->push(_("Recipient saved."), 'horde.success');
    $url = Util::addParameter('lists/index.php', 'channel_id', $info['channel_id']);
    $url = Horde::applicationUrl($url, true);
    header('Location: ' . $url);
    exit;
}

/* Render the form. */
$template = new Horde_Template();
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, 'edit.php', 'post'));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
