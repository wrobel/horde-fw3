var AccessKeys={macos:navigator.appVersion.indexOf("Mac")!=-1,keydownHandler:function(E){var B,D,A,C;if((this.macos&&E.ctrlKey)||(E.altKey&&!E.ctrlKey)){C=String.fromCharCode(E.keyCode||E.charCode);D=$$('[accesskey="'+C.toUpperCase()+'"]').concat($$('[accesskey="'+C.toLowerCase()+'"]'));if(B=D.first()){if(D.size()>1){D.slice(1).invoke("writeAttribute","accesskey",null)}E.stop();if(B.tagName=="INPUT"){}else{if(B.match("A")&&B.onclick){B.onclick()}else{if(document.createEvent){A=document.createEvent("MouseEvents");A.initMouseEvent("click",true,true,window,0,0,0,0,0,false,false,false,false,0,null);B.dispatchEvent(A)}else{B.fireEvent("onclick")}}}}}}};document.observe("keydown",AccessKeys.keydownHandler.bindAsEventListener(AccessKeys));