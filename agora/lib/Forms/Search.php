<?php

require_once 'Horde/Form.php';

/**
 * Agora_Search:: class provides the functions & forms for search.
 *
 * $Horde: agora/lib/Forms/Search.php,v 1.9 2007/06/27 17:22:40 jan Exp $
 *
 * Copyright 2005-2007 Cronosys, LLC <http://www.cronosys.com>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jason Felice <jfelice@cronosys.com>
 * @package Agora
 */
class SearchForm extends Horde_Form {

    /**
    * Get list of available forums
    */
    function getForumsType($scope)
    {
        $forums = &Agora_Messages::singleton($scope);
        $forumlist = $forums->getBareForums();

        return array('multienum', array($forumlist));
    }

    /**
    * Set up forum object
    */
    function SearchForm(&$vars, $scope)
    {
        parent::Horde_Form($vars, _("Search Forums"));

        if ($scope == 'agora') {
            list($forumstype, $forumsparams) = $this->getForumsType($scope);
            $this->addVariable(_("Search in these forums"), 'forums', $forumstype,
                            false, false, null, $forumsparams);
        } else {
            $this->addHidden('', 'scope', 'text', false);
        }

        $this->addVariable(_("Keywords"), 'keywords', 'text', false);
        $var = &$this->addVariable(_("Require all keywords?"), 'allkeywords',
                                   'boolean', false);
        $var->setDefault(true);

        $var = &$this->addVariable(_("Search in subjects?"),
                                   'searchsubjects', 'boolean', false);
        $var->setDefault(true);

        $this->addVariable(_("Search in message contents?"), 'searchcontents',
                           'boolean', false);

        $this->addVariable(_("Author"), 'author', 'text', false);

        $this->setButtons(_("Search"));
    }

    /**
     * Trick getInfo to catch pager parameters
     */
    function getInfo($vars, &$info)
    {
        parent::getInfo($vars, $info);

        if (!$this->isSubmitted()) {
            foreach ($info as $key => $val) {
                $info[$key] = $vars->get($key);
            }
        }
    }

}