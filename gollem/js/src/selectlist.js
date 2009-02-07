/**
 * Provides the javascript for the selectlist.php script.
 *
 * $Horde: gollem/js/src/selectlist.js,v 1.1.2.1 2008/10/09 20:54:41 jan Exp $
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
function returnID()
{
    var field = parent.opener.document[formid].selectlist_selectid, field2 = parent.opener.document[formid].actionID;

    if (parent.opener.closed || !field || !field2) {
        alert(GollemText.opener_window);
        window.close();
        return;
    }

    field.value = cacheid;
    field2.value = 'selectlist_process';

    parent.opener.document[formid].submit();
    window.close();
}
