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
  var SCALE=0.30; // 立方体フレームとラベルが収まるよう少し引く
  function rs(){var r=c.getBoundingClientRect();W=c.width=r.width*DPR;H=c.height=r.height*DPR;}
  rs();addEventListener('resize',rs);
  function proj(p){
    var cy=Math.cos(ry),sy=Math.sin(ry),cx=Math.cos(rx),sx=Math.sin(rx);
    var X=p.x*cy-p.z*sy,Z=p.x*sy+p.z*cy,Y=p.y*cx-Z*sx;Z=p.y*sx+Z*cx;
    var d=3.2,sc=d/(d+Z),s=Math.min(W,H);
    return{sx:W/2+X*s*SCALE*sc,sy:H/2+Y*s*SCALE*sc,sc:sc,Z:Z};
  }
  function P(x1,y1,z1){return proj({x:x1,y:y1,z:z1});}
  // 軸定義: 原点コーナー(-1,1,-1)=全て最小 から各軸方向へ。ラベルは各軸のブランドカラー。
  var O=[-1,1,-1];
  var AXES=[
    {to:[ 1,1,-1],col:'#ffcf6b',name:'アルコール度',max:'11%',min:'0.7%'},
    {to:[-1,-1,-1],col:'#ff5ca8',name:'IBU（苦味）',max:'75',min:'11'},
    {to:[-1,1, 1],col:'#4be0c8',name:'フルーティー',max:'4',min:'1'}
  ];
  function drawFrame(){
    // 立方体ワイヤーフレーム (12辺) — 淡く
    var c8=[[-1,-1,-1],[1,-1,-1],[1,1,-1],[-1,1,-1],[-1,-1,1],[1,-1,1],[1,1,1],[-1,1,1]];
    var edges=[[0,1],[1,2],[2,3],[3,0],[4,5],[5,6],[6,7],[7,4],[0,4],[1,5],[2,6],[3,7]];
    var pj=c8.map(function(v){return P(v[0],v[1],v[2]);});
    x.lineWidth=DPR;x.strokeStyle='rgba(170,150,240,.14)';
    x.beginPath();
    for(var e=0;e<edges.length;e++){var a=pj[edges[e][0]],b=pj[edges[e][1]];x.moveTo(a.sx,a.sy);x.lineTo(b.sx,b.sy);}
    x.stroke();
    // 床グリッド (底面 y=1) — さらに淡く
    x.strokeStyle='rgba(170,150,240,.09)';x.beginPath();
    var t,g1,g2;
    for(t=-1;t<=1.001;t+=2/4){g1=P(t,1,-1);g2=P(t,1,1);x.moveTo(g1.sx,g1.sy);x.lineTo(g2.sx,g2.sy);
      g1=P(-1,1,t);g2=P(1,1,t);x.moveTo(g1.sx,g1.sy);x.lineTo(g2.sx,g2.sy);}
    x.stroke();
    // 3軸 (色つき・矢印・ラベル・目盛)
    var o=P(O[0],O[1],O[2]);
    for(var ai=0;ai<AXES.length;ai++){var ax=AXES[ai],en=P(ax.to[0],ax.to[1],ax.to[2]);
      x.strokeStyle=ax.col;x.globalAlpha=.85;x.lineWidth=1.6*DPR;
      x.beginPath();x.moveTo(o.sx,o.sy);x.lineTo(en.sx,en.sy);x.stroke();
      // 矢印
      var an=Math.atan2(en.sy-o.sy,en.sx-o.sx),ah=7*DPR;
      x.fillStyle=ax.col;x.beginPath();x.moveTo(en.sx,en.sy);
      x.lineTo(en.sx-ah*Math.cos(an-0.4),en.sy-ah*Math.sin(an-0.4));
      x.lineTo(en.sx-ah*Math.cos(an+0.4),en.sy-ah*Math.sin(an+0.4));x.closePath();x.fill();
      // ラベル(軸名 + レンジ)。矢印の先に配置
      x.globalAlpha=1;x.textAlign='center';x.textBaseline='middle';
      var lx=en.sx+(en.sx-o.sx)*0.09,ly=en.sy+(en.sy-o.sy)*0.09;
      x.font='700 '+(12.5*DPR)+'px "M PLUS 1","Noto Sans JP",sans-serif';
      x.fillStyle=ax.col;x.fillText(ax.name,lx,ly);
      x.font=(10*DPR)+'px "M PLUS 1","Noto Sans JP",sans-serif';x.fillStyle='rgba(200,190,235,.65)';
      x.fillText(ax.min+' → '+ax.max,lx,ly+14*DPR);
    }
    x.globalAlpha=1;
  }
  function draw(){
    x.clearRect(0,0,W,H);var s=Math.min(W,H);
    ord=pts.map(function(p,idx){return {p:p,idx:idx,pr:proj(p)};}).sort(function(a,b){return a.pr.Z-b.pr.Z;});
    var g=x.createRadialGradient(W/2,H/2,0,W/2,H/2,s*.55);
    g.addColorStop(0,'rgba(160,107,255,.13)');g.addColorStop(.5,'rgba(255,92,168,.05)');g.addColorStop(1,'rgba(0,0,0,0)');
    x.fillStyle=g;x.fillRect(0,0,W,H);
    drawFrame();
    for(var k=0;k<ord.length;k++){var o=ord[k],b=o.p.b;if(off[b.g])continue;
      var col=GALAXY_COL[b.g]||'#fff',pr=o.pr;
      var rad=(3.2+b.r*1.5)*pr.sc*DPR*(hov===o.idx?1.5:1);
      var gg=x.createRadialGradient(pr.sx,pr.sy,0,pr.sx,pr.sy,rad*3.4);
      gg.addColorStop(0,col);gg.addColorStop(.35,col+'bb');gg.addColorStop(1,col+'00');
      x.globalAlpha=hov===null?.95:(hov===o.idx?1:.4);
      x.fillStyle=gg;x.beginPath();x.arc(pr.sx,pr.sy,rad*3.4,0,7);x.fill();
      // 芯 + 床への投影線(グラフ感): 点から底面(y=1)へ細い線
      x.globalAlpha=hov===null?.38:(hov===o.idx?.85:.15);
      var foot=P(o.p.x,1,o.p.z);
      x.strokeStyle=col;x.lineWidth=DPR;x.beginPath();x.moveTo(pr.sx,pr.sy);x.lineTo(foot.sx,foot.sy);x.stroke();
      x.globalAlpha=1;x.fillStyle='#fff';x.beginPath();x.arc(pr.sx,pr.sy,rad*.5,0,7);x.fill();
      o._sx=pr.sx;o._sy=pr.sy;o._r=rad*3.4;
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
