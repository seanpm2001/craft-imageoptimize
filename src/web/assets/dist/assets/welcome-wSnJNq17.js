var b=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function x(c){return c&&c.__esModule&&Object.prototype.hasOwnProperty.call(c,"default")?c.default:c}var w={exports:{}};(function(c,f){(function(u,o){c.exports=o()})(b,function(){return function(u){function o(n){if(l[n])return l[n].exports;var r=l[n]={i:n,l:!1,exports:{}};return u[n].call(r.exports,r,r.exports,o),r.l=!0,r.exports}var l={};return o.m=u,o.c=l,o.d=function(n,r,d){o.o(n,r)||Object.defineProperty(n,r,{configurable:!1,enumerable:!0,get:d})},o.n=function(n){var r=n&&n.__esModule?function(){return n.default}:function(){return n};return o.d(r,"a",r),r},o.o=function(n,r){return Object.prototype.hasOwnProperty.call(n,r)},o.p="",o(o.s=0)}([function(u,o,l){Object.defineProperty(o,"__esModule",{value:!0});var n=l(1);l.d(o,"Confetti",function(){return n.a}),o.default={install:function(r,d){this.installed||(this.installed=!0,r.prototype.$confetti=new n.a(d))}}},function(u,o,l){function n(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var r=l(2),d=function(){function t(e,a){for(var i=0;i<a.length;i++){var h=a[i];h.enumerable=h.enumerable||!1,h.configurable=!0,"value"in h&&(h.writable=!0),Object.defineProperty(e,h.key,h)}}return function(e,a,i){return a&&t(e.prototype,a),i&&t(e,i),e}}(),s=function(){function t(){n(this,t),this.initialize(),this.onResizeCallback=this.updateDimensions.bind(this)}return d(t,[{key:"initialize",value:function(){this.canvas=null,this.ctx=null,this.W=0,this.H=0,this.particles={},this.droppedCount=0,this.particlesPerFrame=1.5,this.wind=0,this.windSpeed=1,this.windSpeedMax=1,this.windChange=.01,this.windPosCoef=.002,this.maxParticlesPerFrame=2,this.animationId=null}},{key:"createParticles",value:function(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};this.particles=new r.a({ctx:this.ctx,W:this.W,H:this.H,wind:this.wind,windPosCoef:this.windPosCoef,windSpeedMax:this.windSpeedMax,count:0,shape:e.shape||"circle",colors:{opts:e.colors||["DodgerBlue","OliveDrab","Gold","pink","SlateBlue","lightblue","Violet","PaleGreen","SteelBlue","SandyBrown","Chocolate","Crimson"],idx:0,step:10,get color(){return this.opts[(this.idx++/this.step|0)%this.opts.length]}}})}},{key:"createContext",value:function(){this.canvas=document.createElement("canvas"),this.ctx=this.canvas.getContext("2d"),this.canvas.style.display="block",this.canvas.style.position="fixed",this.canvas.style.pointerEvents="none",this.canvas.style.top=0,this.canvas.style.width="100vw",this.canvas.style.height="100vh",this.canvas.id="confetti-canvas",document.querySelector("body").appendChild(this.canvas)}},{key:"start",value:function(e){this.ctx||this.createContext(),this.animationId&&cancelAnimationFrame(this.animationId),this.createParticles(e),this.updateDimensions(),this.particlesPerFrame=this.maxParticlesPerFrame,this.animationId=requestAnimationFrame(this.mainLoop.bind(this)),window.addEventListener("resize",this.onResizeCallback)}},{key:"stop",value:function(){this.particlesPerFrame=0,window.removeEventListener("resize",this.onResizeCallback)}},{key:"remove",value:function(){this.stop(),this.animationId&&cancelAnimationFrame(this.animationId),this.canvas&&document.body.removeChild(this.canvas),this.initialize()}},{key:"updateDimensions",value:function(){this.W===window.innerWidth&&this.H===window.innerHeight||(this.W=this.particles.opts.W=this.canvas.width=window.innerWidth,this.H=this.particles.opts.H=this.canvas.height=window.innerHeight)}},{key:"mainLoop",value:function(e){for(this.updateDimensions(),this.ctx.setTransform(1,0,0,1,0,0),this.ctx.clearRect(0,0,this.W,this.H),this.windSpeed=Math.sin(e/8e3)*this.windSpeedMax,this.wind=this.particles.opts.wind+=this.windChange;this.droppedCount<this.particlesPerFrame;)this.droppedCount+=1,this.particles.add();this.droppedCount-=this.particlesPerFrame,this.particles.update(),this.particles.draw(),this.particles.items.length&&(this.animationId=requestAnimationFrame(this.mainLoop.bind(this)))}}]),t}();o.a=s},function(u,o,l){function n(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var r=l(3),d=function(){function t(e,a){for(var i=0;i<a.length;i++){var h=a[i];h.enumerable=h.enumerable||!1,h.configurable=!0,"value"in h&&(h.writable=!0),Object.defineProperty(e,h.key,h)}}return function(e,a,i){return a&&t(e.prototype,a),i&&t(e,i),e}}(),s=function(){function t(e){n(this,t),this.items=[],this.pool=[],this.opts=e}return d(t,[{key:"update",value:function(){for(var e=0;e<this.items.length;e++)this.items[e].update()===!0&&this.pool.push(this.items.splice(e--,1)[0])}},{key:"draw",value:function(){for(var e=0;e<this.items.length;e++)this.items[e].draw()}},{key:"add",value:function(){this.pool.length>0?this.items.push(this.pool.pop().setup(this.opts)):this.items.push(new r.a().setup(this.opts))}}]),t}();o.a=s},function(u,o,l){function n(s,t){if(!(s instanceof t))throw new TypeError("Cannot call a class as a function")}var r=function(){function s(t,e){for(var a=0;a<e.length;a++){var i=e[a];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(t,e,a){return e&&s(t.prototype,e),a&&s(t,a),t}}(),d=function(){function s(){n(this,s)}return r(s,[{key:"setup",value:function(t){var e=t.ctx,a=t.W,i=t.H,h=t.colors,p=t.wind,v=t.windPosCoef,m=t.windSpeedMax,g=t.count,C=t.shape;return this.ctx=e,this.W=a,this.H=i,this.wind=p,this.shape=C,this.windPosCoef=v,this.windSpeedMax=m,this.x=this.rand(-35,a+35),this.y=this.rand(-30,-35),this.d=this.rand(150)+10,this.r=this.rand(10,30),this.color=h.color,this.tilt=this.randI(10),this.tiltAngleIncremental=(this.rand(.08)+.04)*(this.rand()<.5?-1:1),this.tiltAngle=0,this.angle=this.rand(2*Math.PI),this.count=g++,this}},{key:"randI",value:function(t){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:t+(t=0);return Math.random()*(e-t)+t|0}},{key:"rand",value:function(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:1,e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:t+(t=0);return Math.random()*(e-t)+t}},{key:"update",value:function(){return this.tiltAngle+=this.tiltAngleIncremental*(.2*Math.cos(this.wind+(this.d+this.x+this.y)*this.windPosCoef)+1),this.y+=(Math.cos(this.angle+this.d)+3+this.r/2)/2,this.x+=Math.sin(this.angle),this.x+=Math.cos(this.wind+(this.d+this.x+this.y)*this.windPosCoef)*this.windSpeedMax,this.y+=Math.sin(this.wind+(this.d+this.x+this.y)*this.windPosCoef)*this.windSpeedMax,this.tilt=15*Math.sin(this.tiltAngle-this.count/3),this.y>this.H}},{key:"drawCircle",value:function(){this.ctx.arc(0,0,this.r/2,0,2*Math.PI,!1),this.ctx.fill()}},{key:"drawRect",value:function(){this.ctx.fillRect(0,0,this.r,this.r/2)}},{key:"drawHeart",value:function(){var t=this,e=function(a,i,h,p,v,m){t.ctx.bezierCurveTo(a/t.r*2,i/t.r*2,h/t.r*2,p/t.r*2,v/t.r*2,m/t.r*2)};this.ctx.moveTo(37.5/this.r,20/this.r),e(75,37,70,25,50,25),e(20,25,20,62.5,20,62.5),e(20,80,40,102,75,120),e(110,102,130,80,130,62.5),e(130,62.5,130,25,100,25),e(85,25,75,37,75,40),this.ctx.fill()}},{key:"draw",value:function(){this.ctx.fillStyle=this.color,this.ctx.beginPath(),this.ctx.setTransform(Math.cos(this.tiltAngle),Math.sin(this.tiltAngle),0,1,this.x,this.y),this.shape==="circle"?this.drawCircle():this.shape==="rect"?this.drawRect():this.shape==="heart"&&this.drawHeart()}}]),s}();o.a=d}])})})(w);var _=w.exports;const P=x(_);function k(c,f,u,o,l,n,r,d){var s=typeof c=="function"?c.options:c;f&&(s.render=f,s.staticRenderFns=u,s._compiled=!0),o&&(s.functional=!0),n&&(s._scopeId="data-v-"+n);var t;if(r?(t=function(i){i=i||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,!i&&typeof __VUE_SSR_CONTEXT__<"u"&&(i=__VUE_SSR_CONTEXT__),l&&l.call(this,i),i&&i._registeredComponents&&i._registeredComponents.add(r)},s._ssrRegister=t):l&&(t=d?function(){l.call(this,(s.functional?this.parent:this).$root.$options.shadowRoot)}:l),t)if(s.functional){s._injectStyles=t;var e=s.render;s.render=function(h,p){return t.call(p),e(h,p)}}else{var a=s.beforeCreate;s.beforeCreate=a?[].concat(a,t):[t]}return{exports:c,options:s}}const y=window.Vue;y.use(P);const M=y.extend({mounted:function(){this.$confetti.start({shape:"rect",colors:["DodgerBlue","OliveDrab","Gold","pink","SlateBlue","lightblue","Violet","PaleGreen","SteelBlue","SandyBrown","Chocolate","Crimson"]}),setTimeout(()=>{this.$confetti.stop()},5e3)},methods:{}});var S=function(){var f=this,u=f._self._c;return f._self._setupProxy,u("main")},F=[],H=k(M,S,F,!1,null,null,null,null);const T=H.exports,I=window.Vue;new I({el:"#cp-nav-content",components:{ConfettiParty:T},data:{},methods:{}});
//# sourceMappingURL=welcome-wSnJNq17.js.map
