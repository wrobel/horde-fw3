function addTag(){if(!$("addtag").value.blank()){var a=new Object();a.requestType="TagActions/action=add/gallery="+tagActions.gallery+"/tags="+$("addtag").value;if(tagActions.image){a.requestType+="/image="+tagActions.image}new Ajax.Updater({success:"tags"},tagActions.url,{method:"post",parameters:a,onComplete:function(){$("addtag").value=""}})}return true}function removeTag(a){var b=new Object();b.requestType="TagActions/action=remove/gallery="+tagActions.gallery+"/tags="+a;if(tagActions.image){b.requestType+="/image="+tagActions.image}new Ajax.Updater({success:"tags"},tagActions.url,{method:"post",parameters:b});return true}function submitcheck(){return!addTag()};