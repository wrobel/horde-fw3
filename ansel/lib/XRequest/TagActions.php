<?php
/**
 * Ansel_XRequest_TagActions:: class for handling adding/deleting tags via
 * Ajax calls.
 *
 * $Horde: ansel/lib/XRequest/TagActions.php,v 1.5.2.2 2009/01/06 15:22:32 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest_TagActions extends Ansel_XRequest {

    function Ansel_XRequest_TagActions($params)
    {
        // Setup the variables the script will need, if we have any.
        if (count($params)) {
            $this->_jsVars['tagActions'] = array(
                'url' => Horde::url('xrequest.php', true),
                'gallery' => $params['gallery'],
                'image' => (isset($params['image']) ? $params['image'] : 0),
                'bindTo' => $params['bindTo']
            );
        }

        parent::Ansel_XRequest($params);
    }

    function _attach()
    {
        // Include the js
        Horde::addScriptFile('tagactions.js');

        $js = array();
        // TODO: Attach the delete actions too
        $js[] = "Event.observe(window, 'load', function() {Event.observe(tagActions.bindTo.add, 'click', function(event) {addTag(); Event.stop(event)});});";
        $this->_outputJS($js);
    }

    function handle($args)
    {
        global $ansel_storage;

        $request = $args['action'];
        require_once ANSEL_BASE . '/lib/Tags.php';

        $gallery = $args['gallery'];
        $image = isset($args['image']) ? $args['image'] : null;

        if ($image) {
            $id = $image;
            $type = 'image';
        } else {
            $id = $gallery;
            $type = 'gallery';
        }
        $tags = $args['tags'];

        if (!is_numeric($id)) {
            break;
        }
        /* Get the resource owner */
        if ($type == 'gallery') {
            $resource = $ansel_storage->getGallery($id);
            $parent = $resource;
        } else {
            $resource = $ansel_storage->getImage($id);
            $parent = $ansel_storage->getGallery($resource->gallery);
        }
        $owner = $parent->get('owner');

        switch ($request) {
        case 'add':
            if (!empty($tags)) {
                $tags = explode(',', $tags);
                /* Get current tags so we don't overwrite them */
                $etags = Ansel_Tags::readTags($id, $type);
                $tags = array_keys(array_flip(array_merge($tags, array_values($etags))));
                $resource->setTags($tags);

                /* Get the tags again since we need the newly added tag_ids */
                $newTags = $resource->getTags();
                if (count($newTags)) {
                    $newTags = Ansel_Tags::listTagInfo(array_keys($newTags));
                }
                echo $this->_getTagHtml($newTags, $owner,
                                        $parent->hasPermission(Auth::getAuth(),
                                                               PERMS_EDIT));
            }
            break;

        case 'remove':
            $existingTags = $resource->getTags();
            unset($existingTags[$tags]);
            $resource->setTags($existingTags);
            if (count($existingTags)) {
                $newTags = Ansel_Tags::listTagInfo(array_keys($existingTags));
            } else {
                $newTags = array();
            }
            echo $this->_getTagHtml($newTags, $owner,
                                    $parent->hasPermission(Auth::getAuth(),
                                                           PERMS_EDIT));
            break;
        }
    }

    function _getTagHtml($tags, $owner, $hasEdit)
    {
        global $registry;
        $links = Ansel_Tags::getTagLinks($tags, 'add');
        $html = '<ul>';
        foreach ($tags as $tag_id => $taginfo) {
            $html .= '<li>' . Horde::link($links[$tag_id], sprintf(ngettext("%d photo", "%d photos", $taginfo['total']), $taginfo['total'])) . $taginfo['tag_name'] . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag"), '', $registry->getImageDir('horde')) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

}
