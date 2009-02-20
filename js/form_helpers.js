function addEvent(B,D,C){if(!B){return false}if(typeof C=="string"){C=new Function(C)}if(B.addEventListener){B.addEventListener(D.replace(/on/,""),C,false)}else{if(B.attachEvent){B.attachEvent(D,C)}else{if(B.onload!=null){var A=B[D];newCode=Function(e);A(e);C();B[D]=newCode}else{B[D]=C}}}return true}function toNumber(A){if(isNaN(A)){return 0}else{return Number(A)}}function checkEnabled(){if(arguments.length>2){objSrc=arguments[0];objTarget=objSrc.form.elements[arguments[1]];enabled=arguments[2];toggle=false;var A,B;if(objTarget){switch(objSrc.type.toLowerCase()){case"select-one":B=objSrc.options[objSrc.selectedIndex].value;break;case"select-multiple":B=[];count=0;for(A=0;A<objSrc.length;++A){if(objSrc.options[A].selected){B[count]=objSrc.options[A].value}}break;case"checkbox":if(objSrc.checked){B=objSrc.value;toggle=true}break;default:B=objSrc.value}for(A=3;A<arguments.length;++A){if(typeof (B)=="object"&&(arguments[A] in B)){toggle=true;break}else{if(arguments[A]==B){toggle=true;break}}}objTarget.disabled=toggle?!enabled:enabled;if(!objTarget.disabled){objTarget.focus()}}}}function sumFields(){if(arguments.length>2){objFrm=arguments[0];objTarget=objFrm.elements[arguments[1]];var C=0;if(objTarget){for(var B=2;B<arguments.length;++B){objSrc=objFrm.elements[arguments[B]];if(objSrc){switch(objSrc.type.toLowerCase()){case"select-one":C+=toNumber(objSrc.options[objSrc.selectedIndex].value);break;case"select-multiple":for(var A=0;A<objSrc.length;++A){C+=toNumber(objSrc.options[A].value)}break;case"checkbox":if(objSrc.checked){C+=toNumber(objSrc.value)}break;default:C+=toNumber(objSrc.value)}}}objTarget.value=C}}}function form_setCursorPosition(D,C){var B=document.getElementById(D);if(!B){return }if(B.setSelectionRange){B.focus();B.setSelectionRange(C,C)}else{if(B.createTextRange){var A=B.createTextRange();A.collapse(true);A.moveStart("character",C);A.moveEnd("character",0);A.select();A.scrollIntoView(true)}}};