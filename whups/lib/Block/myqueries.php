<?php

$block_name = _("My Queries");

/**
 * Horde_Block_Whups_myqueries:: Implementation of the Horde_Block API
 * to show the current user's queries.
 *
 * $Horde: whups/lib/Block/myqueries.php,v 1.17 2008/07/10 16:22:15 jan Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_Whups_myqueries extends Horde_Block {

    var $_app = 'whups';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("My Queries");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once WHUPS_BASE . '/lib/Query.php';
        require_once WHUPS_BASE . '/lib/View.php';

        $qManager = new Whups_QueryManager();
        $myqueries = Whups_View::factory('SavedQueries',
                                         array('results' => $qManager->listQueries(Auth::getAuth(), true)));

        $html = Util::bufferOutput(array($myqueries, 'html'), false);
        if ($html) {
            return $html;
        }
        return '<p><em>' . _("No queries have been saved.") . '</em></p>';
    }

}
