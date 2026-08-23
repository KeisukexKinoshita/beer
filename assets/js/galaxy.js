/* 全ビール3D味覚銀河 (外部ライブラリ非依存)。
   使い方: initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS, onPick:fn})
   data要素: {id,n,a,i,f,c,r,g,st,mk,cc}  g=スタイルグループ(ipa/stout/sour/pale/other)
   軸: x=アルコール度 / y=IBU / z=フルーティーさ */
var GALAXY_COL={ipa:'#5fd0ff',stout:'#b98cff',sour:'#ff6fb0',pale:'#ffd06b',other:'#5cf0c2'};
var GALAXY_LABEL={ipa:'IPA系',stout:'Stout / 黒',sour:'Sour',pale:'Pale / Amber',other:'Lager / その他'};

function initGalaxy(opt){
  var c=document.querySelector(opt.canvas); if(!c) return;
  var x=c.getContext('2d');
  var tt=opt.tooltip?document.querySelector(opt.tooltip):null;
  var DPR=Math.min(2,devicePixelRatio||1);
  var rm=matchMedia('(prefers-reduced-motion:reduce)').matches;
  var A=[0.7,11],I=[11,74.6],F=[1,4];
  function nm(v,r){return (v-r[0])/(r[1]-r[0])*2-1;}
  var data=opt.data||[];
  var pts=data.map(function(b){return {b:b,x:nm(b.a,A),y:-nm(b.i,I),z:nm(b.f,F)};});
  var off={}; Object.keys(GALAXY_COL).forEach(function(k){off[k]=false;});
  var W,H,ry=0.6,rx=-0.35,tRy=0.6,tRx=-0.35,drag=false,px,py,auto=true,hov=null,ord=[];
  function rs(){var r=c.getBoundingClientRect();W=c.width=r.width*DPR;H=c.height=r.height*DPR;}
  rs();addEventListener('resize',rs);
  function proj(p){
    var cy=Math.cos(ry),sy=Math.sin(ry),cx=Math.cos(rx),sx=Math.sin(rx);
    var X=p.x*cy-p.z*sy,Z=p.x*sy+p.z*cy,Y=p.y*cx-Z*sx;Z=p.y*sx+Z*cx;
    var d=3.2,sc=d/(d+Z),s=Math.min(W,H);
    return{sx:W/2+X*s*.34*sc,sy:H/2+Y*s*.34*sc,sc:sc,Z:Z};
  }
  function draw(){
    x.clearRect(0,0,W,H);var s=Math.min(W,H);
    ord=pts.map(function(p,idx){return {p:p,idx:idx,pr:proj(p)};}).sort(function(a,b){return a.pr.Z-b.pr.Z;});
    var g=x.createRadialGradient(W/2,H/2,0,W/2,H/2,s*.55);
    g.addColorStop(0,'rgba(160,107,255,.16)');g.addColorStop(.5,'rgba(255,92,168,.06)');g.addColorStop(1,'rgba(0,0,0,0)');
    x.fillStyle=g;x.fillRect(0,0,W,H);
    for(var k=0;k<ord.length;k++){var o=ord[k],b=o.p.b;if(off[b.g])continue;
      var col=GALAXY_COL[b.g]||'#fff',pr=o.pr;
      var rad=(3.4+b.r*1.7)*pr.sc*DPR*(hov===o.idx?1.5:1);
      var gg=x.createRadialGradient(pr.sx,pr.sy,0,pr.sx,pr.sy,rad*3.6);
      gg.addColorStop(0,col);gg.addColorStop(.35,col+'bb');gg.addColorStop(1,col+'00');
      x.globalAlpha=hov===null?.92:(hov===o.idx?1:.45);
      x.fillStyle=gg;x.beginPath();x.arc(pr.sx,pr.sy,rad*3.6,0,7);x.fill();
      x.globalAlpha=1;x.fillStyle='#fff';x.beginPath();x.arc(pr.sx,pr.sy,rad*.5,0,7);x.fill();
      o._sx=pr.sx;o._sy=pr.sy;o._r=rad*3.6;
    }
  }
  function tick(){if(auto&&!drag&&!rm)tRy+=0.0016;ry+=(tRy-ry)*.08;rx+=(tRx-rx)*.08;draw();requestAnimationFrame(tick);}
  tick();
  function pick(mx,my){var best=null,bd=1e9;for(var k=0;k<ord.length;k++){var o=ord[k];if(off[o.p.b.g])continue;
    var dx=mx-o._sx,dy=my-o._sy,d=dx*dx+dy*dy;if(d<Math.max(o._r*o._r,(16*DPR)*(16*DPR))&&d<bd){bd=d;best=o;}}return best;}
  c.addEventListener('mousedown',function(e){drag=true;px=e.clientX;py=e.clientY;auto=false;});
  addEventListener('mouseup',function(){drag=false;});
  addEventListener('mousemove',function(e){if(drag){tRy+=(e.clientX-px)*.008;tRx+=(e.clientY-py)*.008;px=e.clientX;py=e.clientY;tRx=Math.max(-1.2,Math.min(1.2,tRx));}});
  c.addEventListener('mousemove',function(e){
    var r=c.getBoundingClientRect(),mx=(e.clientX-r.left)*DPR,my=(e.clientY-r.top)*DPR,o=pick(mx,my);
    if(o){hov=o.idx;var b=o.p.b;if(tt){tt.innerHTML='<b>'+b.n+'</b> <div class="st">'+(GALAXY_LABEL[b.g]||'')+'</div>'+
      '<div class="row"><span>ALC</span><span>'+b.a.toFixed(1)+'%</span></div><div class="row"><span>IBU</span><span>'+Math.round(b.i)+'</span></div>'+
      '<div class="row"><span>FRUITY</span><span>'+b.f+'/4</span></div><div class="row"><span>★</span><span>'+b.r.toFixed(1)+'</span></div>';
      tt.style.left=(o._sx/DPR)+'px';tt.style.top=(o._sy/DPR)+'px';tt.style.opacity=1;}c.style.cursor='pointer';}
    else{hov=null;if(tt)tt.style.opacity=0;c.style.cursor=drag?'grabbing':'grab';}
  });
  c.addEventListener('mouseleave',function(){hov=null;if(tt)tt.style.opacity=0;});
  c.addEventListener('click',function(e){
    var r=c.getBoundingClientRect(),mx=(e.clientX-r.left)*DPR,my=(e.clientY-r.top)*DPR,o=pick(mx,my);
    if(o&&opt.onPick)opt.onPick(o.p.b);
  });
  c.addEventListener('touchstart',function(e){drag=true;auto=false;px=e.touches[0].clientX;py=e.touches[0].clientY;},{passive:true});
  c.addEventListener('touchmove',function(e){if(drag){tRy+=(e.touches[0].clientX-px)*.008;tRx+=(e.touches[0].clientY-py)*.008;px=e.touches[0].clientX;py=e.touches[0].clientY;tRx=Math.max(-1.2,Math.min(1.2,tRx));}},{passive:true});
  c.addEventListener('touchend',function(){drag=false;});
  if(opt.legend){var lg=document.querySelector(opt.legend);if(lg){Object.keys(GALAXY_COL).forEach(function(k){
    var sp=document.createElement('span');sp.innerHTML='<i style="background:'+GALAXY_COL[k]+';color:'+GALAXY_COL[k]+'"></i>'+GALAXY_LABEL[k];
    sp.onclick=function(){off[k]=!off[k];sp.classList.toggle('off',off[k]);};lg.appendChild(sp);});}}
}
