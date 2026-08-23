/* Nebula 背景の星空 (#stars canvas)。全ページ共通・軽量 */
(function(){
  var c=document.getElementById('stars'); if(!c) return;
  var x=c.getContext('2d'),w,h,st=[];
  var rm=matchMedia('(prefers-reduced-motion:reduce)').matches;
  function rs(){
    w=c.width=innerWidth;h=c.height=innerHeight;st=[];
    var n=Math.min(220,w*h/9000);
    for(var i=0;i<n;i++)st.push({x:Math.random()*w,y:Math.random()*h,z:Math.random(),ph:Math.random()*6.28});
  }
  rs();addEventListener('resize',rs);
  var t=0,cols=['#cfe6ff','#ffb3d9','#c9b0ff'];
  function lp(){
    t+=.02;x.clearRect(0,0,w,h);
    for(var i=0;i<st.length;i++){var s=st[i];var tw=rm?.7:.5+.5*Math.sin(t+s.ph);
      x.globalAlpha=(.2+s.z*.6)*tw;x.fillStyle=cols[(s.ph*3|0)%3];
      x.fillRect(s.x,s.y,s.z*1.8+.3,s.z*1.8+.3);}
    x.globalAlpha=1;if(!rm)requestAnimationFrame(lp);
  }
  lp();
})();
