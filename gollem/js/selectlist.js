function returnID(){var a=parent.opener.document[formid].selectlist_selectid,b=parent.opener.document[formid].actionID;if(parent.opener.closed||!a||!b){alert(GollemText.opener_window);window.close();return}a.value=cacheid;b.value="selectlist_process";parent.opener.document[formid].submit();window.close()};