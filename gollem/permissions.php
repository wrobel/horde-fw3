<?php
/**
 * Gollem permissions administration page.

 * $Horde: gollem/permissions.php,v 1.2.2.9 2008/10/13 09:32:22 jan Exp $
 *
 * Copyright 2005-2007 Vijay Mahrra <vijay.mahrra@es.easynet.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('GOLLEM_BASE', dirname(__FILE__));
require_once GOLLEM_BASE . '/lib/base.php';

if (!Auth::isAdmin()) {
    Horde::authenticationFailureRedirect();
}

if (!Gollem::getBackends('all')) {
    $title = _("Gollem Backend Permissions Administration");
    require GOLLEM_TEMPLATES . '/common-header.inc';
    Gollem::menu();
    Gollem::status();
    $notification->push(_("You need at least one backend defined to set permissions."), 'horde.error');
    $notification->notify();
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Edit permissions for the preferred backend if none is selected. */
$key = Util::getFormData('backend', Gollem::getPreferredBackend());
$app = $registry->getApp();
$backendTag = $app . ':backends:' . $key;
if ($perms->exists($backendTag)) {
    $permission =& $perms->getPermission($backendTag);
    $perm_id = $perms->getPermissionId($permission);
} else {
    $permission =& $perms->newPermission($backendTag);
    $result = $perms->addPermission($permission, $app);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Unable to create backend permission: %s"), $result->getMessage()), 'horde.error');
        header('Location: ' . Horde::applicationUrl('redirect.php', true));
        exit;
    }
    $perm_id = $perms->getPermissionId($permission);
    $notification->push(sprintf(_("Created empty permissions for \"%s\". You must explicitly grant access to this backend now."), $key), 'horde.warning');
}

/* Redirect to horde permissions administration interface. */
$url = Util::addParameter($registry->get('webroot', 'horde') . '/admin/perms/edit.php', 'perm_id', $permission->getId());
header('Location: ' . Horde::url($url, true));