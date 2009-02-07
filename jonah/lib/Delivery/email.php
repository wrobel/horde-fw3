<?php
/**
 * Jonah_Delivery_email Class provides a Jonah delivery driver to mail the
 * results of a form to one or more recipients.
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * $Horde: jonah/lib/Delivery/email.php,v 1.32 2008/01/02 11:13:18 jan Exp $
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Jonah
 */
class Jonah_Delivery_email extends Jonah_Delivery {

    var $_lists;

    function Jonah_Delivery_email($params)
    {
        parent::Jonah_Delivery($params);

        /* Set up the lists object which will allow management of join/leave
         * requests and the subscriptions. */
        $delivery_params = array('delivery' => 'email');
        $this->_lists = &Jonah_Delivery_Lists::singleton($delivery_params);
    }

    /**
     * Handles the request to join or leave the delivery by email of news.
     * Prepares and sends an email requesting confirmation.
     *
     * @param array $request  The array containing the details of this request.
     */
    function processRequest($request)
    {
        global $conf;

        /* The 'recipient' index is the primary key when storing requests, so
         * set the email address value to that and remove the 'email' element
         * from the array since it is not needed in storage. */
        $request['recipient'] = $request['email'];
        unset($request['email']);

        /* Queue the request for confirmation. */
        $request_id = $this->_lists->queue($request);

        /* Set up the link for confirmation which will be emailed to the
         * recipient. */
        $url = Util::addParameter('delivery/email.php', array('confirm' => $request_id, 'to' => $request['recipient']));
        $confirm_link = Horde::applicationUrl($url, true, -1);

        $channel = $GLOBALS['news']->getChannel($request['channel_id']);

        /* Get the mailer object. */
        require_once 'Mail.php';
        $mailer = Mail::factory($conf['mailer']['type'],
                                $conf['mailer']['params']);

        /* Set up the email headers and body. */
        $headers['From'] = $conf['news']['email'];
        $headers['To'] = $request['recipient'];
        $recipients[] = $headers['To'];

        /* Based on type of action requested set up subject/body
         * accordingly. */
        if ($request['action'] == 'join') {
            $headers['Subject'] = sprintf(_("Request to receive news channel \"%s\""), $channel['channel_name']);
            /* Salutation. */
            $name = (!empty($request['name']) ? ' ' . $request['name'] : '');
            $body =  _("Hello") . $name . ",\n" . sprintf(_("You have requested to receive \"%s\" stories by email. Click on this link to confirm the request: %s"), $channel['channel_name'], $confirm_link);
        } elseif ($request['action'] == 'leave') {
            $headers['Subject'] = sprintf(_("Request to stop receiving news channel \"%s\""), $channel['channel_name']);
            $body = _("Hello") . ",\n" . sprintf(_("You have requested to stop receiving \"%s\" stories by email. Click on this link to confirm the request: %s"), $channel['channel_name'], $confirm_link);
        }

        /* Email the request for confirmation with link to confirm. */
        return $mailer->send($recipients, $headers, $body);
    }

    /**
     * Handles the confirmation of a join or leave request. Physically adds or
     * removes the recipient from the subscription lists.
     */
    function confirmRequest($request_id, $recipient)
    {
        $request = $this->_lists->getRequest($request_id);
        if ($recipient != $request['recipient']) {
            /* TODO: Error on bad recipient? Or silent? */
            return;
        }

        $request['id'] = $request_id;
        if ($request['action'] == 'join') {
            $success = $this->_lists->join($request);
        } else {
            $success = $this->_lists->leave($request);
        }

        /* If the join/leave was successful remove the request. */
        if ($success) {
            return $this->_lists->removeRequest($request_id);
        }
    }

    /**
     * Adds a recepient to the delivery list.
     *
     * @param array $recipient  The recipient's details as an array.
     */
    function saveRecipient($recipient)
    {
        return $this->_lists->join($recipient);
    }

    /**
     * Removes a recepient from the delivery list.
     *
     * @param array $recipient  The recipient's details as an array.
     */
    function removeRecipient($recipient)
    {
        return $this->_lists->leave($recipient);
    }

    /**
     * Actually carry out the delivery.
     */
    function _deliver($stories, $recipients)
    {
        global $conf;

        /* Get the required libs. */
        require_once JONAH_BASE . '/lib/version.php';
        require_once 'Horde/MIME/Mail.php';

        /* Loop through stories to build the small set of message objects that
         * we'll need. */
        $messages = array();
        foreach ($stories as $s => $story) {
            $messages[$s] = &Jonah_News::getStoryAsMessage($story);
        }

        /* Loop through recipients. */
        foreach ($recipients as $email => $info) {
            $mail = new MIME_Mail();
            $mail->addHeader('From', $conf['news']['email']);
            $mail->addHeader('To', $email);
            $subject = '';
            foreach ($stories as $s => $story) {
                if (!empty($subject)) {
                    $subject .= '; ';
                }
                $subject .= $story['story_title'];
                $mail->addMIMEPart($messages[$s]);
            }
            $mail->addHeader('Subject', $subject);
            $mail->addHeader('User-Agent', 'Jonah ' . JONAH_VERSION);
            $mail->send($conf['mailer']['type'], $conf['mailer']['params']);
        }
    }

    /**
     * Returns all the recipients for a channel.
     */
    function getRecipients($channel_id)
    {
        return $this->_lists->getRecipients($channel_id);
    }

    /**
     * Identifies this delivery driver and returns a brief description, used
     * by admin when configuring a delivery and set up using Horde_Form.
     *
     * @return array  Array of driver info.
     */
    function getInfo()
    {
        $info['name'] = _("Email");
        $info['desc'] = _("This driver allows the delivery of news stories via email to one or more recipients.");

        return $info;
    }

    /**
     * Returns the required parameters for this delivery driver, used by admin
     * when configuring deliveries and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getParams()
    {
        $params = array();
        $params['recipient'] = array('label' => _("Email"),
                                     'type'  => 'email');
        $params['name']  = array('label'    => _("Name"),
                                 'type'     => 'text',
                                 'required' => false);
        return $params;
    }

}
