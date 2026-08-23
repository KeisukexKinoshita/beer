/* 味プロファイルのレーダーチャート (外部ライブラリ非依存)
   drawRadar('#radar', [v1..v5], labels?)  v は 0..1 に正規化した値 */
function drawRadar(sel, vals, labels){
  var c=document.querySelector(sel); if(!c) return;
  var x=c.getContext('2d'),DPR=Math.min(2,devicePixelRatio||1);
  var r=c.getBoundingClientRect();c.width=r.width*DPR;c.height=r.height*DPR;
  labels=labels||["IBU","アルコール","フルーティー","色","評価"];
  var W=c.width,H=c.height,cx=W/2,cy=H/2,R=Math.min(W,H)*.34,N=vals.length;
  x.clearRect(0,0,W,H);
  var i,g,an,rr,px,py;
  for(g=1;g<=4;g++){x.beginPath();for(i=0;i<=N;i++){an=-Math.PI/2+i/N*6.283;rr=R*g/4;
    px=cx+Math.cos(an)*rr;py=cy+Math.sin(an)*rr;i?x.lineTo(px,py):x.moveTo(px,py);}
    x.closePath();x.strokeStyle='rgba(170,150,240,.16)';x.stroke();}
  for(i=0;i<N;i++){an=-Math.PI/2+i/N*6.283;x.beginPath();x.moveTo(cx,cy);
    x.lineTo(cx+Math.cos(an)*R,cy+Math.sin(an)*R);x.strokeStyle='rgba(170,150,240,.12)';x.stroke();
    x.fillStyle='#ac9fd6';x.font=(11*DPR)+'px "Noto Sans JP"';x.textAlign='center';
    x.fillText(labels[i],cx+Math.cos(an)*(R+16*DPR),cy+Math.sin(an)*(R+16*DPR)+4);}
  var grd=x.createLinearGradient(cx-R,cy-R,cx+R,cy+R);
  grd.addColorStop(0,'rgba(255,92,168,.28)');grd.addColorStop(1,'rgba(75,224,200,.28)');
  x.beginPath();vals.forEach(function(v,i){an=-Math.PI/2+i/N*6.283;rr=R*Math.max(0,Math.min(1,v));
    px=cx+Math.cos(an)*rr;py=cy+Math.sin(an)*rr;i?x.lineTo(px,py):x.moveTo(px,py);});
  x.closePath();x.fillStyle=grd;x.fill();x.strokeStyle='#a06bff';x.lineWidth=2*DPR;x.stroke();
  vals.forEach(function(v,i){an=-Math.PI/2+i/N*6.283;rr=R*Math.max(0,Math.min(1,v));
    x.beginPath();x.arc(cx+Math.cos(an)*rr,cy+Math.sin(an)*rr,3*DPR,0,7);x.fillStyle='#fff';x.fill();});
}
