function returnID(){var A=parent.opener.document[formid].selectlist_selectid,B=parent.opener.document[formid].actionID;if(parent.opener.closed||!A||!B){alert(GollemText.opener_window);window.close();return}A.value=cacheid;B.value="selectlist_process";parent.opener.document[formid].submit();window.close()};