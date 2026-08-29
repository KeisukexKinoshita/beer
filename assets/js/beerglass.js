/* ビールのプレースホルダー描画 (仕様9-1: 商品写真を使わず、データから生成する)
   円柱グラスに注いだ一杯を描き、液の中にだけ微かな宇宙(星と淡い星雲)を忍ばせる。
   画像ファイルは作らない。canvas に毎回描くので容量ゼロ・データ追従・権利処理不要。

   使い方: <canvas class="beerglass" data-bid="pr0001" data-c="7" data-cl="4"
                   data-f="4" data-i="15" data-a="7.5" data-g="sour"
                   role="img" aria-label="…"></canvas>
   値が欠けている場合は中央値で描く(欠損でも絵が壊れない)。 */
(function () {
  /* 色は PHP の group_map() が単一の出所(window.STYLE_GROUPS 経由)。
     JS 単体で開かれた場合に落ちないよう other だけ既定値を持つ。 */
  var GC = (function(){
    var o = {other:'#5cf0c2'}, G = (typeof window!=='undefined' && window.STYLE_GROUPS) || {};
    Object.keys(G).forEach(function(k){ o[k]=G[k].c; });
    return o;
  })();

  /* ビールの色(1-10) → 実際のビール色に近いRGB */
  function beerColor(c) {
    var stops = [[250,222,130],[247,201,86],[240,170,50],[226,133,32],[201,98,26],
                 [168,72,22],[130,52,20],[92,38,18],[56,26,16],[26,14,12]];
    return stops[Math.max(0, Math.min(9, Math.round(c) - 1))];
  }
  function rgba(a, al) { return 'rgba(' + Math.round(a[0]) + ',' + Math.round(a[1]) + ',' + Math.round(a[2]) + ',' + al + ')'; }
  function lighten(a, k) { return [a[0]+(255-a[0])*k, a[1]+(255-a[1])*k, a[2]+(255-a[2])*k]; }
  function darken(a, k) { return [a[0]*k, a[1]*k, a[2]*k]; }
  function hexToRgb(h) { return [parseInt(h.slice(1,3),16), parseInt(h.slice(3,5),16), parseInt(h.slice(5,7),16)]; }
  /* 銘柄IDから決定論的な擬似乱数 — 同じ銘柄は毎回同じ絵になる */
  function rnd(seed) {
    var s = 0;
    for (var i = 0; i < seed.length; i++) s = (s * 31 + seed.charCodeAt(i)) >>> 0;
    return function () { s = (s * 1664525 + 1013904223) >>> 0; return s / 4294967296; };
  }
  function clamp01(v) { return Math.max(0, Math.min(1, v)); }
  /* 正規化の分母は既存銘柄の実測レンジに合わせた目安であって、ビールの取りうる
     範囲ではない。IBU 90 のインペリアルIPAや IBU 8 のヴァイツェンは実在するので、
     0〜1 の外に出た値をそのまま描画係数に使わないよう必ず丸める。 */
  function norm(b) {
    return { alc:  clamp01((b.a - 0.7) / 10.3),
             ibu:  clamp01((b.i - 11) / 64),
             haze: clamp01((b.cl - 1) / 3),
             fr:   clamp01((b.f - 1) / 3) };
  }

  /* ---- 円柱グラス(タンブラー型)の形状 ---- */
  function glassGeom(W, H) {
    var s = Math.min(W, H);
    var gh = s * 0.60, topR = s * 0.150, botR = s * 0.131;
    var cx = W / 2, top = H / 2 - gh * 0.50;
    return { cx:cx, top:top, gh:gh, topR:topR, botR:botR, bot: top + gh,
             liqTop: top + gh * 0.125, liqBot: top + gh * 0.945, midY: top + gh * 0.54 };
  }
  function rAt(g, y) {
    var t = Math.max(0, Math.min(1, (y - g.top) / g.gh));
    return g.topR + (g.botR - g.topR) * t;
  }
  function glassPath(x, g) {
    var r = g.botR * 0.30;
    x.beginPath();
    x.moveTo(g.cx - g.topR, g.top);
    x.lineTo(g.cx - g.botR, g.bot - r);
    x.quadraticCurveTo(g.cx - g.botR, g.bot, g.cx - g.botR + r, g.bot);
    x.lineTo(g.cx + g.botR - r, g.bot);
    x.quadraticCurveTo(g.cx + g.botR, g.bot, g.cx + g.botR, g.bot - r);
    x.lineTo(g.cx + g.topR, g.top);
    x.closePath();
  }
  function liquidClip(x, g) {
    glassPath(x, g); x.clip();
    x.beginPath(); x.rect(g.cx - g.topR * 1.3, g.liqTop, g.topR * 2.6, g.liqBot - g.liqTop); x.clip();
  }

  function pourLiquid(x, g, col) {
    x.save(); liquidClip(x, g);
    var lg = x.createLinearGradient(0, g.liqTop, 0, g.liqBot);
    lg.addColorStop(0, rgba(lighten(col, 0.16), 0.97));
    lg.addColorStop(0.55, rgba(col, 1));
    lg.addColorStop(1, rgba(darken(col, 0.55), 1));
    x.fillStyle = lg; x.fillRect(g.cx - g.topR * 1.3, g.liqTop, g.topR * 2.6, g.liqBot - g.liqTop);
    var sg = x.createLinearGradient(g.cx - g.topR, 0, g.cx + g.topR, 0);
    sg.addColorStop(0, 'rgba(0,0,0,.28)'); sg.addColorStop(0.28, 'rgba(255,255,255,.10)');
    sg.addColorStop(0.62, 'rgba(0,0,0,0)'); sg.addColorStop(1, 'rgba(0,0,0,.30)');
    x.fillStyle = sg; x.fillRect(g.cx - g.topR * 1.3, g.liqTop, g.topR * 2.6, g.liqBot - g.liqTop);
    x.restore();
  }

  /* 液中の淡い星雲(案C) */
  function pourNebula(x, g, b, col, acc) {
    var n = norm(b), r = rnd(b.id + 'nb'), i;
    x.save(); liquidClip(x, g);
    for (i = 0; i < 5; i++) {
      var py = g.liqTop + (0.2 + r() * 0.7) * (g.liqBot - g.liqTop);
      var px = g.cx + (r() - 0.5) * g.topR * 1.3;
      var rr = g.topR * (0.5 + r() * (0.5 + n.haze * 0.4));
      var c = i % 2 === 0 ? lighten(col, 0.5) : acc;
      var gg = x.createRadialGradient(px, py, 0, px, py, rr);
      gg.addColorStop(0, rgba(c, 0.09 + n.alc * 0.07));
      gg.addColorStop(1, rgba(c, 0));
      x.fillStyle = gg; x.beginPath(); x.arc(px, py, rr, 0, 7); x.fill();
    }
    x.restore();
  }

  /* 液中を漂う星 */
  function driftStars(x, g, b, acc) {
    var n = norm(b), r = rnd(b.id + 'st');
    x.save(); liquidClip(x, g);
    var cnt = Math.round(6 + n.ibu * 10);
    for (var i = 0; i < cnt; i++) {
      var py = g.liqTop + g.gh * 0.06 + r() * (g.liqBot - g.liqTop - g.gh * 0.08);
      var rr = rAt(g, py) * 0.88;
      var px = g.cx + (r() - 0.5) * rr * 2;
      var s = Math.max(0.45, g.topR * 0.022 * (0.4 + r()));
      x.fillStyle = r() < n.fr * 0.45 ? rgba(acc, 0.35 + r() * 0.35)
                                      : 'rgba(255,255,255,' + (0.22 + r() * 0.38) + ')';
      x.beginPath(); x.arc(px, py, s, 0, 7); x.fill();
    }
    x.restore();
  }

  /* 泡 */
  function pourFoam(x, g, b, col) {
    var n = norm(b), r = rnd(b.id + 'foam');
    var fh = g.gh * 0.085 * (0.80 + n.haze * 0.55);
    var fTop = g.liqTop - fh;
    x.save(); glassPath(x, g); x.clip();
    var lg = x.createLinearGradient(0, fTop, 0, g.liqTop + fh * 0.18);
    lg.addColorStop(0, 'rgba(255,253,247,.97)');
    lg.addColorStop(0.55, 'rgba(253,248,238,.92)');
    lg.addColorStop(0.85, rgba(lighten(col, 0.78), 0.72));
    lg.addColorStop(1, rgba(lighten(col, 0.55), 0));
    x.fillStyle = lg; x.fillRect(g.cx - g.topR * 1.3, fTop, g.topR * 2.6, fh * 1.18);
    var sg = x.createLinearGradient(g.cx - g.topR, 0, g.cx + g.topR, 0);
    sg.addColorStop(0, 'rgba(150,140,175,.28)'); sg.addColorStop(0.3, 'rgba(255,255,255,0)');
    sg.addColorStop(0.75, 'rgba(255,255,255,0)'); sg.addColorStop(1, 'rgba(140,130,165,.30)');
    x.fillStyle = sg; x.fillRect(g.cx - g.topR * 1.3, fTop, g.topR * 2.6, fh);
    for (var i = 0; i < 16; i++) {
      var px = g.cx + (r() - 0.5) * g.topR * 1.9, py = fTop + r() * fh;
      x.fillStyle = 'rgba(255,255,255,' + (0.30 + r() * 0.45) + ')';
      x.beginPath(); x.arc(px, py, Math.max(0.5, g.topR * 0.038 * (0.35 + r())), 0, 7); x.fill();
    }
    var rr = rAt(g, fTop);
    var tg = x.createLinearGradient(g.cx - rr, 0, g.cx + rr, 0);
    tg.addColorStop(0, 'rgba(236,230,220,1)'); tg.addColorStop(0.4, 'rgba(255,254,250,1)');
    tg.addColorStop(1, 'rgba(226,220,210,1)');
    x.fillStyle = tg; x.beginPath(); x.ellipse(g.cx, fTop, rr * 0.99, rr * 0.17, 0, 0, 7); x.fill();
    x.restore();
  }

  /* ガラス */
  function glassShell(x, g) {
    x.save(); glassPath(x, g); x.clip();
    var lg = x.createLinearGradient(g.cx - g.topR, 0, g.cx - g.topR * 0.35, 0);
    lg.addColorStop(0, 'rgba(255,255,255,.16)'); lg.addColorStop(1, 'rgba(255,255,255,0)');
    x.fillStyle = lg; x.fillRect(g.cx - g.topR, g.top, g.topR * 0.7, g.gh);
    lg = x.createLinearGradient(g.cx + g.topR * 0.45, 0, g.cx + g.topR, 0);
    lg.addColorStop(0, 'rgba(255,255,255,0)'); lg.addColorStop(1, 'rgba(255,255,255,.12)');
    x.fillStyle = lg; x.fillRect(g.cx + g.topR * 0.45, g.top, g.topR * 0.55, g.gh);
    lg = x.createLinearGradient(0, g.bot - g.gh * 0.075, 0, g.bot);
    lg.addColorStop(0, 'rgba(255,255,255,.05)'); lg.addColorStop(1, 'rgba(255,255,255,.20)');
    x.fillStyle = lg; x.fillRect(g.cx - g.botR, g.bot - g.gh * 0.075, g.botR * 2, g.gh * 0.075);
    x.restore();
    x.strokeStyle = 'rgba(228,226,255,.40)'; x.lineWidth = Math.max(1, g.topR * 0.055);
    glassPath(x, g); x.stroke();
    x.strokeStyle = 'rgba(255,255,255,.55)'; x.lineWidth = Math.max(1, g.topR * 0.05);
    x.beginPath(); x.ellipse(g.cx, g.top, g.topR, g.topR * 0.17, 0, 0, 7); x.stroke();
    x.strokeStyle = 'rgba(255,255,255,.16)'; x.lineWidth = Math.max(0.8, g.topR * 0.035);
    x.beginPath(); x.ellipse(g.cx, g.bot - g.gh * 0.012, g.botR * 0.92, g.botR * 0.15, 0, 0, 7); x.stroke();
  }

  function draw(canvas, b) {
    var dpr = Math.min(2, window.devicePixelRatio || 1);
    var rect = canvas.getBoundingClientRect();
    if (rect.width < 2 || rect.height < 2) return;
    canvas.width = Math.round(rect.width * dpr);
    canvas.height = Math.round(rect.height * dpr);
    var x = canvas.getContext('2d');
    var W = canvas.width, H = canvas.height;
    x.clearRect(0, 0, W, H);
    var g = glassGeom(W, H), col = beerColor(b.c), acc = hexToRgb(GC[b.g] || GC.other), n = norm(b);
    var gr = x.createRadialGradient(g.cx, g.midY, 0, g.cx, g.midY, g.topR * (2.5 + n.alc * 1.1));
    gr.addColorStop(0, rgba(col, 0.20 + n.alc * 0.15)); gr.addColorStop(1, rgba(col, 0));
    x.fillStyle = gr; x.beginPath(); x.arc(g.cx, g.midY, g.topR * (2.5 + n.alc * 1.1), 0, 7); x.fill();
    pourLiquid(x, g, col);
    pourNebula(x, g, b, col, acc);
    driftStars(x, g, b, acc);
    pourFoam(x, g, b, col);
    glassShell(x, g);
  }

  function readBeer(el) {
    var num = function (v, dflt) { var f = parseFloat(v); return isNaN(f) ? dflt : f; };
    return {
      id: el.dataset.bid || 'pr0000',
      c:  clamp01((num(el.dataset.c, 4) - 1) / 9) * 9 + 1,   // 1-10 に収める
      cl: num(el.dataset.cl, 2),
      f:  num(el.dataset.f, 2),
      i:  num(el.dataset.i, 30),
      a:  num(el.dataset.a, 5),
      g:  el.dataset.g || 'other'
    };
  }

  function paintAll(root) {
    var els = (root || document).querySelectorAll('canvas.beerglass');
    Array.prototype.forEach.call(els, function (el) { draw(el, readBeer(el)); });
  }

  /* 公開API (動的に増やした要素にも使える) */
  window.paintBeerGlasses = paintAll;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { paintAll(); });
  } else {
    paintAll();
  }
  var t;
  window.addEventListener('resize', function () {
    clearTimeout(t); t = setTimeout(function () { paintAll(); }, 120);
  });
})();
