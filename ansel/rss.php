<?php
/**
 * Ansel RSS feed. Note that we always return a 'normal' thumb image
 * and not a prettythumb since we have no way of knowing what the client
 * requesting this will be viewing the image on.
 *
 * $Horde: ansel/rss.php,v 1.47.2.1 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

$session_control = 'readonly';
require_once dirname(__FILE__) . '/lib/base.php';
require_once ANSEL_BASE . '/lib/version.php';

// Get form data
$stream_type = Util::getFormData('stream_type', 'all');
$id = Util::getFormData('id');
$type = basename(Util::getFormData('type', 'rss2'));
$slug = Util::getFormData('slug');
$uid = md5($stream_type . $id . $type . Auth::getAuth());
$filename = 'ansel_feed_template_' . $uid;
if ($conf['ansel_cache']['usecache']) {
    $cache_key = 'ansel_feed_template_' . $uid;
    $rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
    $filename = $cache->get($filename, $conf['cache']['default_lifetime']);
}

if (empty($rss)) {
    // Assume failure
    $params = array('last_modified' => time(),
                    'name' => _("Error retrieving feed"),
                    'link' => '',
                    'desc' => _("Unable to retrieve requested feed"),
                    'image_url' => Horde::img('alerts/error.png', '', '',
                                              $registry->getImageDir('horde')),
                    'image_link' => '',
                    'image_alt' => '');
    $author = '';

    // Determine what we are requesting
    // @TODO - category
    switch ($stream_type) {
    case 'all':
        $images = $ansel_storage->getRecentImages();
        if (is_a($images, 'PEAR_Error') || !count($images)) {
            $images = array();
        } else {
            // Eventually would like the link to link to a search
            // results page containing the same images present in this
            // feed. For now, just link to the List view until some of
            // the search code can be refactored.
            $params = array('last_modified' => $images[0]->uploaded,
                            'name' => sprintf(_("Recently added photos on %s"),
                                              $conf['server']['name']),
                            'link' => Ansel::getUrlFor('view',
                                                       array('view' => 'List'),
                                                       true),
                            'desc' => sprintf(_("Recently added photos on %s"),
                                              $conf['server']['name']),
                            'image_url' => Ansel::getImageUrl($images[0]->id,
                                                              'thumb', true),
                            'image_alt' => $images[0]->caption,
                            'image_link' => Ansel::getUrlFor(
                                'view', array('image' => $images[0]->id,
                                              'view' => 'Image',
                                              'gallery' => $images[0]->gallery),
                                true));
        }
        break;

    case 'gallery':
        // Retrieve latest from specified gallery
        // Try a slug first.
        if ($slug) {
            $gallery = $ansel_storage->getGalleryBySlug($slug);
        } elseif (is_numeric($id)) {
            $gallery = $ansel_storage->getGallery($id);
        }
        if (!is_a($gallery, 'PEAR_Error') &&
            $gallery->hasPermission(Auth::getAuth(), PERMS_SHOW)) {
            if (!$gallery->countImages() && $gallery->hasSubGalleries()) {
                $subgalleries = $ansel_storage->listGalleries(PERMS_SHOW,
                                                              null,
                                                              $gallery);
                $subs = array();
                foreach ($subgalleries as $subgallery) {
                    $subs[] = $subgallery->id;
                }
                $images = $ansel_storage->getRecentImages($subs);
            } else {
                $images = $gallery->getRecentImages();
                $owner = &$gallery->getOwner();
                $author = $owner->getValue('from_addr');
            }
        }

        if (!isset($images) || is_a($images, 'PEAR_Error') || !count($images)) {
            $images = array();
        } else {
            $style = $gallery->getStyle();
            $viewurl = Ansel::getUrlFor('view',
                                        array('view' => 'Gallery',
                                              'gallery' => $id),
                                        true);
            $img = &$ansel_storage->getImage($gallery->getDefaultImage('ansel_default'));

            $params = array('last_modified' => $gallery->get('last_modified'),
                            'name' => sprintf(_("%s on %s"),
                                              $gallery->get('name'),
                                              $conf['server']['name']),
                            'link' => $viewurl,
                            'desc' => $gallery->get('desc'),
                            'image_url' => Ansel::getImageUrl($img->id, 'thumb',
                                                              true, 'ansel_default'),
                            'image_alt' => $img->caption,
                            'image_link' => Ansel::getUrlFor('view',
                                                             array('image' => $img->id,
                                                                   'gallery' => $img->gallery,
                                                                   'view' => 'Image'),
                                                             true));
        }
        break;

    case 'user':
        $shares = $ansel_storage->listGalleries(PERMS_SHOW, $id);
        if (!is_a($shares, 'PEAR_Error')) {
            $galleries = array();
            foreach ($shares as $gallery) {
                $galleries[] = $gallery->id;
            }
        }
        $images = array();
        if (isset($galleries) && count($galleries)) {
            $images = $ansel_storage->getRecentImages($galleries);
            if (!is_a($images, 'PEAR_Error') && count($images)) {
                require_once('Horde/Identity.php');
                $owner = &Identity::singleton('none', $id);
                $name = $owner->getValue('fullname');
                $author = $owner->getValue('from_addr');
                if (!$name) {
                    $name = $id;
                }
                $params = array('last_modified' => $images[0]->uploaded,
                                'name' => sprintf(_("Photos by %s"),
                                                  $name),
                                'link' => Ansel::getUrlFor('view',
                                                           array('view' => 'List'),
                                                           true),
                                'desc' => sprintf(_("Recently added photos by %s on %s"),
                                                  $name, $conf['server']['name']),
                                'image_url' => Ansel::getImageUrl($images[0]->id,
                                                                  'thumb', true,
                                                                  'ansel_default'),
                                'image_alt' => $images[0]->caption,
                                'image_link' => Ansel::getUrlFor(
                                    'view', array('image' => $images[0]->id,
                                                  'gallery' => $images[0]->gallery,
                                                  'view' => 'Image'), true)
                );
            }
        }
        break;

    case 'tag':
        require_once ANSEL_BASE . '/lib/Tags.php';
        $tag_id = array_values(Ansel_Tags::getTagIds(array($id)));
        $images = Ansel_Tags::searchTagsById($tag_id, 10, 0, 'images');
        $tag_id = array_pop($tag_id);
        $images = $ansel_storage->getImages($images['images']);
        if (!is_a($images, 'PEAR_Error') && count($images)) {
            $tag_id = $tag_id[0];
            $images = array_values($images);
            $params = array('last_modified' => $images[0]->uploaded,
                            'name' => sprintf(_("Photos tagged with %s on %s"),
                                              $id, $conf['server']['name']),
                            'link' => Ansel::getUrlFor('view',
                                                       array('tag' => $id,
                                                             'view' => 'Results'),
                                                       true),
                            'desc' => sprintf(_("Photos tagged with %s on %s"),
                                              $id, $conf['server']['name']),
                            'image_url' => Ansel::getImageUrl($images[0]->id,
                                                              'thumb', true,
                                                              'ansel_default'),
                            'image_alt' => $images[0]->caption,
                            'image_link' => Ansel::getUrlFor('view',
                                                             array('view' => 'Image',
                                                                   'image' => $images[0]->id,
                                                                   'gallery' => $images[0]->gallery),
                                                             true)
                      );
        }
    }

    $imgs = array();
    $cnt = count($images);
    for ($i = 0; $i < $cnt; ++$i) {
        $imgs[$i]['link'] = Ansel::getUrlFor(
            'view',
            array('view' => 'Image',
                  'gallery' => $images[$i]->gallery,
                  'image' => $images[$i]->id), true);
        $imgs[$i]['filename'] = $images[$i]->filename;
        $imgs[$i]['caption'] = $images[$i]->caption;
        $imgs[$i]['url'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'screen', true));
        $imgs[$i]['type'] = $images[$i]->getType('screen');
        $imgs[$i]['author'] = $author;
        $imgs[$i]['thumb'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'thumb', true));
    }

    $charset = NLS::getCharset();
    $xsl = $registry->get('themesuri') . '/feed-rss.xsl';
    $stream_name = @htmlspecialchars($params['name'], ENT_COMPAT, NLS::getCharset());
    $stream_desc = @htmlspecialchars($params['desc'], ENT_COMPAT, NLS::getCharset());
    $stream_updated = htmlspecialchars(date('r', $params['last_modified']));
    $stream_official = htmlspecialchars($params['link']);
    $image_url = htmlspecialchars($params['image_url']);
    $image_link = htmlspecialchars($params['image_link']);
    $image_alt = htmlspecialchars($params['image_alt']);
    $ansel = 'Ansel ' . ANSEL_VERSION . ' (http://www.horde.org/)';

    if ($stream_type != 'all' && $type != 'rss2') {
        $getparams = array('stream_type' => $stream_type, 'type' => $type);
        if (isset($id)) {
            $getparams['id'] = $id;
        }
    } else {
        $getparams = array();
    }
    $stream_rss = Util::addParameter(Horde::applicationUrl('rss.php', true, -1), $getparams);
    $stream_rss2 = Util::addParameter(Horde::applicationUrl('rss.php', true, -1), $getparams);
    $images = $imgs;

    ob_start();
    include ANSEL_TEMPLATES . '/rss/' . $type . '.inc';
    $rss = ob_get_clean();

    if ($conf['ansel_cache']['usecache']) {
        $cache->set($cache_key, $rss);
        $cache->set($filename, $params['name']);
    }
}

$browser->downloadHeaders($filename . '.rss', 'text/xml', true);
echo $rss;
