/*
 * ②解析中の絵(設計書 2026-09-19 §2-b)。
 *
 * 味覚の宇宙へ1点が投げ込まれる。これは飾りではなく、③の評価が
 * 「クラフトビールのユニバースに照らして言語化する」ことの、そのままの絵である。
 *
 * galaxy.js(3D・200銘柄・ドラッグ操作つき)は持ち込まない。
 * 待ち時間を埋めるはずのものが待ち時間を作っては意味がない。
 *
 * 終わりの尺は決めない。応答が返るとブラウザが遷移するので、それまで回り続ける。
 */
function startWarp(opts) {
  var o = opts || {};
  var host = document.getElementById(o.mount || 'warp');
  if (!host) { return; }
  var cv = document.createElement('canvas');
  host.appendChild(cv);
  var x = cv.getContext('2d');
  if (!x) { return; }

  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var W = 0, H = 0;
  function size() {
    // 幕を出した直後は、端末によって寸法が 0 で返ることがある。
    // 0 のまま canvas を作ると何も見えない(実機で「絵が出ない」と報告された)
    W = host.clientWidth  || window.innerWidth  || 320;
    H = host.clientHeight || window.innerHeight || 480;
    cv.width = Math.floor(W * DPR); cv.height = Math.floor(H * DPR);
    cv.style.width = W + 'px'; cv.style.height = H + 'px';
    x.setTransform(DPR, 0, 0, DPR, 0, 0);
  }
  size();
  window.addEventListener('resize', size);

  var colors = (o.colors && o.colors.length) ? o.colors : ['#7f8b93'];
  var accent = o.accent || '#cfe6f2';

  // 既存の星。ゆっくり漂うだけ
  var stars = [];
  for (var i = 0; i < 90; i++) {
    stars.push({
      x: Math.random(), y: Math.random(),
      r: 0.6 + Math.random() * 1.6,
      a: 0.15 + Math.random() * 0.45,
      vx: (Math.random() - 0.5) * 0.00018,
      vy: (Math.random() - 0.5) * 0.00018,
      c: colors[i % colors.length]
    });
  }

  // 外から飛び込む1点。減速して星々の中に落ち着き、また投げ込まれる
  var t0 = performance.now();
  var DUR = 2600;   // 1周の長さ。終わりではなく、繰り返しの単位
  var raf = 0, alive = true;

  function easeOut(p) { return 1 - Math.pow(1 - p, 3); }

  function frame(now) {
    if (!alive) { return; }
    x.clearRect(0, 0, W, H);

    for (var i = 0; i < stars.length; i++) {
      var s = stars[i];
      s.x += s.vx; s.y += s.vy;
      if (s.x < 0) { s.x += 1; } if (s.x > 1) { s.x -= 1; }
      if (s.y < 0) { s.y += 1; } if (s.y > 1) { s.y -= 1; }
      x.globalAlpha = s.a;
      x.fillStyle = s.c;
      x.beginPath(); x.arc(s.x * W, s.y * H, s.r, 0, 7); x.fill();
    }

    var p = ((now - t0) % DUR) / DUR;
    var e = easeOut(p);
    var cx = W * 0.5, cy = H * 0.5;
    var px = W * 1.15 - (W * 1.15 - cx) * e;
    var py = H * 0.12 + (cy - H * 0.12) * e;

    // 尾。速いうちは長く、落ち着くほど短い
    var tail = (1 - e) * 120;
    var g = x.createLinearGradient(px + tail, py - tail * 0.35, px, py);
    g.addColorStop(0, 'rgba(255,255,255,0)');
    g.addColorStop(1, accent);
    x.globalAlpha = 0.55 * (1 - e) + 0.2;
    x.strokeStyle = g; x.lineWidth = 1.4;
    x.beginPath(); x.moveTo(px + tail, py - tail * 0.35); x.lineTo(px, py); x.stroke();

    x.globalAlpha = 1;
    x.fillStyle = accent;
    x.beginPath(); x.arc(px, py, 2.6 + (1 - e) * 1.4, 0, 7); x.fill();

    // 落ち着いたところで、ほのかに広がる輪
    if (e > 0.85) {
      var q = (e - 0.85) / 0.15;
      x.globalAlpha = 0.35 * (1 - q);
      x.strokeStyle = accent; x.lineWidth = 1;
      x.beginPath(); x.arc(cx, cy, 6 + q * 42, 0, 7); x.stroke();
    }

    raf = requestAnimationFrame(frame);
  }
  // 動きを減らす設定の端末では回さない。CSS は canvas を隠すだけなので、
  // ここで止めないと見えない絵を描き続けることになる
  var reduce = false;
  try {
    reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  } catch (e) { reduce = false; }
  if (reduce) {
    // 動きは出さないが、**絵は出す**。星野と、落ち着いた1点を1枚だけ描く。
    // 何も描かないと、待っている間に何が起きているのか分からなくなる
    frame(t0 + DUR * 0.999);
    alive = false;
  } else {
    raf = requestAnimationFrame(frame);
  }

  return function stop() { alive = false; cancelAnimationFrame(raf); };
}
