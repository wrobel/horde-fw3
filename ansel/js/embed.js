Event.observe(window,"load",function(){var B=anselnodes.length;var E=new Array();for(var G=0;G<B;G++){var C=anselnodes[G];if(typeof anseljson[C]["lightbox"]!="undefined"){E=E.concat(anseljson[C]["lightbox"])}var A=$(C);if(anseljson[C]["perpage"]){var F=anseljson[C]["perpage"]}else{var F=anseljson[C]["data"].size()}for(var D=0;D<F;D++){(function(){var K=C;var J=D;var I=A.appendChild(new Element("span",{className:"anselGalleryWidget"}));if(!anseljson[K]["hideLinks"]){if(anseljson[K]["linkToGallery"]){var H=6}else{var H=5}var M=I.appendChild(new Element("a",{href:anseljson[K]["data"][J][H],title:anseljson[K]["data"][J][2]}));var L={image:anseljson[K]["data"][J][3]};M.appendChild(new Element("img",{src:anseljson[K]["data"][J][0]}));if(typeof anseljson[C]["lightbox"]!="undefined"){M.observe("click",function(N){ansel_lb.start(L.image);N.stop()})}}else{I.appendChild(new Element("img",{src:anseljson[K]["data"][J][0]}));if(typeof anseljson[C]["lightbox"]!="undefined"){M.observe("click",function(N){ansel_lb.start(L.image);N.stop()})}}})()}if(anseljson[C]["perpage"]>0){(function(){var L=C;var H=new Element("a",{href:"#",title:"Next Image",className:"anselNext",style:"text-decoration:none;width:40%;float:right;"});H.update(">>");var K={node:L,page:1};H.observe("click",function(M){displayPage(M,K)});var I=new Element("a",{href:"#",title:"Previous Image",className:"anselPrev",style:"text-decoration:none;width:40%;float:right;"});I.update("<<");var J={node:L,page:-1};I.observe("click",function(M){displayPage(M,J)});$(L).appendChild(H);$(L).appendChild(I);Horde_ToolTips.attachBehavior(L);Event.observe(window,"unload",Horde_ToolTips.out.bind(Horde_ToolTips))})()}else{(function(){var H=C;Horde_ToolTips.attachBehavior(H)})()}}if(E.length){lbOptions.gallery_json=E;ansel_lb=new Lightbox(lbOptions)}Event.observe(window,"unload",Horde_ToolTips.out.bind(Horde_ToolTips))});function displayPage(B,N){var F=N.node;var M=N.page;var K=anseljson[F]["perpage"];var H=anseljson[F]["data"].size();var E=Math.ceil(H/K)-1;var O=anseljson[F]["page"];M=O+M;if(M>E){M=0}if(M<0){M=E}var D=$(F);D.update();var C=M*K;var G=Math.min(H-1,C+K-1);for(var J=C;J<=G;J++){var A=D.appendChild(new Element("span",{className:"anselGalleryWidget"}));var I=A.appendChild(new Element("a",{href:anseljson[F]["data"][J][5],alt:anseljson[F]["data"][J][2],title:anseljson[F]["data"][J][2]}));I.appendChild(new Element("img",{src:anseljson[F]["data"][J][0]}))}var P=new Element("a",{href:"",title:"Next Image",style:"text-decoration:none;width:40%;float:right;"});P.update(">>");var N={node:F,page:++O};P.observe("click",function(Q){displayPage(Q,N)}.bind());var L=new Element("a",{href:"",title:"Previous Image",style:"text-decoration:none;width:40%;float:right;"});L.update("<<");var N={node:F,page:--O};L.observe("click",function(Q){displayPage(Q,N)}.bind());D.appendChild(P);D.appendChild(L);Horde_ToolTips.attachBehavior(F);anseljson[F]["page"]=M;B.stop()};