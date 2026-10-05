/* MANBAR · cinematic public pages (landing + sign-in).
   - living background: green leaves + alphabet letters drift left → right (sign-in adds math equations)
   - scroll-driven tone morph: void (black + lime) → forest → paper → void
   - intro: the logo draws itself (outline trace = load %, strokes, leaves, fill, sound waves, orbit), then lands in the hero
   - lens-focus entrance, spring-damped device tilt, live screens
   - demo layer: journey scenes and the idea-feed tile play like a screen recording (typing + a fake cursor; visual only) */
(() => {
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const body = document.body, root = document.documentElement;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const easeInOut = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  const sleep = ms => new Promise(r => setTimeout(r, ms));

  /* ---------------- tones ---------------- */
  const T = {
    void:   { bg: [5, 8, 6],       fg: [236, 243, 232], mute: [150, 168, 156], line: [236, 243, 232], la: .1,  acc: [196, 245, 58], accFg: [8, 16, 4],     pa: .04, c1: [196, 245, 58], c2: [70, 244, 151], c3: [20, 187, 122], glow: 1,   ink: 0 },
    forest: { bg: [9, 38, 27],     fg: [234, 246, 236], mute: [163, 199, 177], line: [234, 246, 236], la: .12, acc: [196, 245, 58], accFg: [8, 16, 4],     pa: .05, c1: [70, 244, 151], c2: [196, 245, 58], c3: [20, 187, 122], glow: .8,  ink: 0 },
    paper:  { bg: [242, 246, 239], fg: [12, 33, 23],    mute: [78, 102, 88],   line: [12, 33, 23],    la: .12, acc: [11, 51, 33],   accFg: [214, 250, 120], pa: .75, c1: [20, 187, 122], c2: [7, 123, 105],  c3: [120, 190, 40], glow: .6,  ink: 1 },
  };
  const mixArr = (a, b, t) => a.map((v, i) => lerp(v, b[i], t));
  const mixTone = (a, b, t) => {
    const o = {};
    for (const k in a) o[k] = Array.isArray(a[k]) ? mixArr(a[k], b[k], t) : lerp(a[k], b[k], t);
    return o;
  };
  // Each section owns its tone (CSS [data-tone] tokens), so text always matches its own ground.
  // The canvas paints the ground per section with a feathered seam just above each boundary,
  // and the leaves/letters recolour by height, so the world visibly morphs as you scroll.
  const sections = $$('[data-tone]');
  let tops = [];
  const readTops = () => { tops = sections.map(s => s.getBoundingClientRect().top); };
  function toneAtY(y, feather = true) {
    if (!sections.length) return T.void;
    let i = 0;
    for (let j = 0; j < tops.length; j++) if (tops[j] <= y) i = j;
    const cur = T[sections[i].dataset.tone] || T.void;
    if (!feather || i + 1 >= tops.length) return cur;
    const F = innerHeight * .22, b = tops[i + 1];
    if (y < b - F) return cur;
    return mixTone(cur, T[sections[i + 1].dataset.tone] || T.void, easeInOut(clamp((y - (b - F)) / F, 0, 1)));
  }
  readTops();
  let tone = toneAtY(34, false), lastVars = '';
  const trip = a => a.map(Math.round).join(' ');
  const field = { dirty: true };
  function applyTone() {
    readTops();
    tone = toneAtY(34, false);   // the nav takes the tone of the section underneath it
    field.dirty = true;
    const v = `${trip(tone.bg)}|${trip(tone.fg)}|${trip(tone.mute)}|${trip(tone.acc)}|${tone.la}|${tone.pa}`;
    if (v === lastVars) return;
    lastVars = v;
    const s = root.style;
    s.setProperty('--bg', trip(tone.bg)); s.setProperty('--fg', trip(tone.fg)); s.setProperty('--mute', trip(tone.mute));
    s.setProperty('--line', trip(tone.line)); s.setProperty('--line-a', String(tone.la));
    s.setProperty('--acc', trip(tone.acc)); s.setProperty('--acc-fg', trip(tone.accFg)); s.setProperty('--panel-a', String(tone.pa));
    root.style.colorScheme = tone.ink > .5 ? 'light' : 'dark';
  }

  /* ---------------- living background: leaves, letters (+ equations on sign-in) ---------------- */
  (function initField() {
    const cv = $('#cField'); if (!cv) return;
    const ctx = cv.getContext('2d'); if (!ctx) return;
    const withMath = body.classList.contains('cine-login');
    const LATIN = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
    const AR = ['أ', 'ب', 'ت', 'ث', 'ج', 'ح', 'د', 'ر', 'س', 'ش', 'ص', 'ع', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'هـ', 'و', 'ي'];
    const EQS = ['E = mc²', 'a² + b² = c²', '∫ f(x) dx', 'Σ k = n(n+1)/2', 'sin²θ + cos²θ = 1', 'f(x) = ax + b', '√2 ≈ 1.414', 'π ≈ 3.1416', 'v = Δx / Δt', 'x = (−b ± √Δ) / 2a', 'log₂ 1024 = 10', 'e^(iπ) + 1 = 0', 'F = ma', 'dy/dx = 2x', 'H₂O', 'A ∩ B = ∅'];
    const LEAF = new Path2D('M0 -1C.66 -.56 .66 .44 0 1C-.66 .44 -.66 -.56 0 -1Z');
    const RIB = new Path2D('M0 -.8L0 .95M0 -.15L.3 -.42M0 .25L-.3 -.02M0 .55L.26 .32');
    let W = 0, H = 0, dpr = 1, parts = [];
    const rnd = (a, b) => a + Math.random() * (b - a);
    function make(p = {}, anywhere = false) {
      const r = Math.random();
      p.kind = withMath && r < .2 ? 'eq' : r < .62 ? 'leaf' : 'letter';
      p.z = rnd(.25, 1);
      p.x = anywhere ? rnd(-60, W + 60) : rnd(-160, -40);
      p.y = rnd(-30, H + 30);
      p.vx = (p.kind === 'eq' ? 34 : 16) + p.z * (p.kind === 'eq' ? 46 : 30);
      p.ph = rnd(0, Math.PI * 2); p.fq = rnd(.4, 1.1); p.amp = rnd(6, 22) * p.z;
      p.rot = rnd(-Math.PI, Math.PI); p.vr = rnd(-.9, .9) * (p.kind === 'leaf' ? 1 : .25);
      p.size = p.kind === 'leaf' ? 6 + p.z * 15 : p.kind === 'letter' ? 13 + p.z * 30 : 12 + p.z * 9;
      p.hue = Math.random();
      p.ar = p.kind === 'letter' && Math.random() < .4;   // ~40% of letters are Arabic (منبر is bilingual)
      p.ch = p.kind === 'letter' ? (p.ar ? AR : LATIN)[Math.floor(Math.random() * (p.ar ? AR : LATIN).length)] : p.kind === 'eq' ? EQS[Math.floor(Math.random() * EQS.length)] : '';
      p.ox = 0; p.oy = 0;
      return p;
    }
    function resize() {
      dpr = Math.min(devicePixelRatio || 1, 1.5);
      W = innerWidth; H = innerHeight;
      cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const want = Math.round(clamp(W * H / (W < 700 ? 14000 : 15500), 26, 96)) + (withMath ? 10 : 0);
      while (parts.length < want) parts.push(make({}, true));
      parts.length = want;
      parts.sort((a, b) => a.z - b.z);
      field.dirty = true;
    }
    resize(); addEventListener('resize', resize);
    const mouse = { x: -999, y: -999 };
    addEventListener('pointermove', e => { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
    addEventListener('pointerleave', () => { mouse.x = mouse.y = -999; });

    const col = (p, tt) => { const a = tt.c1, b = tt.c2, c = tt.c3, h = p.hue; return h < .5 ? mixArr(a, b, h * 2) : mixArr(b, c, (h - .5) * 2); };
    const rgba = (c, a) => `rgba(${c[0] | 0},${c[1] | 0},${c[2] | 0},${a.toFixed(3)})`;
    // reading zones: background art fades to 15% where it crosses copy, so text stays legible
    const CALM = $$(withMath ? '.c-auth-hero h1, .c-auth-hero p, .c-auth-mods, .c-brand' : '.c-hero-copy, .c-head, .c-step h3, .c-step p, .c-checks, .c-foot, .c-close-in, .c-legend');
    let calm = [];
    const readCalm = () => { calm = []; for (const el of CALM) { const r = el.getBoundingClientRect(); if (r.bottom > -20 && r.top < H + 20 && r.width) calm.push([r.left - 12, r.top - 12, r.right + 12, r.bottom + 12]); } };
    const keepOut = (x, y) => { for (const r of calm) if (x > r[0] && x < r[2] && y > r[1] && y < r[3]) return .15; return 1; };
    let last = performance.now(), sy = scrollY;
    function draw(now) {
      const dt = Math.min(.05, (now - last) / 1000); last = now;
      sy = lerp(sy, scrollY, .12);
      readTops(); readCalm();
      // ground: each section's colour, feathered into the next just above the boundary
      const gr = ctx.createLinearGradient(0, 0, 0, H);
      for (let k = 0; k <= 32; k++) gr.addColorStop(k / 32, rgba(toneAtY(H * k / 32).bg, 1));
      ctx.fillStyle = gr; ctx.fillRect(0, 0, W, H);
      // soft canopy light behind the hero devices / paper sections
      const mid = toneAtY(H * .4);
      const g = ctx.createRadialGradient(W * .72, H * .4, 0, W * .72, H * .4, Math.max(W, H) * .6);
      g.addColorStop(0, rgba(mid.c3, .16 * (1 - mid.ink) + .08 * mid.ink)); g.addColorStop(1, rgba(mid.c3, 0));
      ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      for (const p of parts) {
        if (!reduce) {
          p.x += p.vx * dt;
          p.rot += p.vr * dt;
          if (p.x > W + 180) make(p);
        }
        const t = now / 1000;
        let y = p.y + Math.sin(t * p.fq + p.ph) * p.amp - sy * .18 * p.z;
        y = ((y % (H + 80)) + H + 80) % (H + 80) - 40;
        // pointer pushes nearby pieces aside (eased)
        const dx = p.x - mouse.x, dy = y - mouse.y, d2 = dx * dx + dy * dy;
        if (d2 < 140 * 140) { const f = (1 - Math.sqrt(d2) / 140) * 26 * p.z; p.ox = lerp(p.ox, dx / (Math.sqrt(d2) + 1) * f, .15); p.oy = lerp(p.oy, dy / (Math.sqrt(d2) + 1) * f, .15); }
        else { p.ox = lerp(p.ox, 0, .06); p.oy = lerp(p.oy, 0, .06); }
        const x = p.x + p.ox, yy = y + p.oy;
        const tt = toneAtY(yy), ink = tt.ink, glow = tt.glow;
        const c = col(p, tt);
        const letterA = ink > .5 ? (p.ar ? .2 + .3 * p.z : .1 + .24 * p.z) : .07 + .22 * p.z;   // paper: letters (Arabic most of all) stay visible
        const a = (p.kind === 'leaf' ? .16 + .5 * p.z : p.kind === 'letter' ? letterA : .12 + .26 * p.z) * glow * (ink > .5 ? .9 : 1)
          * keepOut(x, yy) * (p.kind === 'eq' ? keepOut(x - 90, yy) : 1);   // fade art (and equation trails) under text
        if (p.kind === 'leaf') {
          ctx.save(); ctx.translate(x, yy); ctx.rotate(p.rot + Math.sin(t * 1.3 + p.ph) * .35);
          ctx.scale(p.size * .62, p.size);
          ctx.fillStyle = rgba(c, a); ctx.fill(LEAF);
          ctx.lineWidth = .07; ctx.strokeStyle = rgba(ink > .5 ? [255, 255, 255] : [8, 30, 16], a * .55); ctx.stroke(RIB);
          ctx.restore();
        } else if (p.kind === 'letter') {
          ctx.save(); ctx.translate(x, yy); ctx.rotate(p.rot * .3);
          ctx.font = `800 ${p.size | 0}px Archivo, Cairo, system-ui, sans-serif`;
          ctx.fillStyle = rgba(c, a); ctx.fillText(p.ch, 0, 0);
          ctx.restore();
        } else {
          ctx.font = `500 ${p.size | 0}px "Geist Mono", ui-monospace, monospace`;
          const w = ctx.measureText(p.ch).width, len = 90 + p.z * 160;
          const lg = ctx.createLinearGradient(x - w / 2 - len, 0, x - w / 2 - 8, 0);
          lg.addColorStop(0, rgba(c, 0)); lg.addColorStop(1, rgba(c, a * .8));
          ctx.strokeStyle = lg; ctx.lineWidth = 1 + p.z; ctx.beginPath(); ctx.moveTo(x - w / 2 - len, yy); ctx.lineTo(x - w / 2 - 8, yy); ctx.stroke();
          ctx.fillStyle = rgba(c, Math.min(1, a * 1.3)); ctx.fillText(p.ch, x, yy);
        }
      }
      // keep the hero copy legible on the dark tones
      const hTop = sections.length ? Math.max(0, tops[0]) : 0, hBot = sections.length > 1 ? Math.min(H, tops[1] - innerHeight * .22) : H;
      if (hBot > hTop) {
        const v = ctx.createLinearGradient(0, 0, W * .55, 0);
        v.addColorStop(0, rgba(T.void.bg, .55)); v.addColorStop(1, rgba(T.void.bg, 0));
        ctx.fillStyle = v; ctx.fillRect(0, hTop, W * .55, hBot - hTop);
      }
    }
    function loop(now) {
      if (!document.hidden && (!reduce || field.dirty)) { draw(now); field.dirty = false; }
      requestAnimationFrame(loop);
    }
    if (document.fonts) document.fonts.ready.then(() => { field.dirty = true; });
    requestAnimationFrame(loop);
  })();

  /* ---------------- scroll: tone, nav ---------------- */
  const nav = $('#cNav');
  let scrollQueued = false;
  function onScroll() {
    if (scrollQueued) return; scrollQueued = true;
    requestAnimationFrame(() => { scrollQueued = false; applyTone(); if (nav) nav.classList.toggle('is-solid', scrollY > 24); });
  }
  addEventListener('scroll', onScroll, { passive: true }); addEventListener('resize', onScroll);
  applyTone(); if (nav) nav.classList.toggle('is-solid', scrollY > 24);

  /* ---------------- entrance + intro ---------------- */
  let entered = false;
  function enter() {
    if (entered) return; entered = true;
    body.classList.add('is-in');
    setTimeout(countUp, 500);
  }
  function countUp() {
    $$('[data-count]').forEach(el => {
      const end = +el.dataset.count; if (!end || reduce) return;
      const t0 = performance.now(), dur = 1400;
      const step = t => { const k = Math.min(1, (t - t0) / dur); el.textContent = Math.round(end * (1 - Math.pow(1 - k, 3))).toLocaleString(); if (k < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    });
  }

  const intro = $('#cIntro');
  if (!intro) requestAnimationFrame(() => requestAnimationFrame(enter));
  else if (reduce) { intro.remove(); enter(); }
  else runIntro();

  function runIntro() {
    body.classList.add('is-intro'); root.style.overflow = 'hidden';
    const mark = $('#cMark'), trace = $('.lm-trace', intro), num = $('#cRingNum'), state = $('#cIntroState'), tile = $('.lm-fill rect', intro);
    const words = ['Loading campus', 'Drawing the stage', 'Growing ideas', 'Ready'];
    let ready = false, shown = 0, counting = true, ended = false;
    const loaded = new Promise(r => document.readyState === 'complete' ? r() : addEventListener('load', r, { once: true }));
    Promise.all([loaded, document.fonts ? document.fonts.ready : null]).then(() => { ready = true; });
    setTimeout(() => { ready = true; }, 3000);
    let t0 = null;   // starts on the first painted frame, so a background tab still gets the full count
    function tick(now) {
      if (!counting) return;
      if (t0 === null) t0 = now;
      const k = Math.min((now - t0) / 1150, 1);
      shown = Math.max(shown, Math.min(100 * (1 - Math.pow(1 - k, 2.4)), ready ? 100 : 92));
      trace.style.strokeDashoffset = 100 - shown;
      num.textContent = String(Math.round(shown)).padStart(3, '0');
      state.textContent = words[Math.min(3, Math.floor(shown / 25))];
      if (shown >= 100) { counting = false; play(); } else requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);

    const at = (ms, fn) => setTimeout(() => { if (!ended) fn(); }, ms);
    function play() {
      intro.classList.add('s-draw');
      at(560, () => intro.classList.add('s-leaf'));
      at(800, () => intro.classList.add('s-fill'));
      at(1020, () => intro.classList.add('s-sound'));
      at(1900, fly);
    }
    function fly() {
      const target = $('#cHeroLogo');
      const a = tile.getBoundingClientRect(), b = target.getBoundingClientRect();
      const dx = (b.left + b.width / 2) - (a.left + a.width / 2), dy = (b.top + b.height / 2) - (a.top + a.height / 2), s = b.width / a.width;
      intro.classList.add('is-fly');
      setTimeout(enter, 120);
      const anim = mark.animate([{ transform: 'none' }, { transform: `translate(${dx}px, ${dy}px) scale(${s})` }], { duration: 820, easing: 'cubic-bezier(.77,0,.175,1)', fill: 'forwards' });
      anim.onfinish = done;
      setTimeout(done, 1200);
    }
    function done() {
      if (ended) return; ended = true;
      body.classList.remove('is-intro'); root.style.overflow = '';
      intro.remove(); off();
    }
    function skip() {
      if (ended) return; counting = false;
      intro.style.transition = 'opacity 240ms ease'; intro.style.opacity = '0';
      enter(); setTimeout(done, 250);
    }
    const evs = ['pointerdown', 'keydown', 'wheel', 'touchstart'];
    const off = () => evs.forEach(ev => removeEventListener(ev, skip));
    evs.forEach(ev => addEventListener(ev, skip, { passive: true }));
  }

  /* ---------------- device rig: spring tilt + glare ---------------- */
  const rig = $('#cRig');
  if (rig) {
    const phone = $('#cPhone'), gl1 = $('#cGlareL'), gl2 = $('#cGlareP');
    const base = { ry: -9, rx: 5 };
    const pose = (x, y) => {
      rig.style.transform = `rotateY(${base.ry + x * 8}deg) rotateX(${base.rx - y * 6}deg)`;
      phone.style.transform = `translateZ(90px) translate3d(${x * -16}px, ${y * -10}px, 0) rotateY(${x * 4}deg)`;
      gl1.style.transform = `translate3d(${28 - x * 26}%, ${-22 - y * 18}%, 0)`;
      gl2.style.transform = `translate3d(${20 - x * 34}%, ${-30 - y * 22}%, 0)`;
    };
    pose(0, 0);
    if (finePointer && !reduce) {
      let tx = 0, ty = 0, x = 0, y = 0, vx = 0, vy = 0, raf = 0, visible = true;
      new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(rig);
      const step = () => {
        vx = vx * .8 + (tx - x) * .045; vy = vy * .8 + (ty - y) * .045;   // spring: stiffness .045, damping .8
        x += vx; y += vy; pose(x, y);
        raf = (Math.abs(vx) + Math.abs(vy) + Math.abs(tx - x) + Math.abs(ty - y) > .0004) ? requestAnimationFrame(step) : 0;
      };
      addEventListener('pointermove', e => {
        if (!visible) return;
        tx = (e.clientX / innerWidth - .5) * 2; ty = (e.clientY / innerHeight - .5) * 2;
        if (!raf) raf = requestAnimationFrame(step);
      }, { passive: true });
    }
  }

  /* ---------------- live screen content (hero devices) ---------------- */
  const hero = $('.c-hero');
  let heroVisible = true;
  if (hero) new IntersectionObserver(([e]) => { heroVisible = e.isIntersecting; }).observe(hero);
  const live = $('#cLive');
  if (live && !reduce) {
    const items = JSON.parse(live.dataset.items || '[]');
    let i = 3;
    setInterval(() => {
      if (!heroVisible || document.hidden || !items.length) return;
      const [ini, h, who, what] = items[i++ % items.length];
      const li = document.createElement('li');
      li.className = 'enter';
      li.innerHTML = `<span class="c-av xs" style="--h:${h}">${ini.slice(0, 2)}</span><p><b>${who}</b> ${what}</p><small>now</small>`;
      live.prepend(li);
      li.getBoundingClientRect();
      li.classList.remove('enter');
      const extra = live.children[4];
      if (extra) extra.remove();
    }, 2600);
  }

  /* ---------------- demo layer helpers (cursor, typing) ---------------- */
  function demoKit(box) {
    const cursor = $('[data-js="cursor"]', box);
    let token = 0;
    const kit = {
      get token() { return token; },
      cancel() { token++; },
      alive: t => t === token,
      async wait(ms, t) { await sleep(ms); return t === token; },
      async moveTo(el, t, fx = .5, fy = .55) {
        if (!cursor || !el) return t === token;
        const b = box.getBoundingClientRect(), r = el.getBoundingClientRect();
        cursor.classList.add('on');
        cursor.style.transform = `translate3d(${r.left - b.left + r.width * fx}px, ${r.top - b.top + r.height * fy}px, 0)`;
        return kit.wait(780, t);
      },
      async click(el, t) {
        if (!cursor || t !== token) return false;
        cursor.classList.remove('click'); cursor.getBoundingClientRect(); cursor.classList.add('click');
        if (el) { el.classList.add('is-press'); setTimeout(() => el.classList.remove('is-press'), 160); }
        return kit.wait(260, t);
      },
      park(t) { if (cursor && t === token) { cursor.style.transform = `translate3d(${box.clientWidth * .82}px, ${box.clientHeight * .9}px, 0)`; } },
      hide() { if (cursor) cursor.classList.remove('on'); },
      async type(el, text, t, speed = 26) {
        el.classList.add('is-typing');
        for (let i = 1; i <= text.length; i++) {
          if (t !== token) { el.classList.remove('is-typing'); return false; }
          el.textContent = text.slice(0, i);
          await sleep(speed + (text[i - 1] === ' ' ? 30 : Math.random() * 22));
        }
        el.classList.remove('is-typing');
        return t === token;
      },
      bump(el) { if (!el) return; el.classList.remove('c-bump'); el.getBoundingClientRect(); el.classList.add('c-bump'); },
    };
    return kit;
  }

  /* ---------------- journey: steps drive the sticky scene, each scene plays live ---------------- */
  const scene = $('#cScene'), steps = $$('.c-step'), stepN = $('#cStepN');
  if (scene && steps.length) {
    const kit = demoKit(scene);
    const P = n => $(`[data-panel="${n}"]`, scene);
    const q = (n, k) => $(`[data-js="${k}"]`, P(n)), qa = (n, k) => $$(`[data-js="${k}"]`, P(n));
    const scripts = {
      async 1(t) {
        const bodyEl = q(1, 'body'), tags = q(1, 'tags'), count = q(1, 'count'), comment = q(1, 'comment'), react = q(1, 'react');
        const text = bodyEl.dataset.full || (bodyEl.dataset.full = bodyEl.textContent);
        bodyEl.textContent = ''; tags.classList.add('c-hide-kids'); count.textContent = '19'; comment.classList.add('c-hide');
        if (!await kit.moveTo(bodyEl, t, .1, .3)) return;
        await kit.click(null, t);
        if (!await kit.type(bodyEl, text, t, 18)) return;
        [...tags.children].forEach((s, i) => { s.style.transition = `opacity 300ms ease ${i * 110}ms, transform 520ms cubic-bezier(.16,1,.3,1) ${i * 110}ms`; });
        tags.classList.remove('c-hide-kids');
        if (!await kit.moveTo(react, t)) return;
        for (const n of [20, 21, 22, 24]) { await kit.click(react, t); count.textContent = n; kit.bump(count); if (!await kit.wait(220, t)) return; }
        comment.classList.remove('c-hide');
        kit.park(t);
        await kit.wait(2400, t);
      },
      async 2(t) {
        const draft = q(2, 'draft'), route = q(2, 'route'), move = q(2, 'move'), dest = q(2, 'dest');
        const parts = [...draft.children];
        parts.forEach(s => { if (s.dataset.t !== undefined) s.textContent = ''; else { s.textContent = ''; s.classList.remove('err'); } });
        route.classList.add('c-hide');
        if (!await kit.moveTo(draft, t, .05, .4)) return;
        for (const s of parts) {
          if (!await kit.type(s, s.dataset.t !== undefined ? s.dataset.t : s.dataset.bad, t, 22)) return;
        }
        parts.filter(s => s.classList.contains('w')).forEach(s => s.classList.add('err'));
        if (!await kit.wait(700, t)) return;
        for (const s of parts.filter(x => x.classList.contains('w'))) {
          s.classList.remove('err'); s.innerHTML = `<s>${s.dataset.bad}</s> <ins>${s.dataset.good}</ins>`;
          if (!await kit.wait(380, t)) return;
        }
        route.classList.remove('c-hide');
        if (!await kit.wait(700, t)) return;
        if (!await kit.moveTo(move, t)) return;
        await kit.click(move, t);
        dest.classList.add('is-flash'); setTimeout(() => dest.classList.remove('is-flash'), 700);
        kit.park(t);
        await kit.wait(2400, t);
      },
      async 3(t) {
        const seats = q(3, 'seats'), apps = $$('.c-app', P(3)), accs = qa(3, 'acc');
        const accHTML = P(3).dataset.acc || (P(3).dataset.acc = accs[0].innerHTML);
        apps.forEach(a => a.classList.add('c-hide'));
        accs.forEach(b => { b.classList.remove('on'); b.textContent = 'Review'; });
        seats.textContent = '1';
        for (const a of apps) { a.classList.remove('c-hide'); if (!await kit.wait(260, t)) return; }
        let n = 1;
        for (const b of accs) {
          if (!await kit.moveTo(b, t)) return;
          await kit.click(b, t);
          b.classList.add('on'); b.innerHTML = accHTML; seats.textContent = String(++n); kit.bump(seats);
          if (!await kit.wait(350, t)) return;
        }
        kit.park(t);
        await kit.wait(2600, t);
      },
      async 4(t) {
        const card = q(4, 'card'), done = q(4, 'done'), doing = q(4, 'doing'), donen = q(4, 'donen'), bar = q(4, 'bar');
        card.style.transition = 'none'; card.style.transform = ''; card.classList.remove('grab');
        doing.textContent = '2'; donen.textContent = '2'; bar.style.setProperty('--w', '.62');
        card.getBoundingClientRect(); card.style.transition = '';
        if (!await kit.moveTo(card, t, .3, .5)) return;
        await kit.click(card, t);
        card.classList.add('grab');
        const c = card.getBoundingClientRect(), d = done.lastElementChild.getBoundingClientRect();
        const dx = d.left - c.left, dy = d.bottom + 8 - c.top;
        card.style.transform = `translate(${dx}px, ${dy}px) rotate(2deg)`;
        const b = scene.getBoundingClientRect();
        $('[data-js="cursor"]', scene).style.transform = `translate3d(${c.left - b.left + c.width * .3 + dx}px, ${c.top - b.top + c.height * .5 + dy}px, 0)`;
        if (!await kit.wait(950, t)) return;
        card.classList.remove('grab'); card.style.transform = `translate(${dx}px, ${dy}px)`;
        doing.textContent = '1'; donen.textContent = '3'; kit.bump(donen);
        bar.style.setProperty('--w', '.78');
        kit.park(t);
        await kit.wait(2600, t);
      },
      async 5(t) {
        const pts = q(5, 'pts'), badges = q(5, 'badges'), lvl = q(5, 'lvl');
        pts.classList.add('c-hide'); badges.classList.add('c-hide-kids'); lvl.style.setProperty('--w', '.42');
        if (!await kit.wait(400, t)) return;
        pts.classList.remove('c-hide');
        [...badges.children].forEach((s, i) => { s.style.transition = `opacity 300ms ease ${i * 160}ms, transform 560ms cubic-bezier(.16,1,.3,1) ${i * 160}ms`; });
        badges.classList.remove('c-hide-kids');
        if (!await kit.wait(500, t)) return;
        lvl.style.setProperty('--w', '.71');
        if (!await kit.moveTo($('.c-proof-item', P(5)), t, .2, .5)) return;
        kit.park(t);
        await kit.wait(2800, t);
      },
    };
    let current = 0, visible = false;
    async function run(step) {
      kit.cancel(); const t = kit.token;
      if (reduce || !visible) return;
      while (kit.alive(t)) {
        await scripts[step](t);
        if (!kit.alive(t)) return;
        await sleep(400);
      }
    }
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; if (visible) run(current || 1); else { kit.cancel(); kit.hide(); } }, { threshold: .25 }).observe(scene);
    const narrow = matchMedia('(max-width: 900px)').matches;
    const io = new IntersectionObserver(es => es.forEach(e => {
      if (!e.isIntersecting) return;
      const n = +e.target.dataset.step;
      steps.forEach(s => s.classList.toggle('is-on', s === e.target));
      scene.dataset.step = n;
      if (stepN) stepN.textContent = n;
      if (n !== current) { current = n; run(n); }
    }), { rootMargin: narrow ? '-72% 0px -24% 0px' : '-48% 0px -48% 0px' });
    steps.forEach(s => io.observe(s));
  }

  /* ---------------- idea-feed tile: plays like a screen recording ---------------- */
  const rec = $('#cRec');
  if (rec && !reduce) {
    const tileBox = rec.closest('.c-tile');
    const kit = demoKit(tileBox);
    const cursorEl = $('[data-js="cursor"]', rec); tileBox.appendChild(cursorEl);   // cursor positions against the tile
    const text = $('[data-js="text"]', rec), post = $('[data-js="post"]', rec), types = $('[data-js="types"]', rec), slots = $$('[data-js="slot"]', rec), clock = $('[data-js="clock"]', rec);
    const ph = text.textContent;
    const R = { team: ['🤝', '👍', '💚'], question: ['💡', '👍', '🤝'], event: ['🎉', '💚', '👍'], idea: ['💡', '💚', '🎉'], resource: ['💚', '💡', '👍'], achievement: ['🎉', '💚', '🤝'] };
    const LABEL = { idea: 'Idea', team: 'Team request', question: 'Question', resource: 'Resource', achievement: 'Showcase', event: 'Event' };
    const SCENES = [
      { type: 'team', title: 'Looking for a Flutter dev', text: 'Looking for a Flutter dev to build a campus events app with me' },
      { type: 'question', title: 'Good notes for Calculus II?', text: 'Does anyone have clear notes for Calculus II before the midterm?' },
      { type: 'event', title: 'Robotics club demo day', text: 'Robotics club demo day: Thursday 2 pm in the Engineering lobby' },
      { type: 'resource', title: 'Free Figma course I loved', text: 'Sharing a free Figma course that helped me design my first app' },
    ];
    const postHTML = s => `<div class="c-post-h"><span class="c-av" style="--h:152">MA</span><span><b>Mariam Al Mansoori</b><small>${LABEL[s.type]} · now</small></span></div><b class="c-post-t">${s.title}</b><p>${s.text}</p><div class="c-post-f"><span class="c-reacts" data-js="r">${R[s.type].map(e => `<i>${e}</i>`).join('')}</span><b data-js="n">0</b><span>0 comments</span></div>`;
    let started = 0, visible = false, idx = 0;
    setInterval(() => { if (!visible || !started) return; const s = Math.floor((performance.now() - started) / 1000); clock.textContent = `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`; }, 250);
    async function scene1(t) {
      const s = SCENES[idx++ % SCENES.length];
      text.textContent = ph; text.classList.remove('has'); post.classList.add('dim');
      if (!await kit.moveTo(text, t, .15, .5)) return false;
      await kit.click(null, t);
      text.classList.add('has');
      if (!await kit.type(text, s.text, t, 30)) return false;
      post.classList.remove('dim');
      const chip = $(`[data-type="${s.type}"]`, types);
      if (!await kit.moveTo(chip, t)) return false;
      await kit.click(chip, t);
      $$('span', types).forEach(c => c.classList.toggle('on', c === chip));
      if (!await kit.moveTo(post, t)) return false;
      await kit.click(post, t);
      // publish: the newest post slides into the first slot, the previous one shifts over
      slots[1].innerHTML = slots[0].innerHTML; slots[1].classList.remove('shift'); slots[1].getBoundingClientRect(); slots[1].classList.add('shift');
      slots[0].innerHTML = postHTML(s); slots[0].classList.remove('enter'); slots[0].getBoundingClientRect(); slots[0].classList.add('enter');
      text.textContent = ph; text.classList.remove('has'); post.classList.add('dim');
      if (!await kit.wait(700, t)) return false;
      const r = $('[data-js="r"]', slots[0]), n = $('[data-js="n"]', slots[0]);
      if (!await kit.moveTo(r, t)) return false;
      for (let k = 1; k <= 3; k++) { await kit.click(r, t); n.textContent = k; kit.bump(n); if (!await kit.wait(200, t)) return false; }
      kit.park(t);
      return kit.wait(1800, t);
    }
    async function loop() {
      kit.cancel(); const t = kit.token;
      started = performance.now();
      while (kit.alive(t) && visible) { if (!await scene1(t)) return; }
    }
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; if (visible) loop(); else { kit.cancel(); kit.hide(); } }, { threshold: .35 }).observe(tileBox);
  }

  /* ---------------- compare table: dots light up, MANBAR column counts to 9/9 ---------------- */
  const table = $('.c-table'), score = $('[data-js="score"]');
  if (table) {
    if (reduce) table.classList.add('is-in');
    else new IntersectionObserver(([e], o) => {
      if (!e.isIntersecting) return; o.disconnect();
      table.classList.add('is-in');
      if (score) { score.textContent = '0'; for (let i = 1; i <= 9; i++) setTimeout(() => { score.textContent = i; }, 120 + (i - 1) * 75); }
    }, { threshold: .3 }).observe(table);
  }

  /* ---------------- bento spotlight ---------------- */
  const bento = $('.c-bento');
  if (bento && finePointer) bento.addEventListener('pointermove', e => {
    const tile = e.target.closest('.c-tile'); if (!tile) return;
    const r = tile.getBoundingClientRect();
    tile.style.setProperty('--mx', `${e.clientX - r.left}px`); tile.style.setProperty('--my', `${e.clientY - r.top}px`);
  });

  /* ---------------- sign-in: typed university email + Chrome's Google bubble ---------------- */
  const mail = $('#cMail');
  if (mail) {
    const inp = $('#mailLocal'), dom = $('#mailDom'), role = $('#mailRole');
    const idle = role.innerHTML;
    const domain = () => (dom.value || dom.dataset.domain || '').toLowerCase();
    const esc = s => s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const update = () => {
      const v = inp.value.trim().toLowerCase();
      const hasAt = v.includes('@');
      mail.classList.toggle('has-at', hasAt);
      const local = hasAt ? v.split('@')[0] : v, at = hasAt ? v.split('@')[1] : domain();
      if (!v) { role.innerHTML = idle; role.dataset.kind = ''; return; }
      if (hasAt && at && !/\./.test(at)) { role.dataset.kind = ''; role.textContent = 'Keep typing your university address…'; return; }
      if (/^\d{6,12}$/.test(local)) { role.dataset.kind = 'student'; role.innerHTML = `<span class="c-role-chip on">Student</span> ${esc(local)}@${esc(at)} · name, major and year come from the roster`; }
      else if (/^\d+$/.test(local)) { role.dataset.kind = ''; role.textContent = 'Student numbers have 6 to 12 digits.'; }
      else if (/^[a-z][a-z0-9._-]+$/.test(local)) { role.dataset.kind = 'teacher'; role.innerHTML = `<span class="c-role-chip on">Teacher</span> ${esc(local)}@${esc(at)} · faculty and staff accounts`; }
      else { role.dataset.kind = ''; role.textContent = 'Use letters, numbers, dots or dashes only.'; }
    };
    inp.addEventListener('input', update);
    if (dom.tagName === 'SELECT') dom.addEventListener('change', update);
    mail.addEventListener('submit', e => {
      update();
      if (!role.dataset.kind) { e.preventDefault(); inp.focus(); role.classList.remove('c-bump'); role.getBoundingClientRect(); role.classList.add('c-bump'); }
    });
  }
  // Called by Google Identity Services when someone picks an account in Chrome's sign-in bubble.
  window.manbarOneTap = resp => {
    const meta = n => document.querySelector(`meta[name="${n}"]`).content;
    fetch(meta('base') + '/auth/google/finish', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-Token': meta('csrf'), 'X-Requested-With': 'fetch' },
      body: new URLSearchParams({ id_token: resp.credential, mode: 'onetap' }),
    }).then(r => r.json()).then(d => {
      if (d.ok) location.replace(d.redirect);
      else { location.reload(); }
    }).catch(() => location.reload());
  };

  /* ---------------- code + password screens ---------------- */
  const otp = $('[data-otp]');
  if (otp) {
    let sent = false;
    otp.addEventListener('input', () => {
      const d = otp.value.replace(/\D/g, '').slice(0, 6);
      otp.value = d.length > 3 ? `${d.slice(0, 3)} ${d.slice(3)}` : d;   // 123 456
      if (d.length === 6 && !sent) { sent = true; otp.form.requestSubmit(); }
    });
  }
  $$('[data-reveal]').forEach(b => b.addEventListener('click', () => {
    const show = b.getAttribute('aria-pressed') !== 'true';
    b.setAttribute('aria-pressed', String(show)); b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    $$('.c-pw-in', b.closest('form')).forEach(i => { i.type = show ? 'text' : 'password'; });
  }));
  $$('[data-countdown]').forEach(b => {
    let n = +b.dataset.countdown; const label = $('[data-countdown-label]', b);
    if (!n) return;
    const t = setInterval(() => { n--; label.textContent = n > 0 ? ` in ${n}s` : ''; if (n <= 0) { b.disabled = false; clearInterval(t); } }, 1000);
  });
  const strength = $('[data-strength]');
  if (strength) {
    const meter = $('.c-strength');
    strength.addEventListener('input', () => {
      const v = strength.value;
      let s = 0;
      if (v.length >= 8) s++;
      if (v.length >= 12) s++;
      if (/[a-z]/i.test(v) && /\d/.test(v)) s++;
      if (/[^a-z0-9]/i.test(v) || v.length >= 16) s++;
      meter.dataset.s = v ? Math.max(1, s) : '';
    });
  }

  /* ---------------- roster match demo ---------------- */
  const match = $('#cMatch'), type = $('#cType');
  if (match && type) {
    const id = '202310101';
    new IntersectionObserver(([e], o) => {
      if (!e.isIntersecting) return; o.disconnect();
      if (reduce) { type.textContent = id; match.classList.add('is-done'); return; }
      let n = 0;
      const t = setInterval(() => { type.textContent = id.slice(0, ++n); if (n >= id.length) { clearInterval(t); setTimeout(() => match.classList.add('is-done'), 250); } }, 95);
    }, { threshold: .6 }).observe(match);
  }
})();
