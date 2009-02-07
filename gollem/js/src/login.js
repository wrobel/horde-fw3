/**
 * Provides the javascript for the login.php script.
 *
 * $Horde: gollem/js/src/login.js,v 1.3.2.1 2008/10/09 20:54:41 jan Exp $
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

function setFocus()
{
    if (document.gollem_login.username) {
        document.gollem_login.username.focus();
    }
}

function gollem_reload()
{
    var url = reload_url + document.gollem_login.backend_key[document.gollem_login.backend_key.selectedIndex].value;
    if (document.gollem_login.url &&
        document.gollem_login.url.value) {
        url += '&url=' + document.gollem_login.url.value;
    }
    window.location = url;
}

function submit_login()
{
    if (document.gollem_login.username &&
        document.gollem_login.username.value == "") {
        alert(GollemText.login_username);
        document.gollem_login.username.focus();
        return false;
    } else if (document.gollem_login.password &&
               document.gollem_login.password.value == "") {
        alert(GollemText.login_password);
        document.gollem_login.password.focus();
        return false;
    } else {
        document.gollem_login.loginButton.disabled = true;
        if (ie_clientcaps) {
            try {
                document.gollem_login.ie_version.value = objCCaps.getComponentVersion("{89820200-ECBD-11CF-8B85-00AA005B4383}","componentid");
            } catch (e) { }
        }
        document.gollem_login.submit();
        return true;
    }
}

function selectLang()
{
    // We need to reload the login page here, but only if the user hasn't
    // already entered a username and password.
    var lang_page = 'login.php?new_lang=' + document.gollem_login.new_lang[document.gollem_login.new_lang.selectedIndex].value;
    if (lang_url !== null) {
        lang_page += '&url=' + lang_url;
    }
    self.location = lang_page;
}

/* Removes any leading hash that might be on a location string. */
function removeHash(h) {
    if (h == null || h == undefined) {
        return null;
    } else if (h.length && h.charAt(0) == '#') {
        if (h.length == 1) {
            return "";
        } else {
            return h.substring(1);
        }
    }
    return h;
}

Event.observe(window, 'load', function() {
    if (gollem_auth && parent.frames.horde_main) {
        if (nomenu) {
            parent.location = self.location;
        } else {
            document.gollem_login.target = '_parent';
        }
    }

    // Need to capture hash information if it exists in URL
    if (location.hash) {
        $('anchor_string').value = removeHash(location.hash);
    }
});
