<?php
/**
 * $Horde: agora/lib/Forms/Forum.php,v 1.4 2007/01/10 09:49:04 duck Exp $
 *
 * @package Agora
 */

/**
 * Load Form class.
 */
require_once 'Horde/Form.php';

/**
 * Forum management class.
 *
 * @package Agora
 */
class ForumForm extends Horde_Form {

    /**
     */
    function ForumForm(&$vars, $title)
    {
        global $forums, $conf;

        parent::Horde_Form($vars, $title);

        $forums_list = Agora::formatCategoryTree($forums->getForums(0, false, 'forum_name', 0, true));

        $this->setButtons($vars->get('forum_id') ? _("Update") : _("Create"));

        $this->addHidden('', 'forum_id', 'int', false);
        $this->addVariable(_("Forum name"), 'forum_name', 'text', true);

        if (count($forums_list) > 0) {
            $this->addVariable(_("Parent forum"), 'forum_parent_id', 'enum', false, false, null, array($forums_list, true));
        } else {
            $this->addHidden('', 'forum_parent_id', 'text', false);
            $vars->set('forum_parent_id', key($forums_list));
        }
        $this->addVariable(_("Enter a brief description of this forum"), 'forum_description', 'longtext', false, false, null, array(4, 40));
        $this->addVariable(_("Is this a moderated forum?"), 'forum_moderated', 'boolean', false, false, _("Set this if you want all messages to be checked by a moderator before they are posted."));
        if ($conf['forums']['enable_attachments'] == '0') {
            $this->addVariable(_("Allow attachments in this forum?"), 'forum_attachments', 'boolean', false, false, _("If selected users will be able to attach files to their messages."));
        }
    }

    /**
     */
    function execute(&$vars)
    {
        global $forums;

        $this->getInfo($vars, $info);
        return $forums->saveForum($info);
    }

}
