if(typeof Prototype=="undefined"||!Prototype.Version.match("1.6")){throw("Prototype-UI library require Prototype library >= 1.6.0")}if(Prototype.Browser.WebKit){Prototype.Browser.WebKitVersion=parseFloat(navigator.userAgent.match(/AppleWebKit\/([\d\.\+]*)/)[1]);Prototype.Browser.Safari2=(Prototype.Browser.WebKitVersion<420)}if(Prototype.Browser.IE){Prototype.Browser.IEVersion=parseFloat(navigator.appVersion.split(";")[1].strip().split(" ")[1]);Prototype.Browser.IE6=Prototype.Browser.IEVersion==6;Prototype.Browser.IE7=Prototype.Browser.IEVersion==7}Prototype.falseFunction=function(){return false};Prototype.trueFunction=function(){return true};var UI={Abstract:{},Ajax:{}};Object.extend(Class.Methods,{extend:Object.extend.methodize(),addMethods:Class.Methods.addMethods.wrap(function(A,B){if(!B){return this}if(!B.hasOwnProperty("methodsAdded")){return A(B)}var C=B.methodsAdded;delete B.methodsAdded;A(B);C.call(B,this);B.methodsAdded=C;return this}),addMethod:function(C,B){var A={};A[C]=B;return this.addMethods(A)},method:function(A){return this.prototype[A].valueOf()},classMethod:function(){$A(arguments).flatten().each(function(A){this[A]=(function(){return this[A].apply(this,arguments)}).bind(this.prototype)},this);return this},undefMethod:function(A){this.prototype[A]=undefined;return this},removeMethod:function(A){delete this.prototype[A];return this},aliasMethod:function(A,B){this.prototype[A]=this.prototype[B];return this},aliasMethodChain:function(B,A){A=A.camelcase();this.aliasMethod(B+"Without"+A,B);this.aliasMethod(B,B+"With"+A);return this}});Object.extend(Number.prototype,{snap:function(A){return parseInt(A==1?this:(this/A).floor()*A)}});Object.extend(String.prototype,{camelcase:function(){var A=this.dasherize().camelize();return A.charAt(0).toUpperCase()+A.slice(1)},makeElement:function(){var A=new Element("div");A.innerHTML=this;return A.down()}});Object.extend(Array.prototype,{empty:function(){return!this.length},extractOptions:function(){return this.last().constructor===Object?this.pop():{}},removeAt:function(B){var A=this[B];this.splice(B,1);return A},remove:function(B){var A;while((A=this.indexOf(B))!=-1){this.removeAt(A)}return B},insert:function(B){var A=$A(arguments);A.shift();this.splice.apply(this,[B,0].concat(A));return this}});Element.addMethods({getScrollDimensions:function(A){return{width:A.scrollWidth,height:A.scrollHeight}},getScrollOffset:function(A){return Element._returnOffset(A.scrollLeft,A.scrollTop)},setScrollOffset:function(A,B){A=$(A);if(arguments.length==3){B={left:B,top:arguments[2]}}A.scrollLeft=B.left;A.scrollTop=B.top;return A},getNumStyle:function(A,B){var C=parseFloat($(A).getStyle(B));return isNaN(C)?null:C},appendText:function(A,B){A=$(A);B=String.interpret(B);A.appendChild(document.createTextNode(B));return A}});document.whenReady=function(A){if(document.loaded){A.call(document)}else{document.observe("dom:loaded",A)}};Object.extend(document.viewport,{getScrollOffset:document.viewport.getScrollOffsets,setScrollOffset:function(A){Element.setScrollOffset(Prototype.Browser.WebKit?document.body:document.documentElement,A)},getScrollDimensions:function(){return Element.getScrollDimensions(Prototype.Browser.WebKit?document.body:document.documentElement)}});(function(){UI.Options={methodsAdded:function(B){B.classMethod($w(" setOptions allOptions optionsGetter optionsSetter optionsAccessor "))},setOptions:function(B){if(!this.hasOwnProperty("options")){this.options=this.allOptions()}this.options=Object.extend(this.options,B||{})},allOptions:function(){var C=this.constructor.superclass,B=C&&C.prototype;return(B&&B.allOptions)?Object.extend(B.allOptions(),this.options):Object.clone(this.options)},optionsGetter:function(){A(this,arguments,false)},optionsSetter:function(){A(this,arguments,true)},optionsAccessor:function(){this.optionsGetter.apply(this,arguments);this.optionsSetter.apply(this,arguments)}};function A(C,D,B){D=$A(D).flatten();if(D.empty()){D=Object.keys(C.allOptions())}D.each(function(F){var E=(B?"set":"get")+F.camelcase();C[E]=C[E]||(B?function(G){return this.options[F]=G}:function(){return this.options[F]})})}})();UI.Carousel=Class.create(UI.Options,{options:{direction:"horizontal",previousButton:".previous_button",nextButton:".next_button",container:".container",scrollInc:"auto",disabledButtonSuffix:"_disabled",overButtonSuffix:"_over"},initialize:function(B,A){this.setOptions(A);this.element=$(B);this.id=this.element.id;this.container=this.element.down(this.options.container).firstDescendant();this.elements=this.container.childElements();this.previousButton=this.options.previousButton==false?null:this.element.down(this.options.previousButton);this.nextButton=this.options.nextButton==false?null:this.element.down(this.options.nextButton);this.posAttribute=(this.options.direction=="horizontal"?"left":"top");this.dimAttribute=(this.options.direction=="horizontal"?"width":"height");this.elementSize=this.computeElementSize();this.nbVisible=this.currentSize()/this.elementSize;var C=this.options.scrollInc;if(C=="auto"){C=Math.floor(this.nbVisible)}[this.previousButton,this.nextButton].each(function(D){if(!D){return}var E=(D==this.nextButton?"next_button":"previous_button")+this.options.overButtonSuffix;D.clickHandler=this.scroll.bind(this,(D==this.nextButton?-1:1)*C*this.elementSize);D.observe("click",D.clickHandler).observe("mouseover",function(){D.addClassName(E)}.bind(this)).observe("mouseout",function(){D.removeClassName(E)}.bind(this))},this);this.updateButtons()},destroy:function($super){[this.previousButton,this.nextButton].each(function(A){if(!A){return}A.stopObserving("click",A.clickHandler)},this);this.element.remove();this.fire("destroyed")},fire:function(B,A){A=A||{};A.carousel=this;return this.element.fire("carousel:"+B,A)},observe:function(A,B){this.element.observe("carousel:"+A,B.bind(this));return this},stopObserving:function(A,B){this.element.stopObserving("carousel:"+A,B);return this},checkScroll:function(A,D){if(A>0){A=0}else{var B=this.elements.last().positionedOffset()[this.posAttribute]+this.elementSize;var C=this.currentSize();if(A+B<C){A+=C-(A+B)}A=Math.min(A,0)}if(D){this.container.style[this.posAttribute]=A+"px"}return A},scroll:function(B){if(this.animating){return this}var A=this.currentPosition()+B;A=this.checkScroll(A,false);B=A-this.currentPosition();if(B!=0){this.animating=true;this.fire("scroll:started");var C=this;this.container.morph("opacity:0.5",{duration:0.2,afterFinish:function(){C.container.morph(C.posAttribute+": "+A+"px",{duration:0.4,delay:0.2,afterFinish:function(){C.container.morph("opacity:1",{duration:0.2,afterFinish:function(){C.animating=false;C.updateButtons().fire("scroll:ended",{shift:B/C.currentSize()})}})}})}})}return this},scrollTo:function(A){if(this.animating||A<0||A>this.elements.length||A==this.currentIndex()||isNaN(parseInt(A))){return this}return this.scroll((this.currentIndex()-A)*this.elementSize)},updateButtons:function(){this.updatePreviousButton();this.updateNextButton();return this},updatePreviousButton:function(){var A=this.currentPosition();var B="previous_button"+this.options.disabledButtonSuffix;if(this.previousButton.hasClassName(B)&&A!=0){this.previousButton.removeClassName(B);this.fire("previousButton:enabled")}if(!this.previousButton.hasClassName(B)&&A==0){this.previousButton.addClassName(B);this.fire("previousButton:disabled")}},updateNextButton:function(){var A=this.currentLastPosition();var B=this.currentSize();var C="next_button"+this.options.disabledButtonSuffix;if(this.nextButton.hasClassName(C)&&A!=B){this.nextButton.removeClassName(C);this.fire("nextButton:enabled")}if(!this.nextButton.hasClassName(C)&&A==B){this.nextButton.addClassName(C);this.fire("nextButton:disabled")}},computeElementSize:function(){return this.elements.first().getDimensions()[this.dimAttribute]},currentIndex:function(){return-this.currentPosition()/this.elementSize},currentLastPosition:function(){if(this.container.childElements().empty()){return 0}return this.currentPosition()+this.elements.last().positionedOffset()[this.posAttribute]+this.elementSize},currentPosition:function(){return this.container.getNumStyle(this.posAttribute)},currentSize:function(){return this.container.parentNode.getDimensions()[this.dimAttribute]},updateSize:function(){this.nbVisible=this.currentSize()/this.elementSize;var A=this.options.scrollInc;if(A=="auto"){A=Math.floor(this.nbVisible)}[this.previousButton,this.nextButton].each(function(B){if(!B){return}B.stopObserving("click",B.clickHandler);B.clickHandler=this.scroll.bind(this,(B==this.nextButton?-1:1)*A*this.elementSize);B.observe("click",B.clickHandler)},this);this.checkScroll(this.currentPosition(),true);this.updateButtons().fire("sizeUpdated");return this}});UI.Ajax.Carousel=Class.create(UI.Carousel,{options:{elementSize:-1,url:null},initialize:function($super,B,A){if(!A.url){throw("url option is required for UI.Ajax.Carousel")}if(!A.elementSize){throw("elementSize option is required for UI.Ajax.Carousel")}$super(B,A);this.endIndex=0;this.hasMore=true;this.updateHandler=this.update.bind(this);this.updateAndScrollHandler=function(E,D,C){this.update(D,C);this.scroll(E)}.bind(this);this.runRequest.bind(this).defer({parameters:{from:0,to:Math.floor(this.nbVisible)},onSuccess:this.updateHandler})},runRequest:function(A){this.requestRunning=true;new Ajax.Request(this.options.url,Object.extend({method:"GET"},A));this.fire("request:started");return this},scroll:function($super,A){if(this.animating||this.requestRunning){return this}var D=(-A)/this.elementSize;if(this.hasMore&&D>0&&this.currentIndex()+this.nbVisible+D-1>this.endIndex){var C=this.endIndex+1;var B=Math.floor(C+this.nbVisible-1);this.runRequest({parameters:{from:C,to:B},onSuccess:this.updateAndScrollHandler.curry(A).bind(this)});return this}else{$super(A)}},update:function(B,A){this.requestRunning=false;this.fire("request:ended");if(!A){A=B.responseJSON}this.hasMore=A.more;this.endIndex=Math.max(this.endIndex,A.to);this.elements=this.container.insert({bottom:A.html}).childElements();return this.updateButtons()},computeElementSize:function(){return this.options.elementSize},updateSize:function($super){var A=this.nbVisible;$super();if(Math.floor(this.nbVisible)-Math.floor(A)>=1&&this.hasMore){if(this.currentIndex()+Math.floor(this.nbVisible)>=this.endIndex){var B=Math.floor(this.currentIndex()+Math.floor(this.nbVisible)-this.endIndex);this.runRequest({parameters:{from:this.endIndex+1,to:this.endIndex+B},onSuccess:this.updateHandler})}}return this},updateNextButton:function($super){var A=this.currentLastPosition();var B=this.currentSize();var C="next_button"+this.options.disabledButtonSuffix;if(this.nextButton.hasClassName(C)&&A!=B){this.nextButton.removeClassName(C);this.fire("nextButton:enabled")}if(!this.nextButton.hasClassName(C)&&A==B&&!this.hasMore){this.nextButton.addClassName(C);this.fire("nextButton:disabled")}}});