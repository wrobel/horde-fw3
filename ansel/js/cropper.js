var CropDraggable=Class.create();Object.extend(Object.extend(CropDraggable.prototype,Draggable.prototype),{initialize:function(A){this.options=Object.extend({drawMethod:function(){}},arguments[1]||{});this.element=$(A);this.handle=this.element;this.delta=this.currentDelta();this.dragging=false;this.eventMouseDown=this.initDrag.bindAsEventListener(this);Event.observe(this.handle,"mousedown",this.eventMouseDown);Draggables.register(this)},draw:function(B){var A=Position.cumulativeOffset(this.element);var D=this.currentDelta();A[0]-=D[0];A[1]-=D[1];var C=[0,1].map(function(E){return(B[E]-A[E]-this.offset[E])}.bind(this));this.options.drawMethod(C)}});var Cropper={};Cropper.Img=Class.create();Cropper.Img.prototype={initialize:function(C,A){this.options=Object.extend({ratioDim:{x:0,y:0},minWidth:0,minHeight:0,displayOnInit:false,onEndCrop:Prototype.emptyFunction,captureKeys:true,onloadCoords:null,maxWidth:0,maxHeight:0},A||{});this.img=$(C);this.clickCoords={x:0,y:0};this.dragging=false;this.resizing=false;this.isWebKit=/Konqueror|Safari|KHTML/.test(navigator.userAgent);this.isIE=/MSIE/.test(navigator.userAgent);this.isOpera8=/Opera\s[1-8]/.test(navigator.userAgent);this.ratioX=0;this.ratioY=0;this.attached=false;this.fixedWidth=(this.options.maxWidth>0&&(this.options.minWidth>=this.options.maxWidth));this.fixedHeight=(this.options.maxHeight>0&&(this.options.minHeight>=this.options.maxHeight));if(typeof this.img=="undefined"){return }if(this.options.ratioDim.x>0&&this.options.ratioDim.y>0){var B=this.getGCD(this.options.ratioDim.x,this.options.ratioDim.y);this.ratioX=this.options.ratioDim.x/B;this.ratioY=this.options.ratioDim.y/B}this.subInitialize();if(this.img.complete||this.isWebKit){this.onLoad()}else{Event.observe(this.img,"load",this.onLoad.bindAsEventListener(this))}},getGCD:function(B,A){if(A==0){return B}return this.getGCD(A,B%A)},onLoad:function(){var D="imgCrop_";var C=this.img.parentNode;var B="";if(this.isOpera8){B=" opera8"}this.imgWrap=Builder.node("div",{"class":D+"wrap"+B});this.north=Builder.node("div",{"class":D+"overlay "+D+"north"},[Builder.node("span")]);this.east=Builder.node("div",{"class":D+"overlay "+D+"east"},[Builder.node("span")]);this.south=Builder.node("div",{"class":D+"overlay "+D+"south"},[Builder.node("span")]);this.west=Builder.node("div",{"class":D+"overlay "+D+"west"},[Builder.node("span")]);var A=[this.north,this.east,this.south,this.west];this.dragArea=Builder.node("div",{"class":D+"dragArea"},A);this.handleN=Builder.node("div",{"class":D+"handle "+D+"handleN"});this.handleNE=Builder.node("div",{"class":D+"handle "+D+"handleNE"});this.handleE=Builder.node("div",{"class":D+"handle "+D+"handleE"});this.handleSE=Builder.node("div",{"class":D+"handle "+D+"handleSE"});this.handleS=Builder.node("div",{"class":D+"handle "+D+"handleS"});this.handleSW=Builder.node("div",{"class":D+"handle "+D+"handleSW"});this.handleW=Builder.node("div",{"class":D+"handle "+D+"handleW"});this.handleNW=Builder.node("div",{"class":D+"handle "+D+"handleNW"});this.selArea=Builder.node("div",{"class":D+"selArea"},[Builder.node("div",{"class":D+"marqueeHoriz "+D+"marqueeNorth"},[Builder.node("span")]),Builder.node("div",{"class":D+"marqueeVert "+D+"marqueeEast"},[Builder.node("span")]),Builder.node("div",{"class":D+"marqueeHoriz "+D+"marqueeSouth"},[Builder.node("span")]),Builder.node("div",{"class":D+"marqueeVert "+D+"marqueeWest"},[Builder.node("span")]),this.handleN,this.handleNE,this.handleE,this.handleSE,this.handleS,this.handleSW,this.handleW,this.handleNW,Builder.node("div",{"class":D+"clickArea"})]);this.imgWrap.appendChild(this.img);this.imgWrap.appendChild(this.dragArea);this.dragArea.appendChild(this.selArea);this.dragArea.appendChild(Builder.node("div",{"class":D+"clickArea"}));C.appendChild(this.imgWrap);this.startDragBind=this.startDrag.bindAsEventListener(this);Event.observe(this.dragArea,"mousedown",this.startDragBind);this.onDragBind=this.onDrag.bindAsEventListener(this);Event.observe(document,"mousemove",this.onDragBind);this.endCropBind=this.endCrop.bindAsEventListener(this);Event.observe(document,"mouseup",this.endCropBind);this.resizeBind=this.startResize.bindAsEventListener(this);this.handles=[this.handleN,this.handleNE,this.handleE,this.handleSE,this.handleS,this.handleSW,this.handleW,this.handleNW];this.registerHandles(true);if(this.options.captureKeys){this.keysBind=this.handleKeys.bindAsEventListener(this);Event.observe(document,"keypress",this.keysBind)}new CropDraggable(this.selArea,{drawMethod:this.moveArea.bindAsEventListener(this)});this.setParams()},registerHandles:function(G){for(var E=0;E<this.handles.length;E++){var F=$(this.handles[E]);if(G){var D=false;if(this.fixedWidth&&this.fixedHeight){D=true}else{if(this.fixedWidth||this.fixedHeight){var C=F.className.match(/([S|N][E|W])$/);var B=F.className.match(/(E|W)$/);var A=F.className.match(/(N|S)$/);if(C){D=true}else{if(this.fixedWidth&&B){D=true}else{if(this.fixedHeight&&A){D=true}}}}}if(D){F.hide()}else{Event.observe(F,"mousedown",this.resizeBind)}}else{F.show();Event.stopObserving(F,"mousedown",this.resizeBind)}}},setParams:function(){this.imgW=this.img.width;this.imgH=this.img.height;$(this.north).setStyle({height:0});$(this.east).setStyle({width:0,height:0});$(this.south).setStyle({height:0});$(this.west).setStyle({width:0,height:0});$(this.imgWrap).setStyle({width:this.imgW+"px",height:this.imgH+"px"});$(this.selArea).hide();var B={x1:0,y1:0,x2:0,y2:0};var A=false;if(this.options.onloadCoords!=null){B=this.cloneCoords(this.options.onloadCoords);A=true}else{if(this.options.ratioDim.x>0&&this.options.ratioDim.y>0){B.x1=Math.ceil((this.imgW-this.options.ratioDim.x)/2);B.y1=Math.ceil((this.imgH-this.options.ratioDim.y)/2);B.x2=B.x1+this.options.ratioDim.x;B.y2=B.y1+this.options.ratioDim.y;A=true}}this.setAreaCoords(B,false,false,1);if(this.options.displayOnInit&&A){this.selArea.show();this.drawArea();this.endCrop()}this.attached=true},remove:function(){if(this.attached){this.attached=false;this.imgWrap.parentNode.insertBefore(this.img,this.imgWrap);this.imgWrap.parentNode.removeChild(this.imgWrap);Event.stopObserving(this.dragArea,"mousedown",this.startDragBind);Event.stopObserving(document,"mousemove",this.onDragBind);Event.stopObserving(document,"mouseup",this.endCropBind);this.registerHandles(false);if(this.options.captureKeys){Event.stopObserving(document,"keypress",this.keysBind)}}},reset:function(){if(!this.attached){this.onLoad()}else{this.setParams()}this.endCrop()},handleKeys:function(B){var A={x:0,y:0};if(!this.dragging){switch(B.keyCode){case (37):A.x=-1;break;case (38):A.y=-1;break;case (39):A.x=1;break;case (40):A.y=1;break}if(A.x!=0||A.y!=0){if(B.shiftKey){A.x*=10;A.y*=10}this.moveArea([this.areaCoords.x1+A.x,this.areaCoords.y1+A.y]);Event.stop(B)}}},calcW:function(){return(this.areaCoords.x2-this.areaCoords.x1)},calcH:function(){return(this.areaCoords.y2-this.areaCoords.y1)},moveArea:function(A){this.setAreaCoords({x1:A[0],y1:A[1],x2:A[0]+this.calcW(),y2:A[1]+this.calcH()},true,false);this.drawArea()},cloneCoords:function(A){return{x1:A.x1,y1:A.y1,x2:A.x2,y2:A.y2}},setAreaCoords:function(E,D,C,B,M){if(D){var K=E.x2-E.x1;var I=E.y2-E.y1;if(E.x1<0){E.x1=0;E.x2=K}if(E.y1<0){E.y1=0;E.y2=I}if(E.x2>this.imgW){E.x2=this.imgW;E.x1=this.imgW-K}if(E.y2>this.imgH){E.y2=this.imgH;E.y1=this.imgH-I}}else{if(E.x1<0){E.x1=0}if(E.y1<0){E.y1=0}if(E.x2>this.imgW){E.x2=this.imgW}if(E.y2>this.imgH){E.y2=this.imgH}if(B!=null){if(this.ratioX>0){this.applyRatio(E,{x:this.ratioX,y:this.ratioY},B,M)}else{if(C){this.applyRatio(E,{x:1,y:1},B,M)}}var H=[this.options.minWidth,this.options.minHeight];var G=[this.options.maxWidth,this.options.maxHeight];if(H[0]>0||H[1]>0||G[0]>0||G[1]>0){var F={a1:E.x1,a2:E.x2};var A={a1:E.y1,a2:E.y2};var L={min:0,max:this.imgW};var J={min:0,max:this.imgH};if((H[0]!=0||H[1]!=0)&&C){if(H[0]>0){H[1]=H[0]}else{if(H[1]>0){H[0]=H[1]}}}if((G[0]!=0||G[0]!=0)&&C){if(G[0]>0&&G[0]<=G[1]){G[1]=G[0]}else{if(G[1]>0&&G[1]<=G[0]){G[0]=G[1]}}}if(H[0]>0){this.applyDimRestriction(F,H[0],B.x,L,"min")}if(H[1]>1){this.applyDimRestriction(A,H[1],B.y,J,"min")}if(G[0]>0){this.applyDimRestriction(F,G[0],B.x,L,"max")}if(G[1]>1){this.applyDimRestriction(A,G[1],B.y,J,"max")}E={x1:F.a1,y1:A.a1,x2:F.a2,y2:A.a2}}}}this.areaCoords=E},applyDimRestriction:function(E,F,C,D,B){var A;if(B=="min"){A=((E.a2-E.a1)<F)}else{A=((E.a2-E.a1)>F)}if(A){if(C==1){E.a2=E.a1+F}else{E.a1=E.a2-F}if(E.a1<D.min){E.a1=D.min;E.a2=F}else{if(E.a2>D.max){E.a1=D.max-F;E.a2=D.max}}}},applyRatio:function(A,E,D,C){var B;if(C=="N"||C=="S"){B=this.applyRatioToAxis({a1:A.y1,b1:A.x1,a2:A.y2,b2:A.x2},{a:E.y,b:E.x},{a:D.y,b:D.x},{min:0,max:this.imgW});A.x1=B.b1;A.y1=B.a1;A.x2=B.b2;A.y2=B.a2}else{B=this.applyRatioToAxis({a1:A.x1,b1:A.y1,a2:A.x2,b2:A.y2},{a:E.x,b:E.y},{a:D.x,b:D.y},{min:0,max:this.imgH});A.x1=B.a1;A.y1=B.b1;A.x2=B.a2;A.y2=B.b2}},applyRatioToAxis:function(D,B,J,I){var H=Object.extend(D,{});var G=H.a2-H.a1;var F=Math.floor(G*B.b/B.a);var E;var C;var A=null;if(J.b==1){E=H.b1+F;if(E>I.max){E=I.max;A=E-H.b1}H.b2=E}else{E=H.b2-F;if(E<I.min){E=I.min;A=E+H.b2}H.b1=E}if(A!=null){C=Math.floor(A*B.a/B.b);if(J.a==1){H.a2=H.a1+C}else{H.a1=H.a1=H.a2-C}}return H},drawArea:function(){var I=this.calcW();var H=this.calcH();var J="px";var E=[this.areaCoords.x1+J,this.areaCoords.y1+J,I+J,H+J,this.areaCoords.x2+J,this.areaCoords.y2+J,(this.img.width-this.areaCoords.x2)+J,(this.img.height-this.areaCoords.y2)+J];var D=this.selArea.style;D.left=E[0];D.top=E[1];D.width=E[2];D.height=E[3];var C=Math.ceil((I-6)/2)+J;var B=Math.ceil((H-6)/2)+J;this.handleN.style.left=C;this.handleE.style.top=B;this.handleS.style.left=C;this.handleW.style.top=B;this.north.style.height=E[1];var A=this.east.style;A.top=E[1];A.height=E[3];A.left=E[4];A.width=E[6];var G=this.south.style;G.top=E[5];G.height=E[7];var F=this.west.style;F.top=E[1];F.height=E[3];F.width=E[0];this.subDrawArea();this.forceReRender()},forceReRender:function(){if(this.isIE||this.isWebKit){var F=document.createTextNode(" ");var D,B,E,A;if(this.isIE){fixEl=this.selArea}else{if(this.isWebKit){fixEl=document.getElementsByClassName("imgCrop_marqueeSouth",this.imgWrap)[0];D=Builder.node("div","");D.style.visibility="hidden";var C=["SE","S","SW"];for(A=0;A<C.length;A++){B=document.getElementsByClassName("imgCrop_handle"+C[A],this.selArea)[0];if(B.childNodes.length){B.removeChild(B.childNodes[0])}B.appendChild(D)}}}fixEl.appendChild(F);fixEl.removeChild(F)}},startResize:function(A){this.startCoords=this.cloneCoords(this.areaCoords);this.resizing=true;this.resizeHandle=Event.element(A).classNames().toString().replace(/([^N|NE|E|SE|S|SW|W|NW])+/,"");Event.stop(A)},startDrag:function(A){this.selArea.show();this.clickCoords=this.getCurPos(A);this.setAreaCoords({x1:this.clickCoords.x,y1:this.clickCoords.y,x2:this.clickCoords.x,y2:this.clickCoords.y},false,false,null);this.dragging=true;this.onDrag(A);Event.stop(A)},getCurPos:function(B){var A=this.imgWrap,C=Position.cumulativeOffset(A);while(A.nodeName!="BODY"){C[1]-=A.scrollTop||0;C[0]-=A.scrollLeft||0;A=A.parentNode}return curPos={x:Event.pointerX(B)-C[0],y:Event.pointerY(B)-C[1]}},onDrag:function(E){if(this.dragging||this.resizing){var D=null;var C=this.getCurPos(E);var B=this.cloneCoords(this.areaCoords);var A={x:1,y:1};if(this.dragging){if(C.x<this.clickCoords.x){A.x=-1}if(C.y<this.clickCoords.y){A.y=-1}this.transformCoords(C.x,this.clickCoords.x,B,"x");this.transformCoords(C.y,this.clickCoords.y,B,"y")}else{if(this.resizing){D=this.resizeHandle;if(D.match(/E/)){this.transformCoords(C.x,this.startCoords.x1,B,"x");if(C.x<this.startCoords.x1){A.x=-1}}else{if(D.match(/W/)){this.transformCoords(C.x,this.startCoords.x2,B,"x");if(C.x<this.startCoords.x2){A.x=-1}}}if(D.match(/N/)){this.transformCoords(C.y,this.startCoords.y2,B,"y");if(C.y<this.startCoords.y2){A.y=-1}}else{if(D.match(/S/)){this.transformCoords(C.y,this.startCoords.y1,B,"y");if(C.y<this.startCoords.y1){A.y=-1}}}}}this.setAreaCoords(B,false,E.shiftKey,A,D);this.drawArea();Event.stop(E)}},transformCoords:function(E,D,C,B){var A=[E,D];if(E>D){A.reverse()}C[B+"1"]=A[0];C[B+"2"]=A[1]},endCrop:function(){this.dragging=false;this.resizing=false;this.options.onEndCrop(this.areaCoords,{width:this.calcW(),height:this.calcH()})},subInitialize:function(){},subDrawArea:function(){}};Cropper.ImgWithPreview=Class.create();Object.extend(Object.extend(Cropper.ImgWithPreview.prototype,Cropper.Img.prototype),{subInitialize:function(){this.hasPreviewImg=false;if(typeof (this.options.previewWrap)!="undefined"&&this.options.minWidth>0&&this.options.minHeight>0){this.previewWrap=$(this.options.previewWrap);this.previewImg=this.img.cloneNode(false);this.previewImg.id="imgCrop_"+this.previewImg.id;this.options.displayOnInit=true;this.hasPreviewImg=true;this.previewWrap.addClassName("imgCrop_previewWrap");this.previewWrap.setStyle({width:this.options.minWidth+"px",height:this.options.minHeight+"px"});this.previewWrap.appendChild(this.previewImg)}},subDrawArea:function(){if(this.hasPreviewImg){var F=this.calcW();var E=this.calcH();var C={x:this.imgW/F,y:this.imgH/E};var D={x:F/this.options.minWidth,y:E/this.options.minHeight};var B={w:Math.ceil(this.options.minWidth*C.x)+"px",h:Math.ceil(this.options.minHeight*C.y)+"px",x:"-"+Math.ceil(this.areaCoords.x1/D.x)+"px",y:"-"+Math.ceil(this.areaCoords.y1/D.y)+"px"};var A=this.previewImg.style;A.width=B.w;A.height=B.h;A.left=B.x;A.top=B.y}}});