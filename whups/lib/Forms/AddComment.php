<?php
/**
 * $Horde: whups/lib/Forms/AddComment.php,v 1.3 2008/08/05 22:48:30 bklang Exp $
 *
 * @package Whups
 */
class AddCommentForm extends Horde_Form {

    function AddCommentForm(&$vars, $title = '')
    {
        global $conf;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        if (!Auth::getAuth()) {
            $this->addVariable(_("Your Email Address"), 'user_email', 'email', true);
            if (!empty($conf['guests']['captcha'])) {
                $this->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null, array(Whups::getCAPTCHA(!$this->isSubmitted()), $conf['guests']['figlet_font']));
            }
        }
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);
        $this->addVariable(_("Attachment"), 'newattachment', 'file', false);
        $this->addVariable(_("Watch this ticket"), 'add_watch', 'boolean', false);

        /* Group restrictions. */
        if (Auth::isAdmin('whups:admin') ||
            $GLOBALS['perms']->hasPermission('whups:hiddenComments', Auth::getAuth(), PERMS_EDIT)) {
            $groups = &Group::singleton();
            $mygroups = $groups->getGroupMemberships(Auth::getAuth());
            if ($mygroups) {
                foreach (array_keys($mygroups) as $gid) {
                    $grouplist[$gid] = $groups->getGroupName($gid, true);
                }
                asort($grouplist);
                $grouplist = array_merge(array(0 => _("This comment is visible to everyone")), $grouplist);
                $this->addVariable(_("Make this comment visible only to members of a group?"), 'group', 'enum', true, false, null, array($grouplist));
            }
        }
    }

    function validate(&$vars, $canAutoFill = false)
    {
        global $conf;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!Auth::getAuth() && !empty($conf['guests']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->insertVariableBefore('newcomment', _("Spam protection"), 'captcha', 'figlet', true, null, null, array(Whups::getCAPTCHA(true), $conf['guests']['figlet_font']));
            }
            return false;
        }

        return true;
    }

}
