<?php
/**
 * Ansel_Widget_links:: class to wrap the display of various feed links etc...
 *
 * $Horde: ansel/lib/Widget/Links.php,v 1.7.2.8 2009/04/24 16:11:58 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Links extends Ansel_Widget {

    function Ansel_Widget_Links($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("Links");
    }

    function html()
    {
        global $registry;

        $feedurl = Horde::url('rss.php', true);
        $owner = $this->_view->gallery->get('owner');
        $html = $this->_htmlBegin();
        $html .= Horde::link(Ansel::getUrlFor('rss_user', array('owner' => $owner))) . Horde::img('feed.png', '', '', $registry->getImageDir('horde')) . ' ' . sprintf(_("Recent photos by %s"), $owner) . '</a>';
        $slug = $this->_view->gallery->get('slug');
        $html .= '<br />' . Horde::link(Ansel::getUrlFor('rss_gallery', array('gallery' => $this->_view->gallery->id, 'slug' => $slug))) . ' ' .  Horde::img('feed.png', '', '', $registry->getImageDir('horde')) . ' ' . sprintf(_("Recent photos in %s"), htmlspecialchars($this->_view->gallery->get('name'))) . '</a>';

        /* Embed html */
        $src = 'xrequest.php?requestType=Embed';
        if (empty($this->_view->_params['image_id'])) {
            if (!empty($slug))  {
                $src .= '/gallery_slug=' . $slug . '/count=10';
            } else {
                $src .= '/gallery_id=' . $this->_view->gallery->id . '/count=10';
            }
        } else {
            // This is an image view
            $src .= '/thumbsize=screen/images=' . $this->_view->_params['image_id'];
        }
        $id = md5(uniqid(''));
        $src .= '/container=ansel' . $id;
        $src = Horde::applicationUrl($src, true);

        $embed =  htmlentities('<script type="text/javascript" src="' . $src . '"></script>'
            . '<div id="ansel' . $id . '"></div>');

        $html .= '<div class="embedInput">' . _("Embed: ") . '<br /><input type="text" readonly="readonly" value="' . $embed
            . '" /></div>';

        $html .= $this->_htmlEnd();
        return $html;
    }

}
?>
