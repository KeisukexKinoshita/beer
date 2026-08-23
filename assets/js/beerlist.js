/* Beer一覧のクライアント側フィルタ・並び替え・検索 (データは38件と小さいのでDOM操作で完結) */
(function(){
  var grid=document.getElementById('beer-grid'); if(!grid) return;
  var cards=Array.prototype.slice.call(grid.querySelectorAll('.bcard'));
  var search=document.getElementById('f-search');
  var sortSel=document.getElementById('f-sort');
  var chips=Array.prototype.slice.call(document.querySelectorAll('.f-chip'));
  var countEl=document.getElementById('f-count');
  var activeGroup='all';

  function apply(){
    var q=(search.value||'').trim().toLowerCase();
    var shown=0;
    cards.forEach(function(card){
      var okG=activeGroup==='all'||card.dataset.group===activeGroup;
      var hay=(card.dataset.name+' '+card.dataset.maker+' '+card.dataset.style).toLowerCase();
      var okQ=!q||hay.indexOf(q)>=0;
      var vis=okG&&okQ;
      card.style.display=vis?'':'none';
      if(vis)shown++;
    });
    if(countEl)countEl.textContent=shown;
    sortNow();
  }
  function sortNow(){
    var v=sortSel.value;
    var vis=cards.filter(function(c){return c.style.display!=='none';});
    vis.sort(function(a,b){
      if(v==='rating')return parseFloat(b.dataset.rating)-parseFloat(a.dataset.rating);
      if(v==='ibu')return parseFloat(b.dataset.ibu)-parseFloat(a.dataset.ibu);
      if(v==='alcohol')return parseFloat(b.dataset.alcohol)-parseFloat(a.dataset.alcohol);
      if(v==='name')return a.dataset.name.localeCompare(b.dataset.name,'ja');
      return parseInt(b.dataset.order)-parseInt(a.dataset.order); // newest
    });
    vis.forEach(function(c){grid.appendChild(c);});
  }
  chips.forEach(function(ch){ch.addEventListener('click',function(){
    chips.forEach(function(x){x.classList.remove('on');});ch.classList.add('on');
    activeGroup=ch.dataset.group;apply();
  });});
  search.addEventListener('input',apply);
  sortSel.addEventListener('change',apply);
  apply();
})();
