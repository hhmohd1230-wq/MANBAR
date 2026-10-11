/* MANBAR learning center: quiz builder helpers, quiz player (stepper, autosave, timer), live quiz (player + host). */
(() => {
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const meta = n => (document.querySelector(`meta[name="${n}"]`) || {}).content || '';
  const send = (url, data) => fetch(meta('base') + url, {
    method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-Token': meta('csrf'), 'X-Requested-With': 'fetch' },
    body: data instanceof FormData ? data : new URLSearchParams(data),
  }).then(r => r.json().catch(() => ({ ok: false })));
  const get = url => fetch(meta('base') + url, { headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' }, cache: 'no-store' }).then(r => r.json());
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- small helpers ---------- */
  // Radio "switch" groups show the matching [data-when] panel in the same form.
  $$('[data-switch]').forEach(group => {
    const form = group.closest('form') || document;
    const sync = () => {
      const v = ($('input:checked', group) || {}).value;
      $$('[data-when]', form).forEach(p => p.classList.toggle('on', p.dataset.when.split(' ').includes(v)));
    };
    group.addEventListener('change', sync); sync();
  });
  // Question editor: add option rows (max 6) and keep the radio values in order.
  const optWrap = $('[data-opts]');
  if (optWrap) {
    const renumber = () => $$('.qb-optrow', optWrap).forEach((row, i) => { $('input[type=radio]', row).value = i; $('.input', row).placeholder = 'Option ' + String.fromCharCode(65 + i); });
    $('[data-add-opt]')?.addEventListener('click', () => {
      if ($$('.qb-optrow', optWrap).length >= 6) return;
      const row = $('.qb-optrow', optWrap).cloneNode(true);
      $('.input', row).value = ''; $('input[type=radio]', row).checked = false;
      optWrap.appendChild(row); renumber(); $('.input', row).focus();
    });
  }
  // PDF preview toggles
  $$('[data-pdf]').forEach(btn => btn.addEventListener('click', () => {
    const box = document.getElementById(btn.dataset.pdf);
    if (!box.firstElementChild) box.innerHTML = `<iframe class="lc-pdf" src="${esc(btn.dataset.src)}" title="Document preview"></iframe>`;
    box.hidden = !box.hidden;
    btn.textContent = box.hidden ? 'Preview' : 'Hide preview';
  }));

  /* ---------- quiz player ---------- */
  const qt = $('#qt');
  if (qt) {
    const aid = qt.dataset.attempt, form = $('#qtForm', qt), cards = $$('.qt-card', qt), dots = $$('.qt-dots button', qt);
    const bar = $('.qt-bar i', qt), counter = $('[data-count]', qt), saveEl = $('.qt-save', qt), timerEl = $('.qt-timer', qt);
    let cur = 0, submitting = false;
    const answered = c => c.dataset.type === 'essay' ? $('textarea', c).value.trim() !== '' : !!$('input:checked', c);
    const refresh = () => {
      const n = cards.filter(answered).length;
      bar.style.width = (n / cards.length * 100) + '%';
      counter.textContent = `${n}/${cards.length} answered`;
      dots.forEach((d, i) => { d.classList.toggle('done', answered(cards[i])); d.classList.toggle('on', i === cur); });
    };
    const show = i => {
      cur = Math.max(0, Math.min(cards.length - 1, i));
      cards.forEach((c, k) => c.classList.toggle('on', k === cur));
      $('[data-prev]', qt).disabled = cur === 0;
      $('[data-next]', qt).hidden = cur === cards.length - 1;
      $('[data-finish]', qt).hidden = cur !== cards.length - 1;
      refresh();
      if (!reduce) qt.scrollIntoView({ block: 'start', behavior: 'smooth' });
    };
    const status = t => { saveEl.textContent = t; };
    const save = (qid, data) => {
      status('Saving…');
      send(`/quiz/attempt/${aid}/save`, { question: qid, ...data }).then(r => {
        if (r.ok) status('Saved ✓');
        else if (r.expired) finish(true);
        else status(r.error || 'Not saved');
      }).catch(() => status('Offline: will save on submit'));
    };
    cards.forEach(c => {
      const qid = c.dataset.q;
      $$('input[type=radio]', c).forEach(r => r.addEventListener('change', () => { save(qid, { choice: r.value }); refresh(); }));
      const ta = $('textarea', c);
      if (ta) { let t; ta.addEventListener('input', () => { refresh(); clearTimeout(t); status('Typing…'); t = setTimeout(() => save(qid, { essay: ta.value }), 1100); }); }
    });
    $('[data-prev]', qt).addEventListener('click', () => show(cur - 1));
    $('[data-next]', qt).addEventListener('click', () => show(cur + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => show(i)));
    const finish = auto => {
      if (submitting) return;
      if (!auto) {
        const left = cards.filter(c => !answered(c)).length;
        if (left && !confirm(`${left} question${left === 1 ? ' is' : 's are'} still unanswered. Submit anyway?`)) return;
      }
      submitting = true; form.submit();
    };
    $('[data-finish]', qt).addEventListener('click', () => finish(false));
    document.addEventListener('keydown', e => {
      if (e.target.closest('textarea, input[type=text]')) return;
      const c = cards[cur];
      if (/^[1-6]$/.test(e.key)) { const r = $$('input[type=radio]', c)[+e.key - 1]; if (r) { r.checked = true; r.dispatchEvent(new Event('change')); } }
      if (e.key === 'ArrowRight' && cur < cards.length - 1) show(cur + 1);
      if (e.key === 'ArrowLeft' && cur > 0) show(cur - 1);
    });
    if (timerEl) {
      let left = +qt.dataset.remaining;
      const end = Date.now() + left * 1000;
      const tick = () => {
        left = Math.max(0, Math.round((end - Date.now()) / 1000));
        timerEl.textContent = `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}`;
        timerEl.classList.toggle('low', left <= 60);
        if (left <= 0) { status('Time is up: submitting…'); finish(true); return; }
        setTimeout(tick, 500);
      };
      tick();
    }
    window.addEventListener('beforeunload', e => { if (!submitting) { e.preventDefault(); e.returnValue = ''; } });
    show(0);
  }

  /* ---------- live quiz (normal quiz layout: pick an answer, submit, review at the end) ---------- */
  const board = (rows, max = 10) => rows.slice(0, max).map((r, i) => `<div class="lv-row ${r.me ? 'me' : ''}" style="animation-delay:${i * 60}ms"><span class="pos">${i + 1}</span><span class="avatar" style="width:34px;height:34px;font-size:13px;background:var(--g600)">${esc(r.initials)}</span><b>${esc(r.name)}</b><span class="pts">${r.score}</span></div>`).join('') || '<p class="muted" style="text-align:center">No scores yet.</p>';
  const podium = rows => { const p = [rows[1], rows[0], rows[2]]; return `<div class="lv-podium">${p.map((r, i) => r ? `<div><span class="avatar" style="width:52px;height:52px;font-size:18px;background:var(--g600)">${esc(r.initials)}</span><b>${esc(r.name)}</b><span class="muted small">${r.score} pts</span><i>${[2, 1, 3][i]}</i></div>` : '<div></div>').join('')}</div>`; };
  const letter = i => String.fromCharCode(65 + i);
  // Countdown that keeps moving between polls (server sends remaining_ms; we tick locally).
  const timer = root => {
    let end = 0, total = 1, raf = 0;
    const step = () => {
      const left = Math.max(0, end - Date.now());
      const i = $('.lv-timerbar i', root), n = $('[data-left]', root);
      if (i) i.style.transform = `scaleX(${left / total})`;
      if (n) n.textContent = Math.ceil(left / 1000);
      if (left > 0) raf = requestAnimationFrame(step);
    };
    return { set(ms, seconds) { cancelAnimationFrame(raf); total = seconds * 1000; end = Date.now() + ms; step(); }, stop() { cancelAnimationFrame(raf); } };
  };
  const poll = (fn, ms) => { let stop = false; const go = () => { if (stop) return; fn().finally(() => setTimeout(go, document.hidden ? ms * 3 : ms)); }; go(); return () => { stop = true; }; };
  const head = s => `<div class="lv-meta"><span>Question ${s.index + 1} of ${s.total}</span><span><b data-left></b> s left</span></div><div class="lv-timerbar"><i></i></div>`;

  const play = $('#lvPlay');
  if (play) {
    const code = play.dataset.code, stage = $('.lv-stage', play), t = timer(play);
    let key = '', sending = false;
    const chips = s => {
      $('[data-score]', play).textContent = s.me ? s.me.score : '–';
      $('[data-rank]', play).textContent = s.me && s.me.rank ? '#' + s.me.rank : '–';
      $('.lv-chips', play).hidden = !s.me;
    };
    const render = s => {
      chips(s);
      const k = [s.state, s.index, s.my_answer ? 1 : 0, s.state === 'lobby' ? s.players : ''].join(':');
      if (s.state === 'question') t.set(s.remaining_ms, s.seconds);
      if (k === key) return;
      key = k;
      if (s.state === 'lobby') {
        stage.innerHTML = `<div class="lv-wait"><span class="lc-live-dot"></span><h2>You’re in!</h2><p>The quiz starts when the host presses Start. ${s.players} player${s.players === 1 ? '' : 's'} joined.</p><p class="small muted">${s.total} questions · ${s.seconds} seconds each · keep this page open</p></div>`;
      } else if (s.state === 'question' && !s.my_answer) {
        stage.innerHTML = head(s) + `<form class="lv-form"><p class="qt-prompt">${esc(s.question.prompt)}</p><div class="qt-opts" role="radiogroup">${s.question.options.map((o, i) => `<label class="qt-opt"><input type="radio" name="live" value="${i}"><span class="qt-key">${letter(i)}</span><span>${esc(o)}</span></label>`).join('')}</div>
          <div class="qt-nav"><span class="small muted">Pick one, then submit. Faster correct answers score more.</span><button class="btn btn-primary btn-lg" type="submit" disabled>Submit answer</button></div></form>`;
        t.set(s.remaining_ms, s.seconds);
        const form = $('.lv-form', stage), btn = $('button', form);
        form.addEventListener('change', () => { btn.disabled = false; });
        form.addEventListener('submit', e => {
          e.preventDefault();
          const pick = $('input:checked', form);
          if (!pick || sending) return;
          sending = true; btn.disabled = true; btn.textContent = 'Sending…';
          send(`/live/${code}/answer`, { question: s.question.id, choice: pick.value }).then(r => {
            sending = false;
            if (!r.ok) { btn.textContent = r.error || 'Not saved'; return; }
            key = ''; refresh();
          }).catch(() => { sending = false; btn.disabled = false; btn.textContent = 'Try again'; });
        });
      } else if (s.state === 'question') {
        const a = s.my_answer;
        stage.innerHTML = head(s) + `<div class="lv-wait"><h2>Answer saved ✓</h2><p>You chose <b>${letter(a.choice)}. ${esc(s.question.options[a.choice])}</b></p><p class="small muted">Waiting for the time to run out or for everyone to answer.</p></div>`;
        t.set(s.remaining_ms, s.seconds);
      } else if (s.state === 'reveal') {
        t.stop();
        const a = s.my_answer;
        if (s.reveal_each && s.correct !== undefined) {
          const right = s.question.options[s.correct];
          stage.innerHTML = `<div class="lv-feedback ${a && a.correct ? 'ok' : 'no'}">${!a ? 'No answer this time' : a.correct ? 'Correct! +' + a.points : 'Not this time'}<small>Right answer: ${letter(s.correct)}. ${esc(right)}</small></div>`
            + (s.explanation ? `<p style="text-align:center;margin-top:14px">${esc(s.explanation)}</p>` : '') + `<h3 style="margin-top:20px">Top players</h3><div class="lv-board">${board(s.leaderboard, 5)}</div>`;
        } else {
          stage.innerHTML = `<div class="lv-wait"><h2>Time’s up</h2><p>${a ? 'Your answer is saved.' : 'No answer saved for this question.'} The next question is coming.</p><p class="small muted">You’ll see the right answers and your result at the end.</p></div>`;
        }
      } else if (s.state === 'ended') {
        t.stop();
        const r = s.review || [], right = r.filter(q => q.mine === q.correct).length;
        stage.innerHTML = `<div class="lv-result"><div class="qr-ring" style="--p:${r.length ? Math.round(right / r.length * 100) : 0}"><div><span><b>${right}/${r.length}</b><br><small>correct</small></span></div></div>
            <div><h2 style="margin:0 0 4px">Quiz finished</h2><p style="margin:0">${s.me ? `You scored <b>${s.me.score}</b> points${s.me.rank ? ` and finished <b>#${s.me.rank}</b> of ${s.players}` : ''}.` : ''}</p></div></div>
          ${s.leaderboard.length ? podium(s.leaderboard) : ''}
          <h3 style="margin:18px 0 10px">Your answers</h3>
          ${r.map((q, i) => `<div class="qr-item ${q.mine === q.correct ? 'ok' : 'bad'}"><div class="qt-k"><span>Question ${i + 1}</span><span class="muted">${q.points} pts</span></div><p style="font-weight:600;margin:0 0 8px">${esc(q.prompt)}</p>
            ${q.mine !== null && q.mine !== q.correct ? `<div class="qr-opt wrong">✘ ${letter(q.mine)}. ${esc(q.options[q.mine])} <span class="small muted">(your answer)</span></div>` : ''}
            <div class="qr-opt right">✔ ${letter(q.correct)}. ${esc(q.options[q.correct])}${q.mine === q.correct ? ' <span class="small muted">(your answer)</span>' : ''}</div>
            ${q.mine === null ? '<p class="small muted" style="margin:6px 0 0">No answer given.</p>' : ''}${q.explanation ? `<p class="qr-feedback">${esc(q.explanation)}</p>` : ''}</div>`).join('')}`;
        stopPoll();
      }
    };
    // number keys / Enter on the open question
    document.addEventListener('keydown', e => {
      const form = $('.lv-form', stage); if (!form) return;
      const i = '123456'.indexOf(e.key), opts = $$('input[type=radio]', form);
      if (i >= 0 && opts[i]) { opts[i].checked = true; form.dispatchEvent(new Event('change')); }
      if (e.key === 'Enter' && $('input:checked', form)) { e.preventDefault(); form.requestSubmit(); }
    });
    const refresh = () => get(`/live/${code}/state`).then(s => { if (s && s.ok) render(s); }).catch(() => {});
    const stopPoll = poll(refresh, 1000);
  }

  const host = $('#lvHost');
  if (host) {
    const code = host.dataset.code, stage = $('.lv-stage', host), t = timer(host), ctrl = $('.lv-ctrl', host);
    let key = '';
    const act = a => { ctrl.querySelectorAll('button').forEach(b => b.disabled = true); return send(`/live/${code}/control`, { action: a }).then(s => { key = ''; if (s && s.ok) render(s); }); };
    const btn = (a, label, cls = 'btn btn-primary btn-lg') => `<button class="${cls}" type="button" data-act="${a}">${label}</button>`;
    // read-only answer list for the host: answer counts + the right answer marked
    const rows = (s, showRight) => {
      const total = Math.max(1, s.answered || 0);
      return `<div class="lv-hlist">${s.question.options.map((o, i) => { const n = (s.counts || [])[i] || 0, ok = showRight && i === s.correct;
        return `<div class="lv-hrow ${ok ? 'right' : ''}"><span class="qt-key">${letter(i)}</span><span class="grow">${esc(o)}${ok ? ' <b class="lv-ok">✓ right answer</b>' : ''}</span><span class="lv-hbar"><i style="width:${Math.round(n / total * 100)}%"></i></span><b class="lv-hn">${n}</b></div>`; }).join('')}</div>`;
    };
    const render = s => {
      $('[data-players]', host).textContent = s.players;
      const k = [s.state, s.index, s.state === 'lobby' ? (s.names || []).join() : '', s.state === 'question' ? s.answered : ''].join(':');
      if (s.state === 'question') t.set(s.remaining_ms, s.seconds);
      if (k === key) return;
      key = k;
      const last = s.index + 1 >= s.total;
      if (s.state === 'lobby') {
        stage.innerHTML = `<div class="lv-wait"><h2>Waiting for players</h2><p>Students in this course see the quiz on their <b>Live quiz</b> page and in their notifications, and join with one tap.</p>
          <div class="lv-players" style="margin-top:14px">${(s.names || []).map((n, i) => `<span style="animation-delay:${i * 40}ms">${esc(n)}</span>`).join('') || '<span class="muted" style="background:none">Nobody yet: use “Open student view” to try it yourself.</span>'}</div></div>`;
        ctrl.innerHTML = btn('next', `▶ Start quiz (${s.total} questions)`) + btn('end', 'Cancel', 'btn btn-ghost');
      } else if (s.state === 'question') {
        stage.innerHTML = head(s).replace('s left</span>', `s left · ${s.answered}/${s.players} answered</span>`) + `<p class="qt-prompt">${esc(s.question.prompt)}</p>${rows(s, true)}<p class="small muted" style="margin-top:10px">You’re hosting: students answer on their own screens. Answer counts update live.</p>`;
        t.set(s.remaining_ms, s.seconds);
        ctrl.innerHTML = btn('reveal', 'Close this question');
      } else if (s.state === 'reveal') {
        t.stop();
        stage.innerHTML = `<div class="lv-meta"><span>Question ${s.index + 1} of ${s.total}</span><span>${s.answered}/${s.players} answered</span></div><p class="qt-prompt">${esc(s.question.prompt)}</p>${rows(s, true)}
          ${s.explanation ? `<p class="qr-feedback">${esc(s.explanation)}</p>` : ''}<p class="small muted">${s.reveal_each ? 'Students now see the right answer and their points.' : 'Students only see “Time’s up”: they get the right answers at the end (change this in the quiz settings).'}</p>
          <h3 style="margin-top:18px">Leaderboard</h3><div class="lv-board">${board(s.leaderboard, 5)}</div>`;
        ctrl.innerHTML = btn('next', last ? '🏆 Finish & show results' : 'Next question ▶');
      } else {
        t.stop();
        stage.innerHTML = `<h2 style="text-align:center">Final results</h2>${podium(s.leaderboard)}<div class="lv-board">${board(s.leaderboard, 10)}</div>`;
        ctrl.innerHTML = `<a class="btn btn-primary btn-lg" href="${esc($('[data-back]', host).href)}">Back to course dashboard</a>`;
        stopPoll();
      }
      $$('[data-act]', ctrl).forEach(b => b.addEventListener('click', () => act(b.dataset.act)));
    };
    const stopPoll = poll(() => get(`/live/${code}/host/state`).then(s => { if (s && s.ok) render(s); }).catch(() => {}), 1000);
  }

  /* ---------- course content: the host drags lessons and quizzes into any order (mouse, touch or arrow keys) ---------- */
  const outline = $('[data-reorder]');
  if (outline) {
    const status = $('[data-order-status]'), hint = status ? status.innerHTML : '';
    const rows = () => $$(':scope > li', outline);
    const keys = () => rows().map(li => li.dataset.key).join(',');
    let saved = keys(), saveTimer, sayTimer;
    const say = (html, cls = '') => {
      if (!status) return;
      status.innerHTML = html; status.className = 'lc-order-hint small ' + cls;
      clearTimeout(sayTimer); if (cls === 'ok') sayTimer = setTimeout(() => say(hint), 2400);
    };
    // "Lesson N" follows the new order straight away (the server renumbers the same way)
    const renumber = () => { let n = 0; rows().forEach(li => { if (li.dataset.key[0] === 'l' && !li.classList.contains('is-summary')) { n++; $$('[data-lnum]', li).forEach(s => s.textContent = n); } }); };
    const save = () => {
      const now = keys(); if (now === saved) return;
      renumber(); say('Saving…');
      send(outline.dataset.reorder, { order: now })
        .then(r => { if (r.ok) { saved = now; say('✓ Order saved', 'ok'); } else say(esc(r.error || 'Could not save. Refresh and try again.'), 'err'); })
        .catch(() => say('Could not save. Check your connection.', 'err'));
    };
    // slide the other rows into their new places instead of jumping
    const flip = (change, skip) => {
      const before = new Map(rows().map(li => [li, li.offsetTop]));
      change();
      if (reduce) return;
      rows().forEach(li => {
        const d = before.get(li) - li.offsetTop; if (!d || li === skip) return;
        li.style.transition = 'none'; li.style.transform = `translateY(${d}px)`;
        requestAnimationFrame(() => requestAnimationFrame(() => { li.style.transition = 'transform .2s cubic-bezier(.2,.8,.2,1)'; li.style.transform = ''; }));
        setTimeout(() => { if (!li.classList.contains('is-dragging')) li.style.transition = ''; }, 260);
      });
    };

    let drag = null;   // { li, pid, startY, startOff, y }
    const docY = cy => cy + scrollY;
    const place = () => {
      const { li } = drag, y = docY(drag.y) - (outline.getBoundingClientRect().top + scrollY);
      const target = rows().find(o => o !== li && y < o.offsetTop + o.offsetHeight / 2) || null;
      if (target ? li.nextElementSibling !== target : outline.lastElementChild !== li) flip(() => outline.insertBefore(li, target), li);
      li.style.transform = `translateY(${docY(drag.y) - drag.startY - (li.offsetTop - drag.startOff)}px)`;
    };
    const tick = () => {   // keep scrolling while the row is held near the top or bottom edge
      if (!drag) return;
      const edge = 70, v = drag.y < edge ? -(edge - drag.y) / 4 : drag.y > innerHeight - edge ? (drag.y - innerHeight + edge) / 4 : 0;
      if (v) { scrollBy(0, v); place(); }
      requestAnimationFrame(tick);
    };
    outline.addEventListener('pointerdown', e => {
      const grip = e.target.closest('[data-grip]');
      if (!grip || e.button !== 0) return;
      e.preventDefault();
      const li = grip.closest('li');
      drag = { li, pid: e.pointerId, startY: docY(e.clientY), startOff: li.offsetTop, y: e.clientY };
      grip.setPointerCapture(e.pointerId);
      li.classList.add('is-dragging'); outline.classList.add('is-sorting'); document.body.classList.add('lc-grabbing');
      requestAnimationFrame(tick);
    });
    outline.addEventListener('pointermove', e => { if (drag && e.pointerId === drag.pid) { drag.y = e.clientY; place(); } });
    const drop = e => {
      if (!drag || e.pointerId !== drag.pid) return;
      const { li } = drag; drag = null;
      li.style.transition = reduce ? '' : 'transform .18s ease'; li.style.transform = '';
      setTimeout(() => { li.style.transition = ''; li.classList.remove('is-dragging'); }, reduce ? 0 : 190);
      outline.classList.remove('is-sorting'); document.body.classList.remove('lc-grabbing');
      save();
    };
    outline.addEventListener('pointerup', drop);
    outline.addEventListener('pointercancel', drop);
    outline.addEventListener('keydown', e => {
      const grip = e.target.closest('[data-grip]');
      if (!grip || !['ArrowUp', 'ArrowDown'].includes(e.key)) return;
      e.preventDefault();
      const li = grip.closest('li'), to = e.key === 'ArrowUp' ? li.previousElementSibling : li.nextElementSibling;
      if (!to) return;
      flip(() => outline.insertBefore(li, e.key === 'ArrowUp' ? to : to.nextElementSibling));
      grip.focus(); renumber();
      say(`Moved to place ${rows().indexOf(li) + 1} of ${rows().length}`);
      clearTimeout(saveTimer); saveTimer = setTimeout(save, 700);
    });
  }

  /* ---------- files: drop zone, image viewer, cover upload ---------- */
  $$('[data-drop]').forEach(zone => {
    const input = $('[data-drop-input]', zone), list = $('[data-drop-list]', zone);
    const show = () => { list.innerHTML = [...input.files].map(f => `<span class="lm-chip">${esc(f.name)}</span>`).join(''); zone.classList.toggle('has-files', input.files.length > 0); };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(t => zone.addEventListener(t, e => { e.preventDefault(); zone.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(t => zone.addEventListener(t, () => zone.classList.remove('is-over')));
    zone.addEventListener('drop', e => { e.preventDefault(); if (e.dataTransfer?.files?.length) { input.files = e.dataTransfer.files; show(); } });
  });

  // cover picker: preview the picture before saving
  $$('[data-cover-input]').forEach(i => i.addEventListener('change', () => {
    const f = i.files[0], box = i.closest('label');
    if (f && box) { box.style.backgroundImage = `url("${URL.createObjectURL(f)}")`; box.style.backgroundSize = 'cover'; box.style.backgroundPosition = 'center'; box.classList.add('has-pick'); }
  }));

  const shots = $$('[data-lightbox]');
  if (shots.length) {
    let at = 0, box = null;
    const open = i => {
      at = (i + shots.length) % shots.length;
      if (!box) {
        box = document.createElement('div'); box.className = 'lm-box'; box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true');
        box.innerHTML = '<button class="lm-box-x" type="button" aria-label="Close">×</button><button class="lm-box-nav prev" type="button" aria-label="Previous image">‹</button><figure><img alt=""><figcaption></figcaption></figure><button class="lm-box-nav next" type="button" aria-label="Next image">›</button>';
        document.body.append(box);
        box.addEventListener('click', e => { if (e.target === box || e.target.closest('.lm-box-x')) close(); else if (e.target.closest('.prev')) open(at - 1); else if (e.target.closest('.next')) open(at + 1); });
      }
      const b = shots[at];
      $('img', box).src = b.dataset.lightbox; $('img', box).alt = b.dataset.caption || '';
      $('figcaption', box).textContent = (b.dataset.caption || '') + (shots.length > 1 ? `  ·  ${at + 1} / ${shots.length}` : '');
      $$('.lm-box-nav', box).forEach(n => n.hidden = shots.length < 2);
      box.classList.add('on'); document.body.style.overflow = 'hidden'; $('.lm-box-x', box).focus();
    };
    const close = () => { box.classList.remove('on'); document.body.style.overflow = ''; shots[at].focus(); };
    shots.forEach((b, i) => b.addEventListener('click', () => open(i)));
    document.addEventListener('keydown', e => {
      if (!box || !box.classList.contains('on')) return;
      if (e.key === 'Escape') close(); else if (e.key === 'ArrowRight') open(at + 1); else if (e.key === 'ArrowLeft') open(at - 1);
    });
  }

  /* ---------- checkout: payment methods, live card preview, demo validation ---------- */
  const ck = $('[data-checkout]');
  if (ck) {
    const amt = ck.dataset.amount, live = ck.dataset.cardLive === '1';
    const payBtn = $('[data-pay-label]', ck);
    const labels = { card: live ? 'Continue to secure card payment' : `Pay AED ${amt}`, applepay: 'Pay with Apple Pay', paypal: 'Continue with PayPal', tabby: `Pay AED ${Math.ceil(amt / 4)} today with tabby` };
    const method = () => ($('input[name="method"]:checked', ck) || {}).value || 'card';
    const sync = () => {
      const m = method();
      $$('[data-panel]', ck).forEach(p => p.hidden = p.dataset.panel !== m);
      payBtn.innerHTML = payBtn.innerHTML.replace(/[^>]*$/, ' ' + (labels[m] || `Pay AED ${amt}`));
      payBtn.dataset.method = m;
    };
    ck.addEventListener('change', e => { if (e.target.name === 'method') sync(); });
    sync();

    const f = k => $(`[data-card="${k}"]`, ck), show = k => $(`[data-card-show="${k}"]`, ck);
    const brandOf = n => /^4/.test(n) ? 'VISA' : /^(5[1-5]|2[2-7])/.test(n) ? 'Mastercard' : /^3[47]/.test(n) ? 'AMEX' : '';
    const luhn = n => { let s = 0; [...n].reverse().forEach((d, i) => { let x = +d; if (i % 2) { x *= 2; if (x > 9) x -= 9; } s += x; }); return n.length >= 13 && s % 10 === 0; };
    if (f('num')) {
      f('num').addEventListener('input', () => {
        const d = f('num').value.replace(/\D/g, '').slice(0, 19);
        f('num').value = d.replace(/(.{4})/g, '$1 ').trim();
        show('num').textContent = (d + '•'.repeat(Math.max(0, 16 - d.length))).replace(/(.{4})/g, '$1 ').trim();
        $('[data-card-brand]', ck).textContent = brandOf(d);
      });
      f('name').addEventListener('input', () => { show('name').textContent = f('name').value.toUpperCase() || 'YOUR NAME'; });
      f('exp').addEventListener('input', () => {
        const d = f('exp').value.replace(/\D/g, '').slice(0, 4);
        f('exp').value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
        show('exp').textContent = f('exp').value || 'MM/YY';
      });
      f('cvc').addEventListener('input', () => { f('cvc').value = f('cvc').value.replace(/\D/g, '').slice(0, 4); });
      $('[data-test-card]', ck)?.addEventListener('click', () => {
        const yy = String(new Date().getFullYear() + 3).slice(2);
        [['num', '4242 4242 4242 4242'], ['name', 'Test Student'], ['exp', '12/' + yy], ['cvc', '123']].forEach(([k, v]) => { f(k).value = v; f(k).dispatchEvent(new Event('input')); });
      });
    }
    ck.addEventListener('submit', e => {
      if (method() !== 'card' || live || !f('num')) { payBtn.disabled = true; payBtn.classList.add('is-busy'); return; }
      const n = f('num').value.replace(/\D/g, ''), [mm, yy] = f('exp').value.split('/').map(Number), err = $('[data-card-err]', ck);
      const now = new Date(), expOk = mm >= 1 && mm <= 12 && yy >= 0 && new Date(2000 + yy, mm) > now;
      const msg = !luhn(n) ? 'Check the card number.' : !f('name').value.trim() ? 'Add the name on the card.' : !expOk ? 'Check the expiry date.' : f('cvc').value.length < 3 ? 'Check the CVC.' : '';
      if (msg) { e.preventDefault(); err.textContent = msg; err.hidden = false; return; }
      err.hidden = true;
      ['num', 'name', 'exp', 'cvc'].forEach(k => f(k).value = '');   // never leaves the browser
      payBtn.disabled = true; payBtn.classList.add('is-busy');
    });
  }
  /* ---------- projects: "Apply for this role" picks the role in the application form ---------- */
  $$('[data-pick-role]').forEach(a => a.addEventListener('click', () => { const sel = $('#pjRole'); if (sel) sel.value = a.dataset.pickRole; setTimeout(() => $('#apply textarea')?.focus(), 300); }));
})();

/* Hero boards: the numbers count up when the page opens. */
(function () {
  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.querySelectorAll('.hb [data-count]').forEach(function (el) {
    var txt = el.textContent, m = txt.match(/\d[\d,]*/);
    if (!m) return;
    var end = parseInt(m[0].replace(/,/g, ''), 10), comma = m[0].indexOf(',') > -1;
    if (!end) return;
    var pre = txt.slice(0, m.index), post = txt.slice(m.index + m[0].length), t0 = null, dur = 900;
    var fmt = function (n) { return comma ? n.toLocaleString('en-US') : String(n); };
    var step = function (ts) {
      if (t0 === null) t0 = ts;
      var k = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - k, 3);
      el.textContent = pre + fmt(Math.round(end * e)) + post;
      if (k < 1) requestAnimationFrame(step);
    };
    el.textContent = pre + fmt(0) + post;
    requestAnimationFrame(step);
  });
})();
