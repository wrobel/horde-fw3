function showFace(a){$("facediv"+a).addClassName("shown");$("facethumb"+a).style.border="1px solid red";$("facedivname"+a).style.display="inline"}function hideFace(a){$("facediv"+a).removeClassName("shown");$("facethumb"+a).style.border="1px solid black";$("facedivname"+a).style.display="none"}document.observe("dom:loaded",function(){Event.observe($("photodiv"),"load",function(){$("faces-on-image").immediateDescendants().collect(function(a){a.clonePosition($("photodiv"),{setWidth:false,setHeight:false})})})});