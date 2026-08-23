/* 製造地マップ (Leaflet + CARTO dark タイル)。
   <div class="lmap" data-lat=".." data-lng=".." data-label=".."></div> を初期化する。
   Leaflet本体は head で読み込む(L がグローバル)。緯度経度が無い要素はスキップ(枠のまま)。*/
(function(){
  if (typeof L === 'undefined') return;
  var els = document.querySelectorAll('.lmap[data-lat]');
  Array.prototype.forEach.call(els, function(el){
    var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return;
    var map = L.map(el, {
      center:[lat,lng], zoom:5, zoomControl:true, scrollWheelZoom:false, attributionControl:true
    });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      subdomains:'abcd', maxZoom:19,
      attribution:'&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);
    // Nebulaカラーの発光マーカー
    var icon = L.divIcon({className:'neb-pin', html:'<span></span>', iconSize:[18,18], iconAnchor:[9,9]});
    var m = L.marker([lat,lng], {icon:icon}).addTo(map);
    if (el.dataset.label) m.bindPopup(el.dataset.label);
    // クリックで地図操作を有効化(誤操作防止で初期はホイール無効)
    el.addEventListener('click', function(){ map.scrollWheelZoom.enable(); });
  });
})();
