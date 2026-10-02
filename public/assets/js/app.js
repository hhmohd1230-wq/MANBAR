/* MANBAR front-end — vanilla JS, no build step */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const meta = n => document.querySelector(`meta[name="${n}"]`)?.content || '';
  const CSRF = meta('csrf'), BASE = meta('base');
  const EMOJI = { like: '👍', love: '💚', insight: '💡', celebrate: '🎉', support: '🤝' };
  const LABEL = { like: 'Like', love: 'Love', insight: 'Insightful', celebrate: 'Celebrate', support: 'Support' };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const icon = (n, s = 18) => `<svg class="ic" width="${s}" height="${s}"><use href="${BASE}/assets/img/icons.svg#i-${n}"/></svg>`;

  async function api(path, data = {}, method = 'POST') {
    const opts = { method, headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'fetch', Accept: 'application/json' } };
    if (method === 'POST') { const fd = data instanceof FormData ? data : new URLSearchParams(data); opts.body = fd; }
    const r = await fetch(BASE + path, opts);
    let j = {};
    try { j = await r.json(); } catch (e) { /* non-json */ }
    if (!r.ok || j.ok === false) throw new Error(j.error || 'Something went wrong');
    return j;
  }

  function toast(msg, type = 'success') {
    let box = $('#toasts');
    if (!box) { box = document.createElement('div'); box.id = 'toasts'; box.className = 'toasts'; document.body.append(box); }
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = icon(type === 'error' ? 'alert' : 'check-circle', 20) + `<div>${esc(msg)}</div>`;
    box.append(t);
    setTimeout(() => { t.style.transition = '.4s'; t.style.opacity = 0; t.style.transform = 'translateX(30px)'; setTimeout(() => t.remove(), 400); }, 4200);
  }
  $$('#toasts .toast').forEach((t, i) => setTimeout(() => { t.style.transition = '.4s'; t.style.opacity = 0; setTimeout(() => t.remove(), 400); }, 5000 + i * 800));

  function modal(html) {
    const m = document.createElement('div');
    m.className = 'modal open';
    m.innerHTML = `<div class="modal-box">${html}</div>`;
    m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-close]')) m.remove(); });
    document.body.append(m);
    return m;
  }

  /* ---------- menus / nav ---------- */
  document.addEventListener('click', e => {
    const mb = e.target.closest('[data-menu]');
    if (mb) { const dd = mb.nextElementSibling; $$('.dropdown.open').forEach(d => d !== dd && d.classList.remove('open')); dd.classList.toggle('open'); return; }
    if (e.target.closest('#userBtn')) { $('#userDrop').classList.toggle('open'); return; }
    if (!e.target.closest('.dropdown')) $$('.dropdown.open').forEach(d => d.classList.remove('open'));
    if (e.target.closest('#menuBtn')) { document.body.classList.toggle('nav-open'); return; }
    if (document.body.classList.contains('nav-open') && !e.target.closest('.sidebar')) document.body.classList.remove('nav-open');
  });
  document.addEventListener('submit', e => {
    const c = e.target.closest('[data-confirm]');
    if (c && !confirm(c.dataset.confirm)) e.preventDefault();
  });
  document.addEventListener('click', e => {
    const c = e.target.closest('[data-copy]');
    if (c) { navigator.clipboard?.writeText(c.dataset.copy).then(() => toast('Link copied')); $$('.dropdown.open').forEach(d => d.classList.remove('open')); }
  });

  /* ---------- reactions ---------- */
  async function react(btn, kind) {
    const type = btn.dataset.react, id = btn.dataset.id;
    try {
      const r = await api('/api/react', { type, id, kind });
      if (type === 'post') {
        const card = btn.closest('.post');
        btn.dataset.kind = r.mine || 'like';
        btn.classList.toggle('on', !!r.mine);
        btn.innerHTML = r.mine ? `<span class="emo">${EMOJI[r.mine]}</span><span class="lbl">${LABEL[r.mine]}</span>` : `${icon('thumb', 19)}<span class="lbl">React</span>`;
        const sum = $('[data-react-summary]', card);
        const kinds = Object.keys(r.counts).sort((a, b) => r.counts[b] - r.counts[a]).slice(0, 3);
        sum.innerHTML = r.total ? `<span class="react-stack">${kinds.map(k => `<span>${EMOJI[k]}</span>`).join('')}</span><b data-react-total>${r.total}</b>` : '';
      } else {
        btn.classList.toggle('on', !!r.mine);
        btn.innerHTML = `${r.mine ? EMOJI[r.mine] : '👍'} <span data-ctotal>${r.total || 'Like'}</span>`;
      }
    } catch (err) { toast(err.message, 'error'); }
  }
  document.addEventListener('click', e => {
    const p = e.target.closest('[data-pick]');
    if (p) { const w = p.closest('.react-wrap'); w.classList.remove('show'); react($('[data-react]', w), p.dataset.pick); return; }
    const b = e.target.closest('[data-react]');
    if (b) { react(b, b.classList.contains('on') ? b.dataset.kind : 'like'); }
  });
  // long-press on touch devices opens the picker
  let lp;
  document.addEventListener('touchstart', e => { const w = e.target.closest('.react-wrap'); if (w) lp = setTimeout(() => w.classList.add('show'), 380); }, { passive: true });
  document.addEventListener('touchend', () => clearTimeout(lp));

  /* ---------- save / follow / share / report ---------- */
  document.addEventListener('click', async e => {
    const s = e.target.closest('[data-save]');
    if (s) { try { const r = await api('/api/bookmark', { id: s.dataset.save }); s.classList.toggle('on', r.saved); toast(r.saved ? 'Saved to your bookmarks' : 'Removed from saved'); } catch (err) { toast(err.message, 'error'); } return; }

    const f = e.target.closest('[data-follow]');
    if (f) {
      try {
        const r = await api('/api/follow', { id: f.dataset.follow });
        f.textContent = r.following ? 'Following' : (f.dataset.label || 'Follow');
        f.classList.toggle('btn-primary', !r.following && f.dataset.primary === '1');
        const c = $('[data-followers]'); if (c) c.textContent = r.followers;
      } catch (err) { toast(err.message, 'error'); }
      return;
    }

    const sh = e.target.closest('[data-share]');
    if (sh) {
      const id = sh.dataset.share;
      const m = modal(`<h3>Share this post</h3><p class="muted small">Repost it to your profile with an optional note, or copy the link.</p>
        <textarea class="textarea" id="shareNote" placeholder="Add your thoughts (optional)…" maxlength="500"></textarea>
        <div class="row" style="margin-top:14px;justify-content:flex-end"><button class="btn btn-ghost" data-close>Cancel</button><button class="btn" id="shareCopy">${icon('link', 16)} Copy link</button><button class="btn btn-primary" id="shareGo">${icon('share', 16)} Share to my feed</button></div>`);
      $('#shareCopy', m).onclick = () => { navigator.clipboard?.writeText(location.origin + BASE + '/post/' + id); toast('Link copied'); m.remove(); };
      $('#shareGo', m).onclick = async () => { try { const r = await api('/api/share', { id, note: $('#shareNote', m).value }); toast('Shared to your feed!'); m.remove(); setTimeout(() => location.href = r.url, 700); } catch (err) { toast(err.message, 'error'); } };
      return;
    }

    const rp = e.target.closest('[data-report]');
    if (rp) {
      $$('.dropdown.open').forEach(d => d.classList.remove('open'));
      const m = modal(`<h3>Report this ${esc(rp.dataset.report)}</h3><p class="muted small">Tell the moderators what is wrong. Reports are confidential.</p>
        <select class="select" id="repReason"><option>Spam or advertising</option><option>Harassment or hate</option><option>Inappropriate content</option><option>Misinformation</option><option>Cheating / academic dishonesty</option><option>Something else</option></select>
        <div class="row" style="margin-top:14px;justify-content:flex-end"><button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-danger" id="repGo">${icon('flag', 16)} Report</button></div>`);
      $('#repGo', m).onclick = async () => { try { const r = await api('/api/report', { type: rp.dataset.report, id: rp.dataset.id, reason: $('#repReason', m).value }); toast(r.message); m.remove(); } catch (err) { toast(err.message, 'error'); } };
    }
  });

  /* ---------- comments ---------- */
  function autoGrow(t) { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 160) + 'px'; }
  document.addEventListener('input', e => { if (e.target.matches('.comment-form textarea, .guide-foot textarea')) autoGrow(e.target); });
  document.addEventListener('keydown', e => {
    if (e.target.matches('.comment-form textarea, #guideIn') && e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.form.requestSubmit(); }
  });
  document.addEventListener('submit', async e => {
    const form = e.target.closest('[data-comment-form]');
    if (!form) return;
    e.preventDefault();
    const ta = $('textarea', form), body = ta.value.trim();
    if (!body) return;
    const btn = $('button[type=submit]', form); btn.disabled = true;
    try {
      const r = await api('/api/comment', { post_id: form.dataset.post, parent_id: form.dataset.parent || '', body });
      const tmp = document.createElement('div'); tmp.innerHTML = r.html.trim();
      const el = tmp.firstElementChild;
      if (form.dataset.parent) $(`[data-replies="${form.dataset.parent}"]`).append(el);
      else $('#commentList').append(el);
      $('#noComments')?.remove();
      ta.value = ''; autoGrow(ta);
      if (form.dataset.parent) form.remove();
      $$('[data-comment-count]').forEach(c => c.textContent = r.count);
    } catch (err) { toast(err.message, 'error'); }
    btn.disabled = false;
  });
  document.addEventListener('click', async e => {
    const rep = e.target.closest('[data-reply]');
    if (rep) {
      const id = rep.dataset.reply, box = $(`[data-replies="${id}"]`);
      if ($(`[data-comment-form][data-parent="${id}"]`, box)) return;
      const f = document.createElement('form');
      f.className = 'comment-form'; f.dataset.commentForm = ''; f.dataset.post = $('#commentList').dataset.post; f.dataset.parent = id;
      f.innerHTML = `<div class="grow"><textarea class="textarea" rows="1" placeholder="Write a reply…" required></textarea></div><button class="btn btn-primary btn-sm" type="submit">${icon('send', 15)}</button>`;
      box.append(f); $('textarea', f).focus();
      return;
    }
    const del = e.target.closest('[data-del-comment]');
    if (del && confirm('Delete this comment?')) {
      try { const r = await api('/api/comment/delete', { id: del.dataset.delComment }); $(`#c${del.dataset.delComment}`).remove(); $$('[data-comment-count]').forEach(c => c.textContent = r.count); } catch (err) { toast(err.message, 'error'); }
    }
    const fc = e.target.closest('[data-focus-comment]');
    if (fc && $('#commentList')) { e.preventDefault(); $('.comment-form textarea')?.focus(); $('#comments').scrollIntoView({ behavior: 'smooth' }); }
  });
  if (location.hash.startsWith('#c')) { const c = $(location.hash); if (c) { c.classList.add('flash-hl'); c.scrollIntoView({ block: 'center' }); } }

  /* ---------- tag inputs ---------- */
  function tagInput(box, hidden, opts = {}) {
    const input = box.querySelector('input') || Object.assign(document.createElement('input'), { placeholder: opts.ph || '' });
    if (!input.parentNode) box.append(input);
    const tags = [];
    const sync = () => {
      $$('.chip', box).forEach(c => c.remove());
      tags.forEach((t, i) => { const c = document.createElement('span'); c.className = 'chip'; c.innerHTML = `${opts.hash ? '#' : ''}${esc(t)} <button type="button" aria-label="Remove">×</button>`; c.querySelector('button').onclick = () => { tags.splice(i, 1); sync(); }; box.insertBefore(c, input); });
      hidden.value = tags.join(',');
      opts.onChange?.(tags);
    };
    const add = v => { v = v.trim().replace(/^#/, '').replace(/,/g, ''); if (v && !tags.some(t => t.toLowerCase() === v.toLowerCase()) && tags.length < (opts.max || 12)) { tags.push(v); sync(); } input.value = ''; };
    input.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(input.value); } else if (e.key === 'Backspace' && !input.value && tags.length) { tags.pop(); sync(); } });
    input.addEventListener('blur', () => add(input.value));
    box.addEventListener('click', () => input.focus());
    (hidden.value ? hidden.value.split(',') : []).forEach(t => t.trim() && tags.push(t.trim()));
    sync();
    return { add, tags };
  }
  $$('.tags-in[data-tags]').forEach(box => {
    const h = document.createElement('input'); h.type = 'hidden'; h.name = box.dataset.tags; h.value = box.dataset.initial || ''; box.after(h);
    const inp = document.createElement('input'); inp.placeholder = box.dataset.ph || 'Type and press Enter'; box.append(inp);
    tagInput(box, h, { ph: box.dataset.ph });
  });

  /* ---------- composer + AI assist ---------- */
  const comp = $('#composer');
  if (comp) {
    const form = $('#composerForm'), openBar = $('#composerOpen'), body = $('#cBody'), title = $('#cTitle');
    const show = () => { openBar.hidden = true; form.hidden = false; title.focus(); };
    openBar.addEventListener('click', show);
    $('#composerCancel').addEventListener('click', () => { form.hidden = true; openBar.hidden = false; });
    $$('input[name=type]', form).forEach(r => r.addEventListener('change', () => { body.placeholder = r.dataset.ph; }));
    const tags = tagInput($('#tagsBox'), $('#tagsVal'), { hash: true, max: 6, ph: 'Add #tags' });
    $('#cImage').addEventListener('change', e => {
      const f = e.target.files[0], pv = $('#imgPrev'); pv.innerHTML = '';
      if (!f) return;
      const url = URL.createObjectURL(f);
      pv.innerHTML = `<div class="img-preview"><img src="${url}" alt=""><button type="button" class="btn btn-sm btn-white" aria-label="Remove">×</button></div>`;
      $('button', pv).onclick = () => { e.target.value = ''; pv.innerHTML = ''; };
    });
    // AI assist
    $('#aiFix').addEventListener('click', async () => {
      const panel = $('#aiPanel'), txt = body.value.trim(), ttl = title.value.trim();
      if ((txt + ttl).length < 6) { toast('Write a few words first, then ask the assistant.', 'error'); return; }
      panel.hidden = false; panel.innerHTML = `<h4>${icon('sparkles', 18)} Thinking…</h4><div class="skeleton" style="height:60px"></div>`;
      try {
        const [rb, rt] = await Promise.all([api('/api/ai/assist', { text: txt || ttl }), ttl ? api('/api/ai/assist', { text: ttl }) : Promise.resolve(null)]);
        const fixB = rb.fix, newBody = txt ? fixB.corrected : null, newTitle = rt ? rt.fix.corrected.replace(/\.$/, '') : null;
        const changed = (newBody && newBody !== txt) || (newTitle && newTitle !== ttl);
        const rt1 = rb.route, p = rt1.primary, cur = $('input[name=type]:checked', form).value;
        let html = `<h4>${icon('sparkles', 18)} MANBAR Assistant <span class="chip sm outline">${fixB.engine === 'claude' ? 'AI' : 'smart rules'}</span></h4>`;
        if (changed) {
          html += `<div class="small muted">I polished your writing:</div>`;
          if (newTitle && newTitle !== ttl) html += `<div class="ai-diff"><b>Title:</b> ${esc(newTitle)}</div>`;
          if (newBody && newBody !== txt) html += `<div class="ai-diff">${esc(newBody)}</div>`;
          html += `<div>${(fixB.changes || []).filter(c => c.from !== '…' && c.from !== '').slice(0, 8).map(c => `<span class="ai-change"><s>${esc(c.from)}</s> → <b>${esc(c.to)}</b></span>`).join('')}</div>
            <div class="row" style="margin:10px 0 4px"><button type="button" class="btn btn-primary btn-sm" id="aiApply">${icon('check', 15)} Apply corrections</button></div>`;
        } else html += `<div class="small">✅ Your writing looks clean — nothing to correct.</div>`;
        const isType = ['idea', 'team', 'question', 'resource', 'achievement', 'event', 'announcement', 'teaching'].includes(p.key);
        html += `<div class="ai-route"><span class="badge-ico tone-green" style="width:40px;height:40px;margin:0;border-radius:13px">${icon('compass', 20)}</span><div class="grow small"><b>Best place: ${esc(p.label)}</b><br><span class="muted">${esc(p.reason)}</span></div>`;
        if (isType && p.key !== cur && $(`input[name=type][value=${p.key}]`, form)) html += `<button type="button" class="btn btn-sm" id="aiSwitch">Use “${esc(p.label)}”</button>`;
        else if (!isType) html += `<button type="button" class="btn btn-sm btn-primary" id="aiGo">Go there ${icon('arrow-right', 14)}</button>`;
        else html += `<span class="chip sm">Already selected ✓</span>`;
        html += `</div>`;
        if (rt1.tags?.length) html += `<div class="row wrap" style="margin-top:10px"><span class="small muted">Suggested tags:</span>${rt1.tags.map(t => `<button type="button" class="chip outline sm" data-addtag="${esc(t)}">+ #${esc(t)}</button>`).join('')}</div>`;
        panel.innerHTML = html;
        $('#aiApply', panel)?.addEventListener('click', () => { if (newBody) body.value = newBody; if (newTitle) title.value = newTitle; toast('Corrections applied'); panel.hidden = true; });
        $('#aiSwitch', panel)?.addEventListener('click', () => { const r = $(`input[name=type][value=${p.key}]`, form); r.checked = true; r.dispatchEvent(new Event('change')); toast('Post type changed to ' + p.label); });
        $('#aiGo', panel)?.addEventListener('click', () => { sessionStorage.setItem('manbarDraft', JSON.stringify({ title: title.value, body: newBody || txt })); location.href = p.url; });
        $$('[data-addtag]', panel).forEach(b => b.onclick = () => { tags.add(b.dataset.addtag); b.remove(); });
      } catch (err) { panel.innerHTML = `<div class="small" style="color:#b83232">${esc(err.message)}</div>`; }
    });
    if (!form.hidden) setTimeout(() => title.focus({ preventScroll: false }), 100);
  }

  /* ---------- draft hand-off (assistant -> other forms) ---------- */
  try {
    const d = JSON.parse(sessionStorage.getItem('manbarDraft') || 'null');
    if (d) {
      const t = $('[data-draft=title]'), b = $('[data-draft=body]');
      if (t || b) {
        if (t && !t.value) t.value = (d.title || d.body || '').split(/[.!?\n]/)[0].slice(0, 120);
        if (b && !b.value) b.value = d.body || d.title || '';
        sessionStorage.removeItem('manbarDraft');
        toast('I carried your draft over — review it and publish.');
      }
    }
  } catch (e) { /* ignore */ }

  /* ---------- AI guide widget ---------- */
  const guide = $('#guide');
  if (guide) {
    const msgs = $('#guideMsgs'), inp = $('#guideIn');
    const toggle = open => { guide.classList.toggle('open', open); if (open) inp.focus(); };
    $('#guideFab').onclick = () => toggle(!guide.classList.contains('open'));
    $('#guideClose').onclick = () => toggle(false);
    const add = (cls, html) => { const d = document.createElement('div'); d.className = 'gm ' + cls; d.innerHTML = html; msgs.append(d); msgs.scrollTop = msgs.scrollHeight; return d; };
    const send = async text => {
      add('me', esc(text));
      const wait = add('bot', '…');
      try {
        const r = await api('/api/ai/guide', { text });
        const p = r.route.primary;
        let h = esc(r.reply).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
        h += `<div class="ai-route" style="margin-top:10px"><div class="grow small"><b>${esc(p.label)}</b><br><span class="muted">${esc(p.hint)}</span></div><button class="btn btn-primary btn-sm" data-go="${esc(p.url)}">Take me there</button></div>`;
        if (r.route.alternatives.length) h += `<div class="sugg"><span class="xs muted">Or try:</span>${r.route.alternatives.map(a => `<button class="chip outline sm" data-go="${esc(a.url)}">${esc(a.label)}</button>`).join('')}</div>`;
        if (r.corrected && r.corrected !== text) h += `<div class="xs muted" style="margin-top:8px">Polished wording: “${esc(r.corrected)}”</div>`;
        wait.innerHTML = h;
        wait.dataset.text = r.corrected || text;
      } catch (err) { wait.innerHTML = esc(err.message); }
    };
    $('#guideForm').addEventListener('submit', e => { e.preventDefault(); const v = inp.value.trim(); if (!v) return; inp.value = ''; autoGrow(inp); send(v); });
    msgs.addEventListener('click', e => {
      const s = e.target.closest('[data-sugg]'); if (s) { send(s.dataset.sugg); return; }
      const g = e.target.closest('[data-go]');
      if (g) { const m = g.closest('.gm'); sessionStorage.setItem('manbarDraft', JSON.stringify({ body: m?.dataset.text || '' })); location.href = g.dataset.go; }
    });
  }

  /* ---------- infinite "load more" ---------- */
  const more = $('#loadMore');
  if (more) more.addEventListener('click', async () => {
    const feed = $('#feed'); const next = feed.dataset.next; more.disabled = true;
    const qs = new URLSearchParams(feed.dataset.qs); qs.set('page', next); qs.set('partial', '1');
    const r = await fetch(location.pathname + '?' + qs, { headers: { 'X-Requested-With': 'fetch' } });
    let html = await r.text();
    const hasMore = html.includes('<!--more-->'); html = html.replace('<!--more-->', '');
    feed.insertAdjacentHTML('beforeend', html);
    feed.dataset.next = +next + 1;
    if (hasMore) more.disabled = false; else more.parentElement.remove();
  });

  /* ---------- live counters ---------- */
  const setBadge = (k, n) => {
    const btn = document.querySelector(`a[href$="/${k}"].icon-btn`); if (!btn) return;
    let b = btn.querySelector('.badge-n');
    if (n > 0) { if (!b) { b = document.createElement('span'); b.className = 'badge-n'; btn.append(b); } b.textContent = n; } else b?.remove();
  };
  if ($('.topbar') && !document.body.classList.contains('admin')) setInterval(async () => {
    try { const r = await api('/api/counts', {}, 'GET'); setBadge('notifications', r.notifications); setBadge('messages', r.messages); } catch (e) { /* offline */ }
  }, 45000);

  /* ---------- course/lesson & misc ---------- */
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.requestSubmit()));
  const dm = $('#dmChat'); if (dm) dm.scrollTop = dm.scrollHeight;
  const chat = $('.chat'); if (chat && !chat.classList.contains('dm')) chat.scrollTop = 0;
})();
