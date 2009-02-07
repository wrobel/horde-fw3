<?php
/**
 * $Horde: ansel/img/ecard.php,v 1.9.2.1 2009/01/06 15:22:24 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';
require_once ANSEL_BASE . '/lib/Forms/Ecard.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

/* Abort if ecard sending is disabled. */
if (empty($conf['ecard']['enable'])) {
    exit;
}

/* Get the gallery and the image, and abort if either fails. */
$gallery = $ansel_storage->getGallery(Util::getFormData('gallery'));
if (is_a($gallery, 'PEAR_Error')) {
    exit;
}
$image = &$gallery->getImage(Util::getFormData('image'));
if (is_a($image, 'PEAR_Error')) {
    exit;
}

/* Run through the action handlers. */
switch (Util::getFormData('actionID')) {
case 'send':
    /* Check for required elements. */
    $from = Util::getFormData('ecard_retaddr');
    if (empty($from)) {
        $notification->push(_("You must enter your e-mail address."), 'horde.error');
        break;
    }
    $to = Util::getFormData('ecard_addr');
    if (empty($to)) {
        $notification->push(_("You must enter an e-mail address to send the message to."), 'horde.error');
        break;
    }

    require_once 'Horde/MIME/Headers.php';
    require_once 'Horde/MIME/Message.php';

    /* Set up the mail headers and read the log file. */
    $msg_headers = new MIME_Headers();
    $msg_headers->addReceivedHeader();
    $msg_headers->addMessageIdHeader();
    $msg_headers->addAgentHeader();
    $msg_headers->addHeader('Date', date('r'));
    $msg_headers->addHeader('From', $from);
    $msg_headers->addHeader('To', $to);
    $msg_headers->addHeader('Subject', _("Ecard - ") . Util::getFormData('image_desc'));

    $alt = new MIME_Message();
    $alt->setType('multipart/alternative');

    /* Create the text/plain part that explains to the user that they need
     * to be able to view the Ecard inline. */
    $textpart = new MIME_Part('text/plain');
    $textpart->setCharset(NLS::getCharset());
    $textpart->setContents(String::wrap(_("You have been sent an Ecard. To view the Ecard, you must be able to view text/html messages in your mail reader. If you are viewing this message, then most likely your mail reader does not support viewing text/html messages.")));
    $alt->addPart($textpart);

    /* Create the multipart/related part. */
    $related = new MIME_Part('multipart/related');

    /* Create the multipart/related part. */
    $htmlpart = new MIME_Part('text/html');
    $htmlpart->setCharset(NLS::getCharset());
    $imgpart = new MIME_Part($image->getType('screen'));
    $imgpart->setContents($image->raw('screen'));
    $img_tag = '<img src="cid:' . $imgpart->setContentID() . '" /><p />';
    $comments = $htmlpart->replaceEOL(Util::getFormData('ecard_comments'));
    if (!Util::getFormData('rtemode')) {
        $comments = '<pre>' . htmlspecialchars($comments) . '</pre>';
    }
    $htmlpart->setContents(String::wrap('<html>' . $img_tag . $comments . '</html>'));
    $related->setContentTypeParameter('start', $htmlpart->setContentID());
    $related->addPart($htmlpart);
    $related->addPart($imgpart);

    $alt->addPart($related);

    $msg_headers->addMIMEHeaders($alt);

    $result = $alt->send(Util::getFormData('ecard_addr'), $msg_headers);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error sending your message: %s"), $result->getMessage()), 'horde.error');
    } else {
        Util::closeWindowJS();
        exit;
    }
}

$title = sprintf(_("Send Ecard :: %s"), $image->filename);

/* Set up the form object. */
$vars = Variables::getDefaultVariables();
$vars->set('actionID', 'send');
$form = new EcardForm($vars, $title);
$renderer = new Horde_Form_Renderer();

$vars->set('image_desc', $image->caption);
$form->addHidden('', 'image_desc', 'text', false);

if ($browser->hasFeature('rte')) {
    require_once 'Horde/Editor.php';
    $editor = Horde_Editor::factory('xinha', array('id' => 'ecard_comments'));
    $vars->set('rtemode', 1);
    $form->addHidden('', 'rtemode', 'text', false);
}

require ANSEL_TEMPLATES . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, 'ecard.php', 'post', 'multipart/form-data');
require $registry->get('templates', 'horde') . '/common-footer.inc';
