<?php
/**
 * The Agora search page.
 *
 * Copyright 2005-2007 Cronosys, LCC <http://www.cronosys.com>
 *
 * $Horde: agora/search.php,v 1.15.2.1 2008/01/02 04:10:59 chuck Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Forms/Search.php';
require_once 'Horde/Variables.php';
require_once 'Horde/UI/Pager.php';

/* Set up the forums object. */
$scope = Util::getGet('scope', 'agora');
$messages = &Agora_Messages::singleton($scope);
$vars = Variables::getDefaultVariables();
$form = new SearchForm($vars, $scope);
$thread_page = Util::getFormData('thread_page');

$template = new Agora_Template();
$template->setOption('gettext', true);

if ($form->isSubmitted() || $thread_page != null) {

    $form->getInfo($vars, $info);

    if (!empty($info['keywords'])) {
        $info['keywords'] = preg_split('/\s+/', $info['keywords']);
    }

    $sort_by = Agora::getSortBy('thread');
    $sort_dir = Agora::getSortDir('thread');
    $thread_per_page = $prefs->getValue('thread_per_page');
    $thread_start = $thread_page * $thread_per_page;

    $searchResults = $messages->search($info, $sort_by, $sort_dir, $thread_start, $thread_per_page);
    if (is_a($searchResults, 'PEAR_Error')) {
        $notification->push($searchResults->getMessage(), 'horde.error');
        header('Location:' . Horde::applicationUrl('search.php'));
        exit;
    }

    if ($searchResults['total'] > count($searchResults['results'])) {
        $pager_ob = new Horde_UI_Pager('thread_page', $vars, array('num' => $searchResults['total'], 'url' => 'search.php', 'perpage' => $thread_per_page));
        foreach ($info as $key => $val) {
            if ($val) {
                if ($key == 'keywords') {
                    $val = implode(' ', $val);
                }
                $pager_ob->preserve($key, $val);
            }
        }
        $template->set('pager_link', $pager_ob->render(), true);
    }

    $template->set('searchTotal', number_format($searchResults['total']));
    $template->set('searchResults', $searchResults['results']);
}

$template->set('menu', Agora::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('searchForm', Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'search.php', 'get'), true);

$title = _("Search Forums");
require AGORA_TEMPLATES . '/common-header.inc';
echo $template->fetch(AGORA_TEMPLATES . '/search/search.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
