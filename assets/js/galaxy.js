/* 全ビール3D味覚グラフ (外部ライブラリ非依存)。
   軸: x=アルコール度 / y=IBU / z=フルーティーさ。原点O=各軸の平均値(±方向にプロット)。
   軸は白黒系、点はスタイル別カラー。
   initGalaxy({canvas,tooltip,legend,data,highlightIds,onPick})
     data: 全ビール配列 {id,n,a,i,f,c,r,g,st,mk,cc} (座標系は常に全体で決める)
     highlightIds: 省略=全て通常表示 / 配列=そのidを強調し他を淡色化 */
var GALAXY_COL={ipa:'#5fd0ff',stout:'#b98cff',sour:'#ff6fb0',pale:'#ffd06b',other:'#5cf0c2'};
var GALAXY_LABEL={ipa:'IPA系',stout:'Stout / 黒',sour:'Sour',pale:'Pale / Amber',other:'Lager / その他'};

function initGalaxy(opt){
  var c=document.querySelector(opt.canvas); if(!c) return;
  var x=c.getContext('2d');
  var tt=opt.tooltip?document.querySelector(opt.tooltip):null;
  var DPR=Math.min(2,devicePixelRatio||1);
  var rm=matchMedia('(prefers-reduced-motion:reduce)').matches;
  var data=opt.data||[];
  var HL=opt.highlightIds?({}):null;
  if(HL){opt.highlightIds.forEach(function(id){HL[id]=true;});}
  var single=opt.highlightIds&&opt.highlightIds.length===1;

  // 平均・スケール(最大偏差)を全データから算出 → 原点=平均、±で正規化
  function avg(f){var s=0;for(var i=0;i<data.length;i++)s+=f(data[i]);return data.length?s/data.length:0;}
  function mad(f,m){var d=0;for(var i=0;i<data.length;i++)d=Math.max(d,Math.abs(f(data[i])-m));return d||1;}
  function mn(f){var v=Infinity;for(var i=0;i<data.length;i++)v=Math.min(v,f(data[i]));return v;}
  function mx(f){var v=-Infinity;for(var i=0;i<data.length;i++)v=Math.max(v,f(data[i]));return v;}
  var fa=function(b){return b.a;},fi=function(b){return b.i;},ff=function(b){return b.f;};
  var ma=avg(fa),mi=avg(fi),mf=avg(ff),da=mad(fa,ma),di=mad(fi,mi),df=mad(ff,mf);
  function nc(v,m,d){return Math.max(-1,Math.min(1,(v-m)/d));}
  var pts=data.map(function(b){return {b:b,x:nc(b.a,ma,da),y:-nc(b.i,mi,di),z:nc(b.f,mf,df),
    hl:HL?!!HL[b.id]:true};});

  var off={}; Object.keys(GALAXY_COL).forEach(function(k){off[k]=false;});
  var W,H,ry=0.6,rx=-0.35,tRy=0.6,tRx=-0.35,drag=false,px,py,auto=true,hov=null,ord=[];
  var SCALE=0.25;
  function rs(){var r=c.getBoundingClientRect();W=c.width=r.width*DPR;H=c.height=r.height*DPR;}
  rs();addEventListener('resize',rs);
  function proj(p){
    var cy=Math.cos(ry),sy=Math.sin(ry),cx=Math.cos(rx),sx=Math.sin(rx);
    var X=p.x*cy-p.z*sy,Z=p.x*sy+p.z*cy,Y=p.y*cx-Z*sx;Z=p.y*sx+Z*cx;
    var d=3.2,sc=d/(d+Z),s=Math.min(W,H);
    return{sx:W/2+X*s*SCALE*sc,sy:H/2+Y*s*SCALE*sc,sc:sc,Z:Z};
  }
  function P(x1,y1,z1){return proj({x:x1,y:y1,z:z1});}

  // 軸(白黒系)。原点(0,0,0)=平均を中心に ± 両方向。dirは+方向。
  function fa1(v){return v.toFixed(1)+'%';}
  function fi1(v){return String(Math.round(v));}
  function ff1(v){return String(Math.round(v*10)/10);}
  var AXES=[
    {dir:[1,0,0], name:'アルコール度', min:fa1(mn(fa)), max:fa1(mx(fa))},
    {dir:[0,-1,0],name:'IBU（苦味）',  min:fi1(mn(fi)), max:fi1(mx(fi))},
    {dir:[0,0,1], name:'フルーティー', min:ff1(mn(ff)), max:ff1(mx(ff))}
  ];
  var AXC={line:'rgba(214,218,235,.42)',arrow:'rgba(236,238,248,.75)',name:'rgba(240,242,252,.92)',val:'rgba(176,182,206,.7)'};

  function drawFrame(){
    // 立方体ワイヤーフレーム(原点中心) — 淡いグレー
    var c8=[[-1,-1,-1],[1,-1,-1],[1,1,-1],[-1,1,-1],[-1,-1,1],[1,-1,1],[1,1,1],[-1,1,1]];
    var edges=[[0,1],[1,2],[2,3],[3,0],[4,5],[5,6],[6,7],[7,4],[0,4],[1,5],[2,6],[3,7]];
    var pj=c8.map(function(v){return P(v[0],v[1],v[2]);});
    x.lineWidth=DPR;x.strokeStyle='rgba(200,205,225,.10)';x.beginPath();
    for(var e=0;e<edges.length;e++){var a=pj[edges[e][0]],b=pj[edges[e][1]];x.moveTo(a.sx,a.sy);x.lineTo(b.sx,b.sy);}
    x.stroke();
    // 床グリッド(底面 y=1)
    x.strokeStyle='rgba(200,205,225,.07)';x.beginPath();var t,g1,g2;
    for(t=-1;t<=1.001;t+=2/4){g1=P(t,1,-1);g2=P(t,1,1);x.moveTo(g1.sx,g1.sy);x.lineTo(g2.sx,g2.sy);
      g1=P(-1,1,t);g2=P(1,1,t);x.moveTo(g1.sx,g1.sy);x.lineTo(g2.sx,g2.sy);}
    x.stroke();
    // 3軸(原点を貫く白黒ライン + 矢印 + 平均マーカー)
    var o=P(0,0,0);
    // 原点(平均)マーカー
    x.fillStyle='rgba(236,238,248,.5)';x.beginPath();x.arc(o.sx,o.sy,2.5*DPR,0,7);x.fill();
    for(var ai=0;ai<AXES.length;ai++){var ax=AXES[ai];
      var pe=P(ax.dir[0],ax.dir[1],ax.dir[2]),ne=P(-ax.dir[0],-ax.dir[1],-ax.dir[2]);
      x.strokeStyle=AXC.line;x.lineWidth=1.4*DPR;
      x.beginPath();x.moveTo(ne.sx,ne.sy);x.lineTo(pe.sx,pe.sy);x.stroke();
      // 矢印(+端)
      var an=Math.atan2(pe.sy-o.sy,pe.sx-o.sx),ah=7*DPR;
      x.fillStyle=AXC.arrow;x.beginPath();x.moveTo(pe.sx,pe.sy);
      x.lineTo(pe.sx-ah*Math.cos(an-0.4),pe.sy-ah*Math.sin(an-0.4));
      x.lineTo(pe.sx-ah*Math.cos(an+0.4),pe.sy-ah*Math.sin(an+0.4));x.closePath();x.fill();
      // ラベル(枠内クランプ)
      x.textAlign='center';x.textBaseline='middle';var pad=52*DPR;
      var lx=pe.sx+(pe.sx-o.sx)*0.09,ly=pe.sy+(pe.sy-o.sy)*0.09;
      lx=Math.max(pad,Math.min(W-pad,lx));ly=Math.max(52*DPR,Math.min(H-40*DPR,ly));
      x.font='700 '+(12*DPR)+'px "M PLUS 1","Noto Sans JP",sans-serif';x.fillStyle=AXC.name;x.fillText(ax.name,lx,ly);
      x.font=(9.5*DPR)+'px "M PLUS 1","Noto Sans JP",sans-serif';x.fillStyle=AXC.val;x.fillText('max '+ax.max,lx,ly+13*DPR);
      // -端に min
      var nx=ne.sx+(ne.sx-o.sx)*0.06,ny=ne.sy+(ne.sy-o.sy)*0.06;
      nx=Math.max(pad,Math.min(W-pad,nx));ny=Math.max(52*DPR,Math.min(H-40*DPR,ny));
      x.fillStyle=AXC.val;x.fillText('min '+ax.min,nx,ny);
    }
  }

  function draw(){
    x.clearRect(0,0,W,H);var s=Math.min(W,H);
    ord=pts.map(function(p,idx){return {p:p,idx:idx,pr:proj(p)};}).sort(function(a,b){return a.pr.Z-b.pr.Z;});
    var g=x.createRadialGradient(W/2,H/2,0,W/2,H/2,s*.55);
    g.addColorStop(0,'rgba(160,107,255,.11)');g.addColorStop(.5,'rgba(255,92,168,.04)');g.addColorStop(1,'rgba(0,0,0,0)');
    x.fillStyle=g;x.fillRect(0,0,W,H);
    drawFrame();
    for(var k=0;k<ord.length;k++){var o=ord[k],b=o.p.b;if(off[b.g])continue;
      var col=GALAXY_COL[b.g]||'#fff',pr=o.pr,isHl=o.p.hl;
      if(HL&&!isHl){ // 非ハイライトは淡い点のみ
        x.globalAlpha=hov===o.idx?.5:.16;x.fillStyle=col;
        x.beginPath();x.arc(pr.sx,pr.sy,2*DPR*pr.sc,0,7);x.fill();x.globalAlpha=1;
        o._sx=pr.sx;o._sy=pr.sy;o._r=6*DPR;continue;
      }
      var rad=(3.2+b.r*1.5)*pr.sc*DPR*(hov===o.idx?1.5:1);
      var gg=x.createRadialGradient(pr.sx,pr.sy,0,pr.sx,pr.sy,rad*3.4);
      gg.addColorStop(0,col);gg.addColorStop(.35,col+'bb');gg.addColorStop(1,col+'00');
      x.globalAlpha=(HL||hov!==null)?(hov===o.idx||(HL&&isHl)?1:.4):.9;
      x.fillStyle=gg;x.beginPath();x.arc(pr.sx,pr.sy,rad*3.4,0,7);x.fill();
      // 床への投影線
      x.globalAlpha=(HL&&isHl)?.55:(hov===null?.32:(hov===o.idx?.8:.12));
      var foot=P(o.p.x,1,o.p.z);x.strokeStyle=col;x.lineWidth=DPR;
      x.beginPath();x.moveTo(pr.sx,pr.sy);x.lineTo(foot.sx,foot.sy);x.stroke();
      x.globalAlpha=1;x.fillStyle='#fff';x.beginPath();x.arc(pr.sx,pr.sy,rad*.5,0,7);x.fill();
      // 単一ハイライト時はリング+名前
      if(single&&isHl){x.strokeStyle='#fff';x.lineWidth=1.5*DPR;x.globalAlpha=.9;
        x.beginPath();x.arc(pr.sx,pr.sy,rad*1.8,0,7);x.stroke();x.globalAlpha=1;
        x.font='700 '+(12*DPR)+'px "M PLUS 1","Noto Sans JP",sans-serif';x.fillStyle='#fff';
        x.textAlign='center';x.fillText(b.n,pr.sx,pr.sy-rad*2.4);}
      o._sx=pr.sx;o._sy=pr.sy;o._r=rad*3.4;
    }
  }
  function tick(){if(auto&&!drag&&!rm)tRy+=0.0016;ry+=(tRy-ry)*.08;rx+=(tRx-rx)*.08;draw();requestAnimationFrame(tick);}
  tick();
  function pick(mx2,my2){var best=null,bd=1e9;for(var k=0;k<ord.length;k++){var o=ord[k];if(off[o.p.b.g])continue;
    var dx=mx2-o._sx,dy=my2-o._sy,d=dx*dx+dy*dy;if(d<Math.max(o._r*o._r,(16*DPR)*(16*DPR))&&d<bd){bd=d;best=o;}}return best;}
  c.addEventListener('mousedown',function(e){drag=true;px=e.clientX;py=e.clientY;auto=false;});
  addEventListener('mouseup',function(){drag=false;});
  addEventListener('mousemove',function(e){if(drag){tRy+=(e.clientX-px)*.008;tRx+=(e.clientY-py)*.008;px=e.clientX;py=e.clientY;tRx=Math.max(-1.2,Math.min(1.2,tRx));}});
  c.addEventListener('mousemove',function(e){
    var r=c.getBoundingClientRect(),mx2=(e.clientX-r.left)*DPR,my2=(e.clientY-r.top)*DPR,o=pick(mx2,my2);
    if(o){hov=o.idx;var b=o.p.b;if(tt){tt.innerHTML='<b>'+b.n+'</b> <div class="st">'+(GALAXY_LABEL[b.g]||'')+'</div>'+
      '<div class="row"><span>ALC</span><span>'+b.a.toFixed(1)+'%</span></div><div class="row"><span>IBU</span><span>'+Math.round(b.i)+'</span></div>'+
      '<div class="row"><span>FRUITY</span><span>'+b.f+'/4</span></div><div class="row"><span>★</span><span>'+b.r.toFixed(1)+'</span></div>';
      tt.style.left=(o._sx/DPR)+'px';tt.style.top=(o._sy/DPR)+'px';tt.style.opacity=1;}c.style.cursor='pointer';}
    else{hov=null;if(tt)tt.style.opacity=0;c.style.cursor=drag?'grabbing':'grab';}
  });
  c.addEventListener('mouseleave',function(){hov=null;if(tt)tt.style.opacity=0;});
  c.addEventListener('click',function(e){var r=c.getBoundingClientRect(),mx2=(e.clientX-r.left)*DPR,my2=(e.clientY-r.top)*DPR,o=pick(mx2,my2);
    if(o&&opt.onPick)opt.onPick(o.p.b);});
  c.addEventListener('touchstart',function(e){drag=true;auto=false;px=e.touches[0].clientX;py=e.touches[0].clientY;},{passive:true});
  c.addEventListener('touchmove',function(e){if(drag){tRy+=(e.touches[0].clientX-px)*.008;tRx+=(e.touches[0].clientY-py)*.008;px=e.touches[0].clientX;py=e.touches[0].clientY;tRx=Math.max(-1.2,Math.min(1.2,tRx));}},{passive:true});
  c.addEventListener('touchend',function(){drag=false;});
  if(opt.legend){var lg=document.querySelector(opt.legend);if(lg){Object.keys(GALAXY_COL).forEach(function(k){
    var sp=document.createElement('span');sp.innerHTML='<i style="background:'+GALAXY_COL[k]+';color:'+GALAXY_COL[k]+'"></i>'+GALAXY_LABEL[k];
    sp.onclick=function(){off[k]=!off[k];sp.classList.toggle('off',off[k]);};lg.appendChild(sp);});}}
}
