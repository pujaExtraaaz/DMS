<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="color-scheme" content="light only">
<title>Coming soon</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#FBFBFF; --ink:#1D1E20; --ink-2:#2F3040; --muted:#6B6E80; --dot:#383C44;
  --edge:122,100,255; --center:255,255,255;
  --g1:#5652E6; --g2:#673DE6; --mag:194,59,208;
  color-scheme:light;
  box-sizing:border-box;
  padding-top:env(safe-area-inset-top,0px);
  padding-bottom:env(safe-area-inset-bottom,0px);
}
*,*::before,*::after{box-sizing:inherit}
html{height:100%;scroll-padding-top:env(safe-area-inset-top,0px);background:var(--bg)}
body{margin:0;min-height:100%;background:var(--bg);color:var(--ink);font-family:'DM Sans','Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased}
main{
  position:relative;min-height:100vh;min-height:100dvh;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:32px 24px;overflow:hidden;
  background:
    radial-gradient(ellipse 50% 55% at 50% 50%, rgba(var(--center),.35) 0%, rgba(var(--center),.15) 50%, rgba(var(--center),0) 100%),
    radial-gradient(ellipse farthest-corner at 50% 50%, rgba(var(--edge),0) 0%, rgba(var(--edge),0) 30%, rgba(var(--edge),.07) 55%, rgba(var(--edge),.15) 80%, rgba(var(--edge),.22) 100%),
    var(--bg);
}
.stack{width:100%;max-width:760px;display:flex;flex-direction:column;align-items:center;gap:56px;text-align:center}
#logo{width:min(240px,62vw);aspect-ratio:1;height:auto;display:block;touch-action:manipulation;cursor:pointer}
.titles{display:flex;flex-direction:column;align-items:center;gap:10px}
.status{
  margin:0;font-size:40px;line-height:1.15;font-weight:500;letter-spacing:-0.02em;
  background:
    radial-gradient(circle 90px at var(--mx,85%) var(--my,50%), rgb(var(--mag)) 0%, rgba(var(--mag),0) 100%),
    linear-gradient(90deg, var(--g1) 0%, var(--g2) 18%, var(--g2) 100%);
  -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:var(--g2);
}
.domain{margin:0;font-size:20px;line-height:1.4;font-weight:500;color:var(--ink-2);word-break:break-word}
footer{position:absolute;left:50%;bottom:64px;transform:translateX(-50%);width:calc(100% - 48px);max-width:520px;text-align:center}
footer p{margin:0;font-size:15px;line-height:1.6;color:var(--muted);display:flex;flex-wrap:wrap;justify-content:center;align-items:baseline;column-gap:.3em}
footer strong{font-weight:700;color:var(--ink)}
footer a{color:var(--muted);text-decoration:underline;position:relative;font:inherit;line-height:inherit;margin:0;padding:0;vertical-align:baseline}
footer a::after{content:"";position:absolute;inset:-12px -6px}
footer a:hover{color:var(--ink)}
footer a:focus-visible,#logo:focus-visible{outline:2px solid var(--g2);outline-offset:3px;border-radius:4px}
@media (max-width:480px){.status{font-size:32px}.domain{font-size:18px}footer{bottom:24px}.stack{gap:40px}}

/* World map on the H dot grid, behind the content */
#map{display:block;position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0}
.stack,footer{z-index:1}
.stack{position:relative}

</style>
</head>
<body>
<main>
  <canvas id="map" aria-hidden="true"></canvas>
  <div class="stack">
    <canvas id="logo" role="img" aria-label="Hostinger"></canvas>
    <div class="titles">
      <h1 class="status" id="status">Coming soon</h1>
      <p class="domain" id="domain" hidden></p>
    </div>
  </div>
  <footer><p><span>Hosted by <strong>HOSTINGER</strong></span><span>·</span><span>Website owner?</span><a href="https://hpanel.hostinger.com">Log in</a></p></footer>
</main>
<script>
(() => {
  const host = location.hostname.replace(/^www\./, '');
  const domainEl = document.getElementById('domain');
  if (host && !/(^|\.)hostingersite\.com$/i.test(host) && !/^[\d.]+$|^\[/.test(host)) {
    domainEl.textContent = host;
    domainEl.hidden = false;
  }

  /* World land mask: 180x90 bitmap, 2-degree cells (Natural Earth 110m) */
  const globe = (() => {
    const MASK = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOAfAP8HAAAAAAAAAAAAAAAAAAAAAADo//z//wcAAAAAAAACAAAAAAAAAAAAhvvw//8PAPABAAAAwAMAAAAAAAAAwADkw////wEABAAAAABgAAAAAAAAAADAUT8A/v8PAAAAAAwA/j8AuAEAAAAAcBHtDcD/fwAAAAAwAPz/fwMAABCAAQD5w/wD8P8FAAAcAIL7//9//xOAAP/ff0668QD/HwAA+A8At///////v3/w/////x8++B8AAOD/1//7////////jP////+/+AE/gAcAn9f//////////w/w/////wEs4AEAAHz+////////////gL////8HeAAcAADg5///////////9ADgAf7/f4AnAAAAAH78////////H0QAAAiA//8f8AcAAIBB4////////38ADwAQAOD//5//AQAAHAT/////////A3AAAAAA/v//+T8AAGDz//////////8DAQAAAMD/////AwAAsP//////////LwAAAAAA6P///2IAAAD+//////////8CAAAAAAD///8/CAAA4P//////////JwAAAAAA8P///wYAAAD+/unz/////z8AAAAAAAD///8HAAAA/pgPPP//////MQAAAAAA8P//PwAAAMBD9v7n/////wcBAAAAAAD///8AAAAAPkD7f/7///8hEAAAAAAA4P//DwAAAIDhAv/n////f8YAAAAAAAD8//8AAAAA+AdE//////8jDwAAAAAAgP//AwAAAMD/APD/////PxgAAAAAAADw/x8AAAAA/n/v//////8HAAAAAAAAAPwDAgAAAOD////7////fwAAAAAAAACgHyAAAACA//9/f/7///8DAAAAAAAAAPQBAAAAAPj//+cv+P//PwAAAAAAAAAAHjAAAADA/////g/+//8EAAAAAAAAAOBhCAAAAP7//99/4D//AAAAAAAAAAAAPAMEAADA////+Qf84BcAAAAAAAAAAAA/AAAAAPz//58fgAf+QAAAAAAAAAAAAA8AAADg////ewA4gA8EAAAAAAAAAADAAAAAAPz//38BgAP4QQAAAAAAAAAAAAgPAADA////zwAwgAwQAAAAAAAAAAAA9Q8AAPj///8HAAVIAAAAAAAAAAAAAID/AQAA////fwBAAAAQAAAAAAAAAAAA+P8AAGDh//8DAAA0GAAAAAAAAAAAAID/HwAAAPj/HwAAgMIBAAAAAAAAAAAA/P8BAACA//8AAAAYXgAAAAAAAAAAAMD/fwAAAPz/BwAAAOOBAQAAAAAAAAAA/P8/AACA/z8AAABgbtQBAAAAAAAAAOD//w8AAPD/AwAAAAQIeAAAAAAAAAAA/P//AQAA/z8AAACAA4APAQAAAAAAAID//w8AAPD/AwAAAAARsEAAAAAAAAAA+P9/AAAA/j8AAAAAAAAAAAAAAAAAAAD//wcAAPD/QwAAAACAIwAAAAAAAAAA8P9/AAAA/z8EAAAAAD8GIAAAAAAAAAD8/wMAAPD/cQAAAAD4ZwAAAAAAAAAAgP8/AAAA/w8HAAAAgP8HAAAAAAAAAAD4/wMAAOD/MAAAAAD//wEBAAAAAAAAgP8PAAAA/g8DAAAA+P8fAAAAAAAAAAD4PwAAAOB/EAAAAID//wMAAAAAAAAAgP8DAAAA/AMAAAAA+P9/AAAAAAAAAAD8HwAAAMA/AAAAAID//wcAAAAAAAAAwP8BAAAA+AEAAAAA8P9/AAAAAAAAAAD8DwAAAIAPAAAAAAAP/gMAAAAAAAAAwB8AAAAAAAAAAAAAEIAfAAEAAAAAAAD+AwAAAAAAAAAAAAAA8AEgAAAAAAAA4AcAAAAAAAAAAAAAAAAAAAYAAAAAAABeAAAAAAAAAAAAAAAAwAAwAAAAAAAAwAMAAAAAAAAAAAAAAAAIgAEAAAAAAAAeAAAAAAAAAAAAAAAAAAAMAAAAAAAA4AEAAAAAAAAAAAAAAAAAAAAAAAAAAAAPAAAAAAAAAAABAAAAAAAAAAAAAAAAcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOAAAAAAAAAAAAAAAAAAAAAAAAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAAAAB4AEDwn/8HAAAAAAAAAAAwAAAAAACA/P/h/////w8AAAAAAAAAwAcAAAD4////z///////PwAAAAAAHALwAACA//////////////8HAADw/y///wMAAP7/////////////HwAA+P///38AAID///////////////8AAPL/////BwAO////////////////DwAA8P////8HEPD//////////////z8AAOD/////////////////////////H/AfwP//////////////////////////////////////////////////////////////////////////////////////';
    const bin = atob(MASK), mb = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) mb[i] = bin.charCodeAt(i);
    const isLand = (lat, lon) => {
      const i = ((Math.floor((lon + 180) / 2) % 180) + 180) % 180;
      const j = Math.min(89, Math.max(0, Math.floor((90 - lat) / 2)));
      const k = j * 180 + i; return (mb[k >> 3] >> (k & 7)) & 1;
    };
    return { isLand };
  })();

  const H_PATH = new Path2D('M0.000188531 14.046V0.000497794L7.0891 3.78046V10.1086L16.4734 10.1132L23.6773 14.046H0.000188531ZM18.3924 8.95058V0L25.6725 3.6859V13.1797L18.3924 8.95058ZM18.3924 26.1177V19.8441L8.93571 19.8375C8.94454 19.8793 1.61224 15.8418 1.61224 15.8418L25.6725 15.9547V30L18.3924 26.1177ZM-6.10352e-05 26.1177L0.000191176 16.9393L7.0891 21.0683V29.8033L-6.10352e-05 26.1177Z');
  const H_BOX = { w: 25.6725, h: 30 };
  const fillH = (ctx, S) => {
    const sc = S * 0.9 / H_BOX.h, ox = (S - H_BOX.w * sc) / 2, oy = (S - H_BOX.h * sc) / 2;
    ctx.save(); ctx.setTransform(sc, 0, 0, sc, ox, oy); ctx.fill(H_PATH, 'evenodd'); ctx.restore();
  };
  const H_GRID = { S: 240, gap: 7, g0: (240 - 7 * Math.floor(240 / 7)) / 2 + 3.5 };

  const startWorldMap = (() => {
    const mc2 = document.getElementById('map'), m2 = mc2.getContext('2d');
    const logoEl = document.getElementById('logo');
    const MAP_COLOR = '#DDD7FF'; /* light violet, solid; never changes on hover */
    const LAT0 = 80, LAT1 = -58;
    const TOP_SCATTER = 9; /* degrees of scattered dots added above the map's top line */
    const LON_SHIFT = 25; /* degrees the map is moved right (dot grid unchanged) */
    const KEEP_GAP = 48; /* distance labels keep from the H, texts and footer */
    const DOT_GAP = 26; /* (scattered) distance map dots keep from the texts */
    const MONO = '11px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let pts = [], W = 0, H = 0, dpr = 1, k = 1, R = 2.5;
    let running = false, last = 0, lastDraw = 0, dirty = false, keep = [];
    const ANN = [];
    let proj2 = null;
    const mouse = { x: -9999, y: -9999 };
    const hCells = (() => {
      const { S, gap, g0 } = H_GRID, c = document.createElement('canvas'); c.width = S; c.height = S;
      const x = c.getContext('2d');
      fillH(x, S);
      const d = x.getImageData(0, 0, S, S).data, set = new Set();
      for (let j = 0, y = g0; y < S; j++, y += gap) for (let i = 0, xx = g0; xx < S; i++, xx += gap)
        if (d[(Math.floor(y) * S + Math.floor(xx)) * 4 + 3] > 128) set.add(i + ',' + j);
      return set;
    })();
    const build = () => {
      W = mc2.clientWidth; H = mc2.clientHeight; if (!W || !H) return false;
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      mc2.width = Math.round(W * dpr); mc2.height = Math.round(H * dpr);
      const mr = mc2.getBoundingClientRect(), lr = logoEl.getBoundingClientRect();
      if (!lr.width) return false;
      k = lr.width / H_GRID.S; const gap = H_GRID.gap * k; R = 2.5 * k;
      const ox = lr.left - mr.left + H_GRID.g0 * k, oy = lr.top - mr.top + H_GRID.g0 * k;
      const Wm = Math.max(W * 1.04, H * 2.2), Hm = Wm * (LAT0 - LAT1) / 360;
      const mx = (W - Wm) / 2 + LON_SHIFT / 360 * Wm, my = (H - Hm) / 2; /* shifted right: Atlantic near the center */
      proj2 = { mx, my, Wm, Hm, gap };
      pts = [];
      const i0 = Math.ceil(-ox / gap) - 1, i1 = Math.floor((W - ox) / gap); /* one extra column left of the screen for the smooth slide */
      const j0 = Math.ceil(-oy / gap), j1 = Math.floor((H - oy) / gap);
      for (let j = j0; j <= j1; j++) {
        const y = oy + j * gap, lat = LAT0 - (y - my) / Hm * (LAT0 - LAT1);
        if (lat > Math.min(89.9, LAT0 + TOP_SCATTER) || lat < LAT1) continue;
        for (let i = i0; i <= i1; i++) {
          if (hCells.has(i + ',' + j)) continue;
          const x = ox + i * gap, lon = (((-180 + (x - mx) / Wm * 360) % 360) + 540) % 360 - 180; /* wraps around */
          /* g: spatially clustered noise, so neighbouring dots reveal together as groups */
          const g = 0.65 * hash(Math.floor(i / 6), Math.floor(j / 6)) + 0.35 * hash(Math.floor(i / 3) + 101, Math.floor(j / 3) + 37);
          const n = Math.random();
          /* scattered top edge: the full map stays; above its top line, extra rows of dots thin out */
          const top = Math.min(1, Math.max(0, (lat - LAT0) / TOP_SCATTER));
          if (top > 0 && 0.5 * n + 0.5 * g < top * 0.95) continue;
          if (globe.isLand(lat, lon)) {
            pts.push({ hx: x, hy: y, x, y, vx: 0, vy: 0, lat, lon, n, g, i, j });
          }
        }
      }
      keep = ['#logo', '.titles', 'footer p'].map(sel => document.querySelector(sel)).filter(el => el && !el.hidden)
        .map(el => { const r = el.getBoundingClientRect(); return { l: r.left - mr.left - KEEP_GAP, t: r.top - mr.top - KEEP_GAP, r: r.right - mr.left + KEEP_GAP, b: r.bottom - mr.top + KEEP_GAP }; });
      pts.forEach((p, idx) => { p.idx = idx; p.depth = -1; p.kd = false; });
      if (still) {
        const zones = [['.titles', DOT_GAP], ['footer p', DOT_GAP]].map(([sel, gp]) => [document.querySelector(sel), gp]).filter(([el]) => el && !el.hidden)
          .map(([el, gp]) => { const r = el.getBoundingClientRect(); return { l: r.left - mr.left, t: r.top - mr.top, r: r.right - mr.left, b: r.bottom - mr.top, gp }; });
        for (const p of pts) {
          const f = 0.1 + 1.2 * (0.5 * p.n + 0.5 * p.g);
          p.kd = zones.some(z => { const dx = Math.max(z.l - p.hx, 0, p.hx - z.r), dy = Math.max(z.t - p.hy, 0, p.hy - z.b), t2 = z.gp * f; return dx * dx + dy * dy < t2 * t2 || (dx === 0 && dy === 0); });
        }
      }
      /* how far each land dot is from the coast (in grid steps), for the melting disappearance */
      { const at = new Map(); for (const p of pts) at.set(p.i + ',' + p.j, p);
        const q = [];
        for (const p of pts) {
          if (!at.has((p.i + 1) + ',' + p.j) || !at.has((p.i - 1) + ',' + p.j) || !at.has(p.i + ',' + (p.j + 1)) || !at.has(p.i + ',' + (p.j - 1))) { p.depth = 0; q.push(p); }
        }
        for (let h = 0; h < q.length; h++) {
          const p = q[h];
          for (const [di, dj] of [[1, 0], [-1, 0], [0, 1], [0, -1]]) {
            const n2 = at.get((p.i + di) + ',' + (p.j + dj));
            if (n2 && n2.depth < 0) { n2.depth = p.depth + 1; q.push(n2); }
          }
        }
        for (const p of pts) if (p.depth < 0) p.depth = 0; }
      /* no labels in the column right above the H */
      { const r = logoEl.getBoundingClientRect(); keep.push({ l: r.left - mr.left - 70, t: -1e4, r: r.right - mr.left + 70, b: r.top - mr.top }); }
      ANN.length = 0;
      return true;
    };
    const hash = (a, b) => { const v = Math.sin(a * 12.9898 + b * 78.233) * 43758.5453; return v - Math.floor(v); };
    const clear = (x, y, w, h) => keep.every(z => x + w < z.l || x > z.r || y + h < z.t || y > z.b);
    const fmt = (lat, lon) => Math.abs(lat).toFixed(2) + '\u00b0' + (lat >= 0 ? 'N' : 'S') + ' ' + Math.abs(lon).toFixed(2) + '\u00b0' + (lon >= 0 ? 'E' : 'W');
    const tag = (x, y, text) => {
      m2.font = MONO;
      const tw = m2.measureText(text).width;
      m2.fillStyle = '#EFEBFF'; m2.beginPath();
      if (m2.roundRect) m2.roundRect(x, y, tw + 10, 17, 8.5); else m2.rect(x, y, tw + 10, 17);
      m2.fill();
      m2.fillStyle = '#5B3AD0'; m2.fillText(text, x + 5, y + 12);
    };
    const MAX_LABELS = 60;
    const LW = 168; /* label box width incl. dot */
    const labelBox = (p, side) => (side > 0 ? { x: p.hx - 4, y: p.hy - 12, w: LW, h: 24 } : { x: p.hx - LW + 4, y: p.hy - 12, w: LW, h: 24 });
    /* Major cities with their real coordinates (lat, lon); labels pin to the map dot nearest each city */
    const CITIES = {
      NA: [[40.71, -74.01], [34.05, -118.24], [41.88, -87.63], [43.65, -79.38], [19.43, -99.13], [37.77, -122.42], [49.28, -123.12], [25.76, -80.19], [45.50, -73.57], [29.76, -95.37], [47.61, -122.33], [33.75, -84.39], [39.74, -104.99], [33.45, -112.07], [32.78, -96.80], [42.36, -71.06], [39.95, -75.17], [38.91, -77.04], [42.33, -83.05], [44.98, -93.27], [36.17, -115.14], [51.05, -114.07], [20.66, -103.35], [25.69, -100.32], [23.11, -82.37], [8.98, -79.52]],
      SA: [[-23.55, -46.63], [-34.60, -58.38], [-12.05, -77.04], [4.71, -74.07], [-33.45, -70.67], [-22.91, -43.17], [10.48, -66.90], [-0.18, -78.47], [-34.90, -56.16], [-8.05, -34.88], [-15.79, -47.88], [-12.97, -38.50], [-3.73, -38.53], [6.24, -75.58], [-2.19, -79.89], [-16.49, -68.12], [-25.26, -57.58], [-19.92, -43.94]],
      EU: [[51.51, -0.13], [48.86, 2.35], [52.52, 13.40], [40.42, -3.70], [41.90, 12.50], [54.69, 25.28], [52.23, 21.01], [52.37, 4.90], [59.33, 18.07], [50.45, 30.52], [41.01, 28.98],
           [38.72, -9.14], [53.35, -6.26], [59.91, 10.75], [55.68, 12.57], [50.08, 14.44], [48.21, 16.37], [47.50, 19.04], [41.39, 2.17], [45.46, 9.19], [37.98, 23.73], [60.17, 24.94], [56.95, 24.11], [48.14, 11.58], [50.85, 4.35], [47.38, 8.54], [44.43, 26.10], [53.55, 9.99], [50.11, 8.68], [45.76, 4.84], [43.30, 5.37], [53.48, -2.24], [55.95, -3.19], [41.15, -8.61], [37.39, -5.98], [40.85, 14.27], [50.06, 19.94], [42.70, 23.32], [44.79, 20.45], [59.44, 24.75], [57.71, 11.97]],
      AF: [[30.04, 31.24], [6.52, 3.38], [-1.29, 36.82], [-26.20, 28.05], [33.57, -7.59], [9.03, 38.74], [-4.32, 15.31], [14.72, -17.47], [5.60, -0.19], [36.75, 3.06], [-6.79, 39.21], [-33.92, 18.42], [5.36, -4.01], [0.35, 32.58], [-8.84, 13.23], [15.50, 32.56], [36.81, 10.18], [-29.86, 31.03], [12.00, 8.52], [-15.39, 28.32], [-17.83, 31.05], [31.63, -7.98]],
      AS: [[35.68, 139.69], [39.90, 116.41], [31.23, 121.47], [19.08, 72.88], [28.61, 77.21], [1.35, 103.82], [37.57, 126.98], [25.20, 55.27], [-6.21, 106.85], [13.76, 100.50],
           /* India */ [12.97, 77.59], [13.08, 80.27], [22.57, 88.36], [17.39, 78.49], [23.02, 72.57], [18.52, 73.86], [26.91, 75.79], [26.85, 80.95], [22.32, 114.17], [24.71, 46.68], [24.86, 67.01], [23.81, 90.41], [14.60, 120.98], [34.69, 135.50], [25.29, 51.53], [24.45, 54.38], [23.59, 58.41], [21.49, 39.19], [32.09, 34.78], [31.95, 35.93], [39.93, 32.86], [31.55, 74.34], [27.72, 85.32], [6.93, 79.86], [21.17, 72.83], [21.15, 79.09], [9.93, 76.27], [41.30, 69.24]],
      OC: [[-33.87, 151.21], [-37.81, 144.96], [-36.85, 174.76], [-31.95, 115.86], [-27.47, 153.03], [-34.93, 138.60], [-41.29, 174.78]]
    };
    /* never put coordinate labels over Russia, Belarus, Iran, Iraq or Afghanistan (approximate outlines) */
    const noLabel = (lat, lon) =>
      (lat >= 55 && lon >= 28) || (lat >= 50 && lon >= 37) || (lat >= 45 && lon >= 36.5 && lon <= 50) ||         /* Russia (incl. the south) */
      (lat >= 43.3 && lat < 45 && lon >= 37.5 && lon <= 41.5 && !(lat < 43.6 && lon > 40)) ||                     /* Russia: Sochi coast */
      (lat >= 54.3 && lat <= 55.3 && lon >= 19.6 && lon <= 22.9) ||                                              /* Russia: Kaliningrad */
      (lat >= 42.5 && lon >= 130.6 && lon <= 135.5) || (lat >= 45.6 && lon >= 135.5) ||                         /* Russia: Far East (not Japan) */
      (lat >= 51.3 && lat <= 55.8 && lon >= 23.5 && lon <= 32.8 && !(lat > 53.9 && lon < 26.6)) ||                /* Belarus (keeps Vilnius) */
      (lat >= 25.3 && lat <= 39.8 && lon >= 44 && lon <= 63.3 && !(lat < 26.6 && lon > 51 && lon < 57) && !(lat < 31 && lon < 48.5)) ||        /* Iran (keeps UAE) */
      (lat >= 29 && lat <= 37.4 && lon >= 38.8 && lon <= 48.6 && !(lat < 30.1 && lon > 46.5)) ||                 /* Iraq (keeps Kuwait) */
      (lat >= 29.4 && lat <= 38.5 && lon >= 60.5 && lon <= 71.3) || (lat >= 36.5 && lat <= 38.5 && lon > 71.3 && lon <= 74.9) || /* Afghanistan (not Pakistan) */
      (lat >= 37.7 && lat <= 43.1 && lon >= 124.2 && lon <= 130.8);                                               /* North Korea */
    const cityDot = (lat, lon) => {
      if (!proj2 || lat > LAT0 || lat < LAT1) return null;
      let x = proj2.mx + (lon + 180) / 360 * proj2.Wm; const y = proj2.my + (LAT0 - lat) / (LAT0 - LAT1) * proj2.Hm;
      if (x > W) x -= proj2.Wm; else if (x < 0) x += proj2.Wm;
      let best = null, bd = (proj2.gap * 2.5) ** 2;
      for (const q of pts) { const d = (q.hx - x) ** 2 + (q.hy - y) ** 2; if (d < bd) { bd = d; best = q; } }
      return best;
    };
    const ORIGINS = [[40.71, -74.01], [30.04, 31.24], [-23.55, -46.63]]; /* 3 fixed map appearances: New York, Cairo, Sao Paulo */
    const W_DELAY = 2000, W_SPREAD = 2100, W_JIT = 380, W_HOLD = 5000, W_GAP = 6300;
    let wv = null, wIdx = 0, wT = null, wV = null, plan = [];
    let cycleCache = [], dotCache = null, dotCacheKey = -1, blankDrawn = false; /* perf: 3 precomputed cycles + a bitmap of the still map */
    const MELT = 18; /* ms per grid step inland: near the front, edges melt before interiors */
    const W_SHRINK = 650; /* how long the circle takes to contract */
    /* Labels: only big cities (roughly 3M+ metro), each shown with its real coordinates */
    const BIG = new Set([
      '40.71,-74.01', '34.05,-118.24', '41.88,-87.63', '43.65,-79.38', '19.43,-99.13', '37.77,-122.42', '29.76,-95.37', '32.78,-96.8',
      '25.76,-80.19', '33.75,-84.39', '38.91,-77.04', '42.36,-71.06', '39.95,-75.17', '45.5,-73.57', '20.66,-103.35', '25.69,-100.32',
      '-23.55,-46.63', '-34.6,-58.38', '-12.05,-77.04', '4.71,-74.07', '-33.45,-70.67', '-22.91,-43.17', '-19.92,-43.94', '10.48,-66.9', '-15.79,-47.88', '6.24,-75.58',
      '51.51,-0.13', '48.86,2.35', '52.52,13.4', '40.42,-3.7', '41.9,12.5', '41.01,28.98', '41.39,2.17', '45.46,9.19', '52.23,21.01', '50.45,30.52',
      '48.21,16.37', '47.5,19.04', '53.55,9.99', '37.98,23.73', '52.37,4.9', '50.85,4.35', '38.72,-9.14', '48.14,11.58', '59.33,18.07', '44.43,26.1', '50.08,14.44',
      '30.04,31.24', '6.52,3.38', '-4.32,15.31', '-26.2,28.05', '-1.29,36.82', '-8.84,13.23', '5.36,-4.01', '-6.79,39.21', '15.5,32.56', '9.03,38.74',
      '33.57,-7.59', '5.6,-0.19', '-33.92,18.42', '36.75,3.06', '12,8.52',
      '35.68,139.69', '28.61,77.21', '31.23,121.47', '19.08,72.88', '39.9,116.41', '23.81,90.41', '24.86,67.01', '22.57,88.36', '13.76,100.5', '-6.21,106.85',
      '37.57,126.98', '14.6,120.98', '13.08,80.27', '12.97,77.59', '17.39,78.49', '31.55,74.34', '23.02,72.57', '22.32,114.17', '24.71,46.68', '1.35,103.82',
      '18.52,73.86', '25.2,55.27', '34.69,135.5', '21.49,39.19', '39.93,32.86', '21.17,72.83', '26.91,75.79', '26.85,80.95',
      '-33.87,151.21', '-37.81,144.96'
    ]);
    /* also in the label pool (no priority, random like the others): Vilnius, Berlin, Oslo */
    const D_ALWAYS = new Set(['54.69,25.28', '52.52,13.4', '59.91,10.75']);
    const D_KEEP = 0.89; /* share of big cities labelled per cycle */
    /* more real big cities (real coordinates), roughly doubling the label pool */
    const D_EXTRA = [
      [47.61, -122.33], [33.45, -112.07], [39.74, -104.99], [42.33, -83.05], [44.98, -93.27], [32.72, -117.16], [27.95, -82.46], [38.63, -90.20],
      [35.23, -80.84], [28.54, -81.38], [36.17, -115.14], [49.28, -123.12], [51.05, -114.07], [45.42, -75.70], [19.04, -98.21], [32.51, -117.04],
      [23.11, -82.37], [14.63, -90.51], [18.49, -69.93], [8.98, -79.52], [18.47, -66.11], [30.27, -97.74], [29.42, -98.49], [45.52, -122.68],
      [-8.05, -34.88], [-12.97, -38.50], [-3.73, -38.53], [-30.03, -51.23], [-25.43, -49.27], [-3.12, -60.02], [-1.46, -48.50], [-2.19, -79.89],
      [-0.18, -78.47], [3.45, -76.53], [-34.90, -56.16], [-25.26, -57.58], [-16.49, -68.12], [-17.78, -63.18], [-31.42, -64.18], [10.65, -71.64], [10.96, -74.80],
      [53.48, -2.24], [52.49, -1.89], [55.86, -4.25], [53.35, -6.26], [45.76, 4.84], [43.30, 5.37], [50.11, 8.68], [50.94, 6.96], [48.78, 9.18],
      [40.85, 14.27], [45.07, 7.69], [39.47, -0.38], [37.39, -5.98], [41.15, -8.61], [55.68, 12.57], [59.91, 10.75], [60.17, 24.94], [47.38, 8.54],
      [42.70, 23.32], [44.79, 20.45], [50.06, 19.94], [49.99, 36.23], [51.92, 4.48], [40.64, 22.94], [38.42, 27.14],
      [31.20, 29.92], [7.38, 3.95], [9.08, 7.40], [14.72, -17.47], [0.35, 32.58], [36.81, 10.18], [-29.86, 31.03], [-25.75, 28.19], [-15.39, 28.32],
      [-17.83, 31.05], [-4.04, 39.67], [4.05, 9.77], [3.85, 11.50], [6.69, -1.62], [12.64, -8.00], [12.37, -1.52], [-18.88, 47.51], [2.05, 45.32],
      [-25.97, 32.57], [-11.66, 27.48], [34.02, -6.84], [9.64, -13.58],
      [29.56, 106.55], [23.13, 113.26], [22.54, 114.06], [30.57, 104.07], [39.34, 117.36], [30.59, 114.31], [34.34, 108.94], [30.27, 120.16], [32.06, 118.80],
      [25.03, 121.57], [10.82, 106.63], [21.03, 105.85], [3.14, 101.69], [16.84, 96.17], [35.18, 129.08], [35.18, 136.91], [22.36, 91.78], [27.72, 85.32],
      [6.93, 79.86], [31.42, 73.08], [33.68, 73.05], [29.38, 47.99], [25.29, 51.53], [24.45, 54.38], [23.59, 58.41], [32.09, 34.78], [31.95, 35.93],
      [40.41, 49.87], [41.72, 44.79], [40.18, 44.51], [43.24, 76.89], [41.30, 69.24], [47.89, 106.91], [43.06, 141.35], [33.59, 130.40], [3.60, 98.67],
      [-7.25, 112.75], [-6.92, 107.62], [10.32, 123.89], [11.56, 104.92], [22.72, 75.86], [21.15, 79.09], [25.59, 85.14], [23.26, 77.41], [9.93, 76.27],
      [17.69, 83.22], [11.02, 76.96], [26.45, 80.33],
      [-31.95, 115.86], [-27.47, 153.03], [-36.85, 174.76], [-34.93, 138.60]
    ];
    const waveReset = () => { wv = { phase: 'wait', t0: performance.now() + W_DELAY }; wIdx = 0; plan = []; ANN.length = 0; cycleCache = []; dotCacheKey = -1; blankDrawn = false; };
    const hitBox = (lb, o) => lb.x < o.x + o.w && lb.x + lb.w > o.x && lb.y < o.y + o.h && lb.y + lb.h > o.y;
    const inView = (lb) => lb.x >= 6 && lb.y >= 24 && lb.x + lb.w <= W - 6 && lb.y + lb.h <= H - 6 && clear(lb.x, lb.y, lb.w, lb.h);
    const placeLabel = (L, now, hideAt) => {
      const p = L.p;
      if (ANN.length >= MAX_LABELS || noLabel(L.lat, L.lon)) return;
      { const ex = (p.hx - W / 2) / (W * 0.40), ey = (p.hy - H / 2) / (H * (p.hy > H / 2 ? 0.24 : 0.38)); if (ex * ex + ey * ey > 1) return; } /* no labels far out in the corners */
      const free = (lb) => inView(lb) && !ANN.some(a => hitBox(lb, labelBox(a.p, a.side)));
      const side = free(labelBox(p, 1)) ? 1 : free(labelBox(p, -1)) ? -1 : 0;
      if (!side) return;
      /* no vertical lines of labels - keep labels in nearby rows clearly offset sideways */
      if (ANN.some(a => (Math.abs(a.p.hx - p.hx) < 34 && Math.abs(a.p.hy - p.hy) < 170) || (Math.abs(a.p.hy - p.hy) < 6 && Math.abs(a.p.hx - p.hx) < 120))) return;
      /* label stays until the contracting circle reaches its dot; it blinks a bit as the edge comes close */
      const lifeLeft = hideAt + wV[p.idx] - now;
      if (lifeLeft < 600) return; /* too close to melting: skip rather than flash */
      ANN.push({ p, lat: L.lat, lon: L.lon, side, born: now, life: lifeLeft });
    };
    /* Each of the 3 appearances is computed once (per screen size) and then replayed:
       text-edge scatter, start point, timings and the chosen labels stay fixed per appearance. */
    const buildVariant = (vi) => {
      const [la, lo] = ORIGINS[vi];
      let o = cityDot(la, lo);
      const labelOrigin = !!o;
      if (!labelOrigin) {
        const cx0 = proj2.mx + (lo + 180) / 360 * proj2.Wm, cy0 = proj2.my + (LAT0 - la) / (LAT0 - LAT1) * proj2.Hm;
        let bd = Infinity; o = null;
        for (const q of pts) { const d = (q.hx - cx0) ** 2 + (q.hy - cy0) ** 2; if (d < bd) { bd = d; o = q; } }
      }
      if (!o) return null;
      let maxD = 1;
      for (const p of pts) maxD = Math.max(maxD, Math.hypot(p.hx - o.hx, p.hy - o.hy));
      const vT = new Float32Array(pts.length);
      for (const p of pts) vT[p.idx] = Math.hypot(p.hx - o.hx, p.hy - o.hy) / maxD * W_SPREAD + p.g * W_JIT; /* scattered front */
      /* disappearing: a scattered circle contracts into the point OPPOSITE the start point.
         Melting feel: near the edge, coastal dots go a little before inland ones */
      const vV = new Float32Array(pts.length); let vMax = 0;
      const ccx = W - o.hx, ccy = H - o.hy;
      let cMax = 1; for (const p of pts) cMax = Math.max(cMax, Math.hypot(p.hx - ccx, p.hy - ccy));
      const meltStart = W_SPREAD + W_JIT + W_HOLD; /* fully revealed map stays, then melts */
      for (const p of pts) {
        const rD = Math.hypot(p.hx - ccx, p.hy - ccy) / cMax;
        let v = (1 - rD) * W_SHRINK + p.g * 80 + p.n * 30 + Math.min(p.depth, 6) * MELT;
        v = Math.max(v, vT[p.idx] - meltStart + 450); /* every dot stays visible at least ~0.45 s */
        vV[p.idx] = v; if (v > vMax) vMax = v;
      }
      /* labels light up at cities as the map reaches them; the starting point first */
      const tpl = labelOrigin ? [{ p: o, lat: la, lon: lo, rel: 0 }] : [];
      const planned = new Set([la + ',' + lo]);
      const consider = (cla, clo, needBig) => {
        const key = cla + ',' + clo;
        if (planned.has(key) || (needBig && !BIG.has(key) && !D_ALWAYS.has(key)) || Math.random() > D_KEEP) return;
        planned.add(key);
        const q = cityDot(cla, clo); if (!q) return;
        tpl.push({ p: q, lat: cla, lon: clo, rel: vT[q.idx] + 100 + Math.random() * 200 });
      };
      for (const c in CITIES) for (const [cla, clo] of CITIES[c]) consider(cla, clo, true);
      for (const [cla, clo] of D_EXTRA) consider(cla, clo, false);
      tpl.sort((a, b) => a.rel - b.rel);
      return { wT: vT, wV: vV, vMax, meltStart, tpl };
    };
    const startCycle = (now) => {
      const vi = wIdx++ % ORIGINS.length;
      const c = cycleCache[vi] || (cycleCache[vi] = buildVariant(vi));
      if (!c) return;
      wT = c.wT; wV = c.wV;
      const hideAt = now + c.meltStart;
      wv = { phase: 'run', t0: now, hideAt, end: hideAt + c.vMax + 250 };
      plan = c.tpl.map(x => ({ p: x.p, lat: x.lat, lon: x.lon, at: now + x.rel }));
    };
    const waveTick = (now) => {
      if (!proj2) { if (!build()) return; waveReset(); }
      if (!wv) waveReset();
      if (wv.phase === 'wait') { if (now >= wv.t0) startCycle(now); return; }
      while (plan.length && plan[0].at <= now) { const L = plan.shift(); placeLabel(L, now, wv.hideAt); }
      if (now >= wv.end) { wv = { phase: 'wait', t0: now + W_GAP }; plan = []; }
    };
    /* soft light area behind the texts: from the normal dot colour out at the edge to almost the background at the centre */
    /* 16 fine steps from the normal dot colour (#DDD7FF) to the palest (#EFEDFF): a smooth, blurred transition */
    const TXT_FADE = Array.from({ length: 16 }, (_, i) => { const t = i / 15; return 'rgb(' + Math.round(221 + 18 * t) + ',' + Math.round(215 + 22 * t) + ',255)'; });
    let tgCache = null, tgKey = '';
    const textGlow = () => {
      const key = W + 'x' + H; if (tgCache && tgKey === key) return tgCache;
      const mr = mc2.getBoundingClientRect(), a1 = document.getElementById('status').getBoundingClientRect();
      const d1 = document.getElementById('domain'), b1 = d1 && !d1.hidden ? d1.getBoundingClientRect() : a1;
      const l = Math.min(a1.left, b1.left) - mr.left, r = Math.max(a1.right, b1.right) - mr.left, t = a1.top - mr.top, b = b1.bottom - mr.top;
      const lg = logoEl.getBoundingClientRect();
      tgCache = { cx: (l + r) / 2, cy: (t + b) / 2, rx: (r - l) / 2 + 160, ry: (b - t) / 2 + 110,
                  hx: (lg.left + lg.right) / 2 - mr.left, hy: (lg.top + lg.bottom) / 2 - mr.top, hrx: lg.width / 2 + 130, hry: lg.height / 2 + 90 };
      tgKey = key; return tgCache;
    };
    const glowPaths = () => Array.from({ length: TXT_FADE.length }, () => new Path2D());
    const glowAdd = (paths, x, y) => {
      const g = textGlow(), L = TXT_FADE.length;
      const ex = (x - g.cx) / g.rx, ey = (y - g.cy) / g.ry, hx = (x - g.hx) / g.hrx, hy = (y - g.hy) / g.hry;
      const t = Math.max(0, 1 - Math.sqrt(ex * ex + ey * ey), 1 - Math.sqrt(hx * hx + hy * hy)), sm = t * t * (3 - 2 * t);
      const b = Math.min(L - 1, Math.round(sm * (L - 1)));
      paths[b].moveTo(x + R, y); paths[b].arc(x, y, R, 0, Math.PI * 2);
    };
    const glowFill = (ctx, paths) => { for (let b = 0; b < paths.length; b++) { ctx.fillStyle = TXT_FADE[b]; ctx.fill(paths[b]); } };
    const draw = (now) => {
      /* perf: between cycles the canvas is empty - clear it once, then skip drawing */
      const empty = !still && (!wv || wv.phase === 'wait') && !ANN.length;
      if (empty && blankDrawn) return;
      blankDrawn = empty;
      m2.setTransform(dpr, 0, 0, dpr, 0, 0); m2.clearRect(0, 0, W, H);
      const path = new Path2D();
      /* The whole map spreads out from one point, holds, then ages away in the same order
         (oldest dots near the starting point go first, outward to the sides) */
      if (still) { for (const p of pts) if (!p.kd) { path.moveTo(p.x + R, p.y); path.arc(p.x, p.y, R, 0, Math.PI * 2); } }
      else if (wv && wv.phase === 'run' && wT) {
        const t = now - wv.t0;
        if (!dirty && t >= W_SPREAD + W_JIT && now < wv.hideAt) {
          /* map fully shown and nothing moving: draw it once into a bitmap and just copy that */
          if (dotCacheKey !== wv.t0) {
            if (!dotCache) dotCache = document.createElement('canvas');
            dotCache.width = mc2.width; dotCache.height = mc2.height;
            const dc = dotCache.getContext('2d');
            dc.setTransform(dpr, 0, 0, dpr, 0, 0);
            const gp2 = glowPaths(); for (const p of pts) glowAdd(gp2, p.hx, p.hy);
            glowFill(dc, gp2);
            dotCacheKey = wv.t0;
          }
          m2.setTransform(1, 0, 0, 1, 0, 0); m2.drawImage(dotCache, 0, 0); m2.setTransform(dpr, 0, 0, dpr, 0, 0);
        } else {
          const gp2 = glowPaths();
          for (const p of pts) {
            const tr = wT[p.idx];
            if (t < tr || now > wv.hideAt + wV[p.idx]) continue;
            glowAdd(gp2, p.x, p.y);
          }
          glowFill(m2, gp2);
        }
      }
      m2.fillStyle = MAP_COLOR; m2.fill(path);
      for (let i = ANN.length - 1; i >= 0; i--) {
        const a = ANN[i], age = now - a.born;
        if (age > a.life + 1150) { ANN.splice(i, 1); continue; }
        if (age > a.life) continue;
        const fin = Math.min(1, age / 220);
        /* while the map is on, labels now and then give a short blink, like their entrance */
        let idle = 1;
        if (age > 400 && a.life - age > 400) {
          if (!a.nb) a.nb = a.born + 900 + Math.random() * 2600;
          if (now > a.nb + 260) a.nb = now + 1400 + Math.random() * 3200;
          else if (now >= a.nb) idle = Math.floor((now - a.nb) / 65) % 2 ? 0.35 : 1;
        }
        const flick = idle * (age < 260 ? (Math.floor(age / 65) % 2 ? 0.35 : 1) : 1) *
                      (a.life - age < 260 ? (Math.floor((a.life - age) / 65) % 2 ? 1 : 0.35) : 1); /* blink out = same as blink in */
        m2.globalAlpha = fin * flick;
        m2.fillStyle = '#7660FF'; m2.beginPath(); m2.arc(a.p.x, a.p.y, R + 1, 0, Math.PI * 2); m2.fill();
        { /* label follows its pinned dot when pushed; sits left or right of it */
          const txt = fmt(a.lat, a.lon); m2.font = MONO; const tw = m2.measureText(txt).width + 10;
          tag(a.side > 0 ? Math.round(a.p.x) + 9 : Math.round(a.p.x) - 9 - Math.round(tw), Math.round(a.p.y) - 8, txt);
        }
        m2.globalAlpha = 1;
      }
    };
    const physics = (n) => {
      const reach = 60 * k; let moving = false;
      for (let s2 = 0; s2 < n; s2++) {
        moving = false;
        for (const p of pts) {
          const dx = p.x - mouse.x, dy = p.y - mouse.y;
          if (Math.abs(dx) < reach && Math.abs(dy) < reach) {
            const d = Math.hypot(dx, dy);
            if (d < reach && d > 0.01) { const f = (1 - d / reach) * 1.3 * k; p.vx += dx / d * f; p.vy += dy / d * f; }
          }
          if (p.vx !== 0 || p.vy !== 0 || p.x !== p.hx || p.y !== p.hy) {
            p.vx += (p.hx - p.x) * 0.045; p.vy += (p.hy - p.y) * 0.045;
            p.vx *= 0.84; p.vy *= 0.84; p.x += p.vx; p.y += p.vy;
            if (Math.abs(p.vx) + Math.abs(p.vy) < 0.004 && Math.abs(p.hx - p.x) + Math.abs(p.hy - p.y) < 0.02) { p.x = p.hx; p.y = p.hy; p.vx = p.vy = 0; }
            else moving = true;
          }
        }
      }
      return moving;
    };
    const step = (now) => {
      if (document.hidden) { running = false; return; }
      const dt = last ? Math.min(100, now - last) : 16.7; last = now;
      if (dirty) dirty = physics(Math.max(1, Math.min(8, Math.round(dt / 16.7 * 2))));
      waveTick(now);
      if (dirty || now - lastDraw >= 33) { draw(now); lastDraw = now; } /* labels at ~30fps */
      if (!dirty && wv && wv.phase === 'wait' && !ANN.length) {
        running = false; last = 0;
        setTimeout(wake, Math.max(0, wv.t0 - performance.now()) + 20);
        return;
      }
      requestAnimationFrame(step);
    };
    const wake = () => {
      dirty = true;
      if (still) { draw(performance.now()); return; }
      if (!running && !document.hidden) { running = true; last = 0; requestAnimationFrame(step); }
    };
    window.addEventListener('pointermove', (e) => {
      const r = mc2.getBoundingClientRect(); mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top; wake();
    }, { passive: true });
    document.documentElement.addEventListener('pointerleave', () => { mouse.x = mouse.y = -9999; wake(); });
    window.addEventListener('pointerdown', (e) => {
      if (still || performance.now() < 800 || e.button > 0 || e.target.closest('a,button')) return;
      const r = mc2.getBoundingClientRect(), qx = e.clientX - r.left, qy = e.clientY - r.top, rad = H_GRID.S * 0.9 * k;
      for (const p of pts) { const dx = p.x - qx, dy = p.y - qy; if (Math.abs(dx) > rad || Math.abs(dy) > rad) continue; const d = Math.hypot(dx, dy) || 1, f = Math.max(0, 1 - d / rad) * 22 * k; p.vx += dx / d * f; p.vy += dy / d * f; }
      wake();
    });
    const refresh = () => {
      if (!build()) return;
      waveReset();
      draw(performance.now());
      wake();
    };
    window.addEventListener('resize', refresh, { passive: true });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) wake(); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(refresh);
    return refresh;
  })();

  /* Start the map animation */
  startWorldMap();

  const cv = document.getElementById('logo');
  const head = document.getElementById('status');
  const main = document.querySelector('main');
  const S = 240, dpr = Math.min(window.devicePixelRatio || 1, 2);
  cv.width = S * dpr; cv.height = S * dpr;
  const ctx = cv.getContext('2d');

  /* Sample the H mark into a dot grid. */
  const m = document.createElement('canvas'); m.width = S; m.height = S;
  const mc = m.getContext('2d');
  fillH(mc, S);
  const data = mc.getImageData(0, 0, S, S).data;
  const gap = 7, g0 = (S - gap * Math.floor(S / gap)) / 2 + gap / 2, pts = [];
  for (let y = g0; y < S; y += gap) for (let x = g0; x < S; x += gap) {
    if (data[(Math.floor(y) * S + Math.floor(x)) * 4 + 3] > 128) pts.push({ hx: x, hy: y, x: x, y: y, vx: 0, vy: 0 });
  }

  const reach = 60, R = 2.5;
  const V = [103, 61, 230], M = [194, 59, 208];
  let A = [56, 60, 68], dotColor = '#383C44';
  const readColors = () => {
    dotColor = getComputedStyle(document.documentElement).getPropertyValue('--dot').trim() || '#383C44';
    const h = dotColor.replace('#', '');
    if (h.length === 6) A = [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
  };
  const mix = (a, b, t) => 'rgb(' + (a[0] + (b[0] - a[0]) * t | 0) + ',' + (a[1] + (b[1] - a[1]) * t | 0) + ',' + (a[2] + (b[2] - a[2]) * t | 0) + ')';
  const mouse = { x: -9999, y: -9999 };

  const draw = () => {
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, S, S);
    const lit = [], cr = reach * 1.4;
    ctx.fillStyle = dotColor;
    ctx.beginPath();
    for (const p of pts) {
      const d = Math.hypot(p.x - mouse.x, p.y - mouse.y);
      if (d < cr) { lit.push(p, d / cr); continue; }
      ctx.moveTo(p.x + R, p.y); ctx.arc(p.x, p.y, R, 0, Math.PI * 2);
    }
    ctx.fill();
    for (let i = 0; i < lit.length; i += 2) {
      const p = lit[i], t = lit[i + 1];
      ctx.fillStyle = t < 0.4 ? mix(V, M, t / 0.4) : t < 0.75 ? mix(M, V, (t - 0.4) / 0.35) : mix(V, A, (t - 0.75) / 0.25);
      ctx.beginPath(); ctx.arc(p.x, p.y, R, 0, Math.PI * 2); ctx.fill();
    }
  };

  readColors();

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    for (const p of pts) { p.x = p.hx; p.y = p.hy; }
    draw();
    return;
  }

  /* Layout is measured once and on resize/scroll, never per frame. */
  let cRect, mRect, hW = 0, hH = 0;
  const measure = () => { cRect = cv.getBoundingClientRect(); mRect = main.getBoundingClientRect(); hW = head.offsetWidth; hH = head.offsetHeight; };
  measure();
  window.addEventListener('resize', measure, { passive: true });
  window.addEventListener('scroll', measure, { passive: true });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { measure(); hx = tx = hW * 0.85; hy = hH / 2; setHead(); });

  let hx = hW * 0.85, hy = hH / 2, tx = hx;
  const setHead = () => { head.style.setProperty('--mx', hx.toFixed(1) + 'px'); head.style.setProperty('--my', hy.toFixed(1) + 'px'); };
  setHead();

  let running = false, raf = 0, last = 0;
  const SPEED = 2; /* physics steps per 60fps frame */
  const step = (now) => {
    let moving = false;
    const dt = last ? Math.min(100, now - last) : 16.7;
    last = now;
    const n = Math.max(1, Math.min(8, Math.round(dt / 16.7 * SPEED)));
    for (let s = 0; s < n; s++) {
    moving = false;
    for (const p of pts) {
      const dx = p.x - mouse.x, dy = p.y - mouse.y, d = Math.hypot(dx, dy);
      if (d < reach && d > 0.01) { const f = (1 - d / reach) * 1.3; p.vx += dx / d * f; p.vy += dy / d * f; }
      p.vx += (p.hx - p.x) * 0.045; p.vy += (p.hy - p.y) * 0.045;
      p.vx *= 0.84; p.vy *= 0.84;
      p.x += p.vx; p.y += p.vy;
      if (!moving && (Math.abs(p.vx) + Math.abs(p.vy) > 0.02)) moving = true;
    }
    }
    draw();
    if (Math.abs(tx - hx) > 0.3) { hx += (tx - hx) * Math.min(1, 0.12 * n); setHead(); moving = true; }
    if (moving && !document.hidden) raf = requestAnimationFrame(step);
    else { running = false; last = 0; }
  };
  const wake = () => { if (!running && !document.hidden) { running = true; raf = requestAnimationFrame(step); } };

  window.addEventListener('pointermove', (e) => {
    measure();
    mouse.x = (e.clientX - cRect.left) * S / cRect.width;
    mouse.y = (e.clientY - cRect.top) * S / cRect.height;
    const frac = Math.min(1, Math.max(0, (e.clientX - mRect.left) / mRect.width));
    tx = frac * hW;
    wake();
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => { mouse.x = mouse.y = -9999; wake(); });
  window.addEventListener('pointerdown', (e) => {
    measure();
    const qx = (e.clientX - cRect.left) * S / cRect.width, qy = (e.clientY - cRect.top) * S / cRect.height;
    if (performance.now() < 800 || e.button > 0) return;
    if (qx < 0 || qy < 0 || qx > S || qy > S) return;
    for (const p of pts) { const dx = p.x - qx, dy = p.y - qy, d = Math.hypot(dx, dy) || 1, f = Math.max(0, 1 - d / (S * 0.9)) * 22; p.vx += dx / d * f; p.vy += dy / d * f; }
    wake();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { cancelAnimationFrame(raf); running = false; last = 0; } else wake();
  });

  draw();
})();
</script>
</body>
</html>