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

  /* ---------- appearance / app shell ---------- */
  const root = document.documentElement;
  const themeButton = $('#themeToggle');
  const setTheme = theme => {
    const dark = theme === 'dark';
    root.dataset.theme = dark ? 'dark' : 'light';
    themeButton?.setAttribute('aria-pressed', String(dark));
    if (themeButton) {
      const label = `Switch to ${dark ? 'light' : 'dark'} mode`;
      themeButton.setAttribute('aria-label', label);
      themeButton.title = label;
    }
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#0b1711' : '#34b06b');
    try { localStorage.setItem('manbar.theme', dark ? 'dark' : 'light'); } catch (_) { /* storage may be unavailable */ }
  };
  setTheme(root.dataset.theme === 'dark' ? 'dark' : 'light');
  themeButton?.addEventListener('click', () => setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark'));

  const setSidebarCollapsed = collapsed => {
    root.classList.toggle('sidebar-pref-collapsed', collapsed && innerWidth > 960);
    $('#sidebarCollapse')?.setAttribute('aria-expanded', String(!collapsed));
    $('#sidebarRestore')?.setAttribute('aria-expanded', String(!collapsed));
    try { localStorage.setItem('manbar.sidebar', collapsed ? 'collapsed' : 'open'); } catch (_) { /* storage may be unavailable */ }
  };
  $('#sidebarCollapse')?.addEventListener('click', () => setSidebarCollapsed(true));
  $('#sidebarRestore')?.addEventListener('click', () => setSidebarCollapsed(false));
  addEventListener('resize', () => {
    if (innerWidth <= 960) root.classList.remove('sidebar-pref-collapsed');
    else {
      try { root.classList.toggle('sidebar-pref-collapsed', localStorage.getItem('manbar.sidebar') === 'collapsed'); } catch (_) { /* no-op */ }
    }
  });

  const hero = $('[data-hero-rotator]');
  if (hero) {
    const slides = $$('.home-hero-bg', hero), dots = $$('[data-hero-slide]', hero);
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');
    let active = 0, heroTimer = 0;
    const showHeroSlide = index => {
      active = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.classList.toggle('is-active', i === active));
      dots.forEach((dot, i) => {
        dot.classList.toggle('is-active', i === active);
        if (i === active) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
      });
    };
    const stopHero = () => clearInterval(heroTimer);
    const startHero = () => {
      stopHero();
      if (!reduceMotion.matches && !document.hidden) heroTimer = setInterval(() => showHeroSlide(active + 1), 6500);
    };
    dots.forEach(dot => dot.addEventListener('click', () => { showHeroSlide(+dot.dataset.heroSlide); startHero(); }));
    hero.addEventListener('mouseenter', stopHero);
    hero.addEventListener('mouseleave', startHero);
    hero.addEventListener('focusin', stopHero);
    hero.addEventListener('focusout', startHero);
    document.addEventListener('visibilitychange', () => document.hidden ? stopHero() : startHero());
    reduceMotion.addEventListener?.('change', startHero);
    startHero();
  }

  /* ---------- profile people dialogs ---------- */
  document.addEventListener('click', e => {
    const opener = e.target.closest('[data-people-dialog]');
    if (opener) {
      const dialog = document.getElementById(opener.dataset.peopleDialog);
      if (dialog?.showModal) dialog.showModal();
      return;
    }
    const closer = e.target.closest('[data-dialog-close]');
    if (closer) closer.closest('dialog')?.close();
  });
  $$('.people-dialog').forEach(dialog => dialog.addEventListener('click', e => {
    const box = dialog.getBoundingClientRect();
    if (e.clientX < box.left || e.clientX > box.right || e.clientY < box.top || e.clientY > box.bottom) dialog.close();
  }));

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
        <div class="writing-inline"><textarea class="textarea" id="shareNote" data-ai-writing="message" placeholder="Add your thoughts (optional)…" maxlength="500"></textarea><button class="icon-btn writing-quick" type="button" data-ai-quick aria-label="Improve writing" title="Improve writing">${icon('sparkles', 17)}</button></div>
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
      f.innerHTML = `<div class="grow"><textarea class="textarea" data-ai-writing="message" rows="1" placeholder="Write a reply…" required></textarea></div><button class="icon-btn writing-quick" type="button" data-ai-quick aria-label="Improve writing" title="Improve writing">${icon('sparkles', 15)}</button><button class="btn btn-primary btn-sm" type="submit" aria-label="Send reply">${icon('send', 15)}</button>`;
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

  /* ---------- profile appearance preview ---------- */
  const profileNamePreview = $('#profileNamePreview');
  const profileStylePreview = $('#profileStylePreview');
  if (profileNamePreview && profileStylePreview) {
    $$('input[name="name_style"]').forEach(input => input.addEventListener('change', () => {
      profileNamePreview.className = `profile-name profile-name-${input.value}`;
    }));
    $$('input[name="profile_effect"]').forEach(input => input.addEventListener('change', () => {
      profileStylePreview.dataset.effect = input.value;
    }));
  }
  const profileCoverInput = $('#profileCoverInput');
  const profileCoverPreview = $('#profileCoverPreview');
  if (profileCoverInput && profileCoverPreview) {
    let profileCoverObjectUrl = '';
    profileCoverInput.addEventListener('change', () => {
      const file = profileCoverInput.files[0];
      if (!file) return;
      if (profileCoverObjectUrl) URL.revokeObjectURL(profileCoverObjectUrl);
      profileCoverObjectUrl = URL.createObjectURL(file);
      profileCoverPreview.classList.add('has-image');
      profileCoverPreview.style.setProperty('--profile-cover-image', `url("${profileCoverObjectUrl}")`);
    });
    addEventListener('pagehide', () => { if (profileCoverObjectUrl) URL.revokeObjectURL(profileCoverObjectUrl); }, { once: true });
  }

  /* ---------- automatic local writing coach ---------- */
  $$('[data-ai-writing-form]').forEach(form => {
    const fields = $$('[data-ai-writing]', form);
    const panel = $('[data-writing-assist]', form);
    const draftButton = $('[data-ai-autofill]', form);
    const improveButton = $('[data-ai-improve-all]', form);
    if (!fields.length || !panel) return;
    let timer = 0, requestNo = 0, lastChecked = '', dismissed = '';

    const signature = () => fields.map(field => field.value.trim()).join('\u241e');
    const checkWriting = async (force = false) => {
      const sig = signature();
      if (sig.replace(/\u241e/g, '').length < 4) {
        if (force) { toast('Write a few words first.', 'error'); fields[0].focus(); }
        return;
      }
      if (!force && (sig === lastChecked || sig === dismissed)) return;
      lastChecked = sig;
      const ownRequest = ++requestNo;
      panel.hidden = false;
      panel.className = 'writing-assist is-checking';
      panel.innerHTML = `${icon('sparkles', 16)} <span>Checking spelling and grammar locally…</span>`;
      try {
        const checked = await Promise.all(fields.map(field => {
          const text = field.value.trim();
          return text.length >= 3
            ? api('/api/ai/assist', { text, kind: field.dataset.aiWriting, local_only: 1 })
            : Promise.resolve(null);
        }));
        if (ownRequest !== requestNo || signature() !== sig) return;
        const suggestions = checked.map((result, index) => result && result.fix.corrected !== fields[index].value.trim()
          ? { field: fields[index], original: fields[index].value.trim(), corrected: result.fix.corrected, changes: result.fix.changes || [] }
          : null).filter(Boolean);
        if (!suggestions.length) {
          panel.className = 'writing-assist is-clear';
          panel.innerHTML = `${icon('check-circle', 16)} <span>Writing check complete — everything looks clear.</span>`;
          setTimeout(() => { if (panel.classList.contains('is-clear')) panel.hidden = true; }, 2400);
          return;
        }
        const count = suggestions.reduce((n, item) => n + Math.max(1, item.changes.length), 0);
        panel.className = 'writing-assist has-suggestions';
        panel.innerHTML = `<div class="writing-assist-head"><span>${icon('sparkles', 17)} <b>${count} writing ${count === 1 ? 'improvement' : 'improvements'} ready</b></span><span class="writing-assist-local">Works without an API</span></div>
          <div class="writing-assist-previews">${suggestions.map(item => `<div><span>${item.field.dataset.aiWriting === 'title' ? 'Title' : 'Description'}</span><p>${esc(item.corrected)}</p></div>`).join('')}</div>
          <div class="writing-assist-actions"><button class="btn btn-primary btn-sm" type="button" data-writing-apply>${icon('check', 14)} Apply improvements</button><button class="btn btn-ghost btn-sm" type="button" data-writing-dismiss>Keep my wording</button></div>`;
        $('[data-writing-apply]', panel).onclick = () => {
          suggestions.forEach(item => { item.field.value = item.corrected; item.field.dispatchEvent(new Event('input', { bubbles: true })); if (item.field.tagName === 'TEXTAREA') autoGrow(item.field); });
          lastChecked = signature();
          panel.className = 'writing-assist is-clear';
          panel.innerHTML = `${icon('check-circle', 16)} <span>Improvements applied. You can still edit anything before publishing.</span>`;
          setTimeout(() => { if (panel.classList.contains('is-clear')) panel.hidden = true; }, 2600);
        };
        $('[data-writing-dismiss]', panel).onclick = () => { dismissed = sig; panel.hidden = true; };
      } catch (err) {
        if (ownRequest !== requestNo) return;
        panel.className = 'writing-assist is-error';
        panel.innerHTML = `${icon('alert', 16)} <span>${esc(err.message)} Your writing was not changed.</span>`;
      }
    };
    const setToolBusy = (button, busy, label) => {
      if (!button) return;
      if (!button.dataset.idleHtml) button.dataset.idleHtml = button.innerHTML;
      button.disabled = busy;
      button.innerHTML = busy ? `${icon('sparkles', 15)} ${label}` : button.dataset.idleHtml;
    };
    draftButton?.addEventListener('click', async () => {
      const titleField = $('[data-ai-writing="title"]', form);
      const bodyField = $('[data-ai-writing="body"]', form);
      const titleValue = titleField?.value.trim() || '';
      const bodyValue = bodyField?.value.trim() || '';
      if ((titleValue + bodyValue).length < 3) {
        toast('Add a short title or idea first, then I can build the draft.', 'error');
        (titleField || bodyField)?.focus();
        return;
      }
      clearTimeout(timer);
      const sourceSignature = signature();
      const draftRequest = ++requestNo;
      setToolBusy(draftButton, true, 'Building draft…');
      if (improveButton) improveButton.disabled = true;
      panel.hidden = false;
      panel.className = 'writing-assist is-checking';
      panel.innerHTML = `${icon('sparkles', 16)} <span>Turning your idea into a clear draft…</span>`;
      try {
        const result = await api('/api/ai/assist', {
          action: 'draft', context: form.dataset.aiContext || 'project', title: titleValue, body: bodyValue,
          category: $('[name="category"]', form)?.value || '', level: $('[name="level"]', form)?.value || '', local_only: 1
        });
        if (draftRequest !== requestNo || signature() !== sourceSignature) {
          panel.hidden = true;
          toast('Your text changed while I was drafting. Press Create a draft again when ready.', 'error');
          return;
        }
        const draft = result.draft;
        panel.className = 'writing-assist has-suggestions is-draft';
        panel.innerHTML = `<div class="writing-assist-head"><span>${icon('wand', 17)} <b>${draft.generated ? 'Your draft is ready' : 'Your writing is polished'}</b></span><span class="writing-assist-local">Smart draft · no API required</span></div>
          <div class="writing-assist-previews"><div><span>Title</span><p>${esc(draft.title)}</p></div><div><span>Description</span><p>${esc(draft.body)}</p></div></div>
          <p class="writing-assist-note">Review the details before publishing. MANBAR AI never submits the form for you.</p>
          <div class="writing-assist-actions"><button class="btn btn-primary btn-sm" type="button" data-draft-apply>${icon('check', 14)} Use this draft</button><button class="btn btn-ghost btn-sm" type="button" data-writing-dismiss>Keep editing</button></div>`;
        $('[data-draft-apply]', panel).onclick = () => {
          if (titleField) titleField.value = draft.title;
          if (bodyField) { bodyField.value = draft.body; autoGrow(bodyField); }
          fields.forEach(field => field.dispatchEvent(new Event('input', { bubbles: true })));
          clearTimeout(timer);
          lastChecked = signature();
          panel.hidden = false;
          panel.className = 'writing-assist is-clear';
          panel.innerHTML = `${icon('check-circle', 16)} <span>Draft added. Review and personalise it before publishing.</span>`;
          setTimeout(() => { if (panel.classList.contains('is-clear')) panel.hidden = true; }, 3200);
        };
        $('[data-writing-dismiss]', panel).onclick = () => { panel.hidden = true; };
      } catch (err) {
        panel.className = 'writing-assist is-error';
        panel.innerHTML = `${icon('alert', 16)} <span>${esc(err.message)} Nothing was changed.</span>`;
      } finally {
        setToolBusy(draftButton, false, '');
        if (improveButton) improveButton.disabled = false;
      }
    });
    improveButton?.addEventListener('click', () => checkWriting(true));
    fields.forEach(field => field.addEventListener('input', () => {
      clearTimeout(timer);
      panel.hidden = true;
      timer = setTimeout(checkWriting, 1800);
    }));
    form.addEventListener('focusout', event => {
      if (!event.target.matches('[data-ai-writing]')) return;
      clearTimeout(timer);
      timer = setTimeout(checkWriting, 350);
    });
  });

  /* Fast writing fields (chat and comments): improve only when the user asks. */
  document.addEventListener('click', async event => {
      const button = event.target.closest('[data-ai-quick]');
      if (!button) return;
      const container = button.closest('form, .writing-inline, .modal-box');
      const field = container?.querySelector('[data-ai-writing]');
      const original = field?.value.trim() || '';
      if (!field || original.length < 3) { toast('Write a few words first.', 'error'); field?.focus(); return; }
      if (button.disabled) return;
      button.disabled = true;
      button.classList.add('is-loading');
      const oldLabel = button.getAttribute('aria-label');
      button.setAttribute('aria-label', 'Improving writing');
      try {
        const result = await api('/api/ai/assist', { text: original, kind: field.dataset.aiWriting || 'message', local_only: 1 });
        const corrected = result.fix?.corrected || original;
        if (corrected === original) {
          toast('Your writing already looks clear.');
        } else {
          field.value = corrected;
          field.dispatchEvent(new Event('input', { bubbles: true }));
          if (field.tagName === 'TEXTAREA') autoGrow(field);
          toast('Writing improved — review it before sending.');
        }
        field.focus();
      } catch (err) {
        toast(err.message || 'Writing check failed. Try again.', 'error');
      } finally {
        button.disabled = false;
        button.classList.remove('is-loading');
        button.setAttribute('aria-label', oldLabel || 'Improve writing');
      }
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
        const [rb, rt] = await Promise.all([api('/api/ai/assist', { text: txt || ttl, kind: 'body' }), ttl ? api('/api/ai/assist', { text: ttl, kind: 'title' }) : Promise.resolve(null)]);
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
        } else html += `<div class="small row">${icon('check-circle', 16)} Your writing looks clean — nothing to correct.</div>`;
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
    const msgs = $('#guideMsgs'), inp = $('#guideIn'), form = $('#guideForm'), sendButton = $('button[type=submit]', form);
    let sending = false;
    const toggle = open => { guide.classList.toggle('open', open); $('#guideFab').setAttribute('aria-expanded', String(open)); if (open) inp.focus(); };
    $('#guideFab').onclick = () => toggle(!guide.classList.contains('open'));
    $('#guideClose').onclick = () => toggle(false);
    $$('[data-open-guide]').forEach(btn => btn.addEventListener('click', () => toggle(true)));
    const add = (cls, html) => { const d = document.createElement('div'); d.className = 'gm ' + cls; d.innerHTML = html; msgs.append(d); msgs.scrollTop = msgs.scrollHeight; return d; };
    const send = async text => {
      if (sending) return;
      sending = true;
      sendButton.disabled = true;
      add('me', esc(text));
      const wait = add('bot is-thinking', `<span class="guide-thinking">${icon('sparkles', 15)} Searching MANBAR and checking your writing…</span>`);
      try {
        const r = await api('/api/ai/guide', { text });
        const p = r.route.primary;
        let h = esc(r.reply).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
        if (r.results?.length) {
          const typeIcon = { project: 'rocket', person: 'user', mentor: 'compass', course: 'book', service: 'store' };
          h += `<div class="guide-result-label">${icon('search', 14)} Live MANBAR results</div><div class="guide-results">${r.results.map((item, index) => `<article class="guide-result ${index === 0 ? 'is-best' : ''}">
            <span class="guide-result-icon">${icon(typeIcon[item.type] || 'search', 16)}</span><div class="grow"><div class="guide-result-title">${esc(item.title)}${index === 0 ? '<span>Best match</span>' : ''}</div><p>${esc(item.description)}</p><small>${esc(item.meta)}</small><em>${esc(item.reason)}</em></div><button class="btn btn-sm" type="button" data-open-result="${esc(item.url)}" aria-label="Open ${esc(item.title)}">Open</button>
          </article>`).join('')}</div>`;
        } else if (r.intent === 'route') {
          h += `<div class="ai-route guide-route"><span class="guide-result-icon">${icon('compass', 17)}</span><div class="grow small"><b>${esc(p.label)}</b><br><span class="muted">${esc(p.hint)}</span></div><button class="btn btn-primary btn-sm" type="button" data-go="${esc(p.url)}">Take me there</button></div>`;
          if (r.route.alternatives.length) h += `<div class="sugg"><span class="xs muted">Other useful places:</span>${r.route.alternatives.map(a => `<button class="chip outline sm" type="button" data-go="${esc(a.url)}">${esc(a.label)}</button>`).join('')}</div>`;
        }
        if (r.corrected && r.corrected !== text) h += `<div class="guide-correction"><span>${icon('edit', 14)} Polished wording</span><p>${esc(r.corrected)}</p><button class="btn btn-ghost btn-sm" type="button" data-use-correction>Use this in the assistant</button></div>`;
        if (r.suggestions?.length) h += `<div class="sugg guide-followups">${r.suggestions.map(s => `<button class="chip outline sm" type="button" data-sugg="${esc(s)}">${esc(s)}</button>`).join('')}</div>`;
        wait.classList.remove('is-thinking');
        wait.innerHTML = h;
        wait.dataset.text = r.corrected || text;
      } catch (err) {
        wait.classList.remove('is-thinking');
        wait.innerHTML = `${icon('alert', 16)} ${esc(err.message)} <button class="btn btn-ghost btn-sm" type="button" data-sugg="${esc(text)}">Try again</button>`;
      } finally {
        sending = false;
        sendButton.disabled = false;
        msgs.scrollTop = msgs.scrollHeight;
      }
    };
    form.addEventListener('submit', e => { e.preventDefault(); const v = inp.value.trim(); if (!v || sending) return; inp.value = ''; autoGrow(inp); send(v); });
    msgs.addEventListener('click', e => {
      const s = e.target.closest('[data-sugg]'); if (s) { send(s.dataset.sugg); return; }
      const correction = e.target.closest('[data-use-correction]');
      if (correction) { inp.value = correction.closest('.gm')?.dataset.text || ''; autoGrow(inp); inp.focus(); return; }
      const result = e.target.closest('[data-open-result]');
      if (result) { location.href = result.dataset.openResult; return; }
      const g = e.target.closest('[data-go]');
      if (g) { const m = g.closest('.gm'); sessionStorage.setItem('manbarDraft', JSON.stringify({ body: m?.dataset.text || '' })); location.href = g.dataset.go; }
    });
  }

  /* ---------- project cover preview ---------- */
  const projectCoverInput = $('#projectCoverInput');
  const projectCoverPreview = $('#projectCoverPreview');
  if (projectCoverInput && projectCoverPreview) {
    const previewImage = $('img', projectCoverPreview);
    const removePreview = $('#projectCoverRemove');
    let previewUrl = '';
    const clearProjectCover = () => {
      projectCoverInput.value = '';
      projectCoverPreview.hidden = true;
      previewImage.removeAttribute('src');
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = '';
    };
    projectCoverInput.addEventListener('change', () => {
      const file = projectCoverInput.files?.[0];
      if (!file) return clearProjectCover();
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(file);
      previewImage.src = previewUrl;
      projectCoverPreview.hidden = false;
    });
    removePreview?.addEventListener('click', clearProjectCover);
  }

  /* ---------- first-time product tour ---------- */
  const tour = $('#productTour');
  if (tour) {
    const steps = [
      {
        selector: '[data-tour="ai"]',
        title: 'Start with Ask MANBAR AI',
        text: 'Tell MANBAR what you want to achieve in your own words. It can suggest the right tool, improve a draft, and help you decide what to do next.'
      },
      {
        selector: '[data-tour="spaces-overview"]',
        title: 'Six tools, one starting point',
        text: 'This section is your map of MANBAR. Choose AI, Projects, Marketplace, Learning center, Mentors, or People depending on what you need.'
      },
      {
        selector: '[data-tour="projects"]',
        title: 'Build or join a project',
        text: 'Create a project, describe the skills you need, invite teammates, manage tasks, and keep your shared work in one place.'
      },
      {
        selector: '[data-tour="marketplace"]',
        title: 'Offer or find student services',
        text: 'Use Marketplace to offer a skill—such as design, tutoring, writing, or development—or find a trusted student who can help.'
      },
      {
        selector: '[data-tour="learning"]',
        title: 'Learn something practical',
        text: 'Open the Learning center for short courses, lessons, and community resources you can use in class, a project, or your capstone.'
      },
      {
        selector: '[data-tour="mentors"]',
        title: 'Ask an experienced person',
        text: 'Find teachers and experienced members by expertise, view their profiles, and request guidance when you need direction.'
      },
      {
        selector: '[data-tour="people"]',
        title: 'Meet your campus community',
        text: 'Discover classmates across majors, follow their work, open their profiles, and start a private conversation.'
      }
    ];
    const card = $('#tourCard'), focus = $('#tourFocus'), title = $('#tourTitle'), copy = $('#tourText');
    const count = $('#tourCount'), next = $('#tourNext'), back = $('#tourBack'), progress = $('#tourProgress');
    const shades = Object.fromEntries($$('[data-tour-shade]', tour).map(el => [el.dataset.tourShade, el]));
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let current = 0, target = null, positionTimer;

    progress.innerHTML = steps.map(() => '<i></i>').join('');
    document.body.classList.add('tour-active');

    const px = n => Math.max(0, Math.round(n)) + 'px';
    const setBox = (el, x, y, w, h) => Object.assign(el.style, { left: px(x), top: px(y), width: px(w), height: px(h) });
    const position = () => {
      if (!target || !target.isConnected) return;
      const raw = target.getBoundingClientRect(), pad = 9;
      const x = Math.max(7, raw.left - pad), y = Math.max(7, raw.top - pad);
      const right = Math.min(innerWidth - 7, raw.right + pad), bottom = Math.min(innerHeight - 7, raw.bottom + pad);
      setBox(shades.top, 0, 0, innerWidth, y);
      setBox(shades.bottom, 0, bottom, innerWidth, innerHeight - bottom);
      setBox(shades.left, 0, y, x, bottom - y);
      setBox(shades.right, right, y, innerWidth - right, bottom - y);
      setBox(focus, x, y, right - x, bottom - y);

      const gap = 18, cw = card.offsetWidth, ch = card.offsetHeight;
      let left, top;
      if (innerWidth > 720 && right + gap + cw < innerWidth - 12) {
        left = right + gap; top = Math.min(Math.max(12, y), innerHeight - ch - 12);
      } else if (innerWidth > 720 && x - gap - cw > 12) {
        left = x - gap - cw; top = Math.min(Math.max(12, y), innerHeight - ch - 12);
      } else {
        left = Math.min(Math.max(14, raw.left + raw.width / 2 - cw / 2), innerWidth - cw - 14);
        top = bottom + gap + ch < innerHeight ? bottom + gap : Math.max(12, y - ch - gap);
      }
      Object.assign(card.style, { left: px(left), top: px(top) });
    };

    const showStep = index => {
      current = Math.max(0, Math.min(steps.length - 1, index));
      const step = steps[current];
      target = $(step.selector);
      if (!target) { finish(); return; }
      title.textContent = step.title;
      copy.textContent = step.text;
      count.textContent = `Step ${current + 1} of ${steps.length}`;
      back.hidden = current === 0;
      next.innerHTML = current === steps.length - 1 ? `Finish ${icon('check', 15)}` : `Next ${icon('arrow-right', 15)}`;
      $$('i', progress).forEach((dot, i) => dot.classList.toggle('on', i <= current));
      const mobile = innerWidth <= 700;
      if (mobile) {
        const oldScrollBehavior = document.documentElement.style.scrollBehavior;
        document.documentElement.style.scrollBehavior = 'auto';
        scrollTo(0, Math.max(0, scrollY + target.getBoundingClientRect().top - 82));
        document.documentElement.style.scrollBehavior = oldScrollBehavior;
      } else {
        target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center', inline: 'nearest' });
      }
      clearTimeout(positionTimer);
      positionTimer = setTimeout(() => {
        position();
      }, mobile || reduceMotion ? 0 : 320);
    };

    const finish = async () => {
      clearTimeout(positionTimer);
      document.body.classList.remove('tour-active');
      tour.remove();
      try { await api('/api/tour/complete'); }
      catch (err) { toast('The tour closed, but your preference could not be saved.', 'error'); }
    };

    next.addEventListener('click', () => current === steps.length - 1 ? finish() : showStep(current + 1));
    back.addEventListener('click', () => showStep(current - 1));
    $('#tourSkip').addEventListener('click', finish);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && tour.isConnected) finish(); });
    addEventListener('resize', position, { passive: true });
    addEventListener('scroll', position, { passive: true });
    requestAnimationFrame(() => { showStep(0); next.focus({ preventScroll: true }); });
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

  /* ---------- live direct messages ---------- */
  const dm = $('#dmChat');
  const dmForm = $('#dmForm');
  if (dm && dmForm) {
    const peer = Number(dm.dataset.peer);
    const input = $('#dmInput');
    const fileInput = $('#dmAttachment');
    const preview = $('#dmAttachmentPreview');
    const typing = $('#dmTyping');
    const emojiPanel = $('#dmEmojiPanel');
    const emojiButton = $('#dmEmojiButton');
    const recordButton = $('#dmRecordButton');
    const improveButton = $('[data-ai-quick]', dmForm);
    const voiceRecorder = $('#dmVoiceRecorder');
    const recordingLive = $('#dmRecordingLive');
    const recordingTime = $('#dmRecordingTime');
    const voiceReady = $('#dmVoiceReady');
    const voicePreview = $('#dmVoicePreview');
    const voiceSize = $('#dmVoiceSize');
    const send = dmForm.querySelector('.dm-send');
    const maxBytes = Number(dmForm.dataset.maxBytes || 12 * 1024 * 1024);
    let last = Number(dm.dataset.last || 0);
    let pollBusy = false;
    let typingSentAt = 0;
    let mediaRecorder = null;
    let mediaStream = null;
    let recordingChunks = [];
    let recordedFile = null;
    let recordingStartedAt = 0;
    let recordingTimer = null;
    let discardRecording = false;
    let voiceObjectUrl = '';

    const nearBottom = () => dm.scrollHeight - dm.scrollTop - dm.clientHeight < 110;
    const scrollBottom = smooth => dm.scrollTo({ top: dm.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    const sizeLabel = bytes => bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : bytes >= 1024 ? `${Math.round(bytes / 1024)} KB` : `${bytes} B`;
    const clearFile = () => {
      fileInput.value = '';
      preview.hidden = true;
      const old = preview.querySelector('.dm-preview-thumb');
      if (old?.dataset.objectUrl) URL.revokeObjectURL(old.dataset.objectUrl);
      old?.remove();
      preview.querySelector('.dm-preview-icon')?.removeAttribute('hidden');
    };
    const formatRecordingTime = seconds => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    const releaseMicrophone = () => {
      mediaStream?.getTracks().forEach(track => track.stop());
      mediaStream = null;
    };
    const setRecordingState = active => {
      dmForm.classList.toggle('is-recording', active);
      input.disabled = active;
      fileInput.disabled = active;
      emojiButton.disabled = active;
      if (improveButton) improveButton.disabled = active;
      send.disabled = active;
      recordButton.classList.toggle('recording', active);
      recordButton.setAttribute('aria-pressed', String(active));
      recordButton.setAttribute('aria-label', active ? 'Stop recording' : 'Record a voice message');
      recordButton.title = active ? 'Stop recording' : 'Record a voice message';
    };
    const resetVoicePreview = () => {
      recordedFile = null;
      if (voiceObjectUrl) URL.revokeObjectURL(voiceObjectUrl);
      voiceObjectUrl = '';
      voicePreview.pause();
      voicePreview.removeAttribute('src');
      voicePreview.load();
      voiceReady.hidden = true;
      recordingLive.hidden = true;
      voiceRecorder.hidden = true;
      voiceSize.textContent = 'Review it before sending';
    };
    const stopRecording = discard => {
      discardRecording = discard;
      clearInterval(recordingTimer);
      recordingTimer = null;
      if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
      else {
        releaseMicrophone();
        setRecordingState(false);
        if (discard) resetVoicePreview();
      }
    };
    const startRecording = async () => {
      if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        toast('Voice recording is not supported in this browser.', 'error');
        return;
      }
      if (!window.isSecureContext) {
        toast('Voice recording requires HTTPS or localhost.', 'error');
        return;
      }
      clearFile();
      resetVoicePreview();
      emojiPanel.hidden = true;
      emojiButton.classList.remove('active');
      emojiButton.setAttribute('aria-expanded', 'false');
      try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
        const options = typeof MediaRecorder.isTypeSupported === 'function'
          ? ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/webm'].find(type => MediaRecorder.isTypeSupported(type))
          : '';
        mediaRecorder = options ? new MediaRecorder(mediaStream, { mimeType: options }) : new MediaRecorder(mediaStream);
        recordingChunks = [];
        discardRecording = false;
        mediaRecorder.addEventListener('dataavailable', e => { if (e.data.size) recordingChunks.push(e.data); });
        mediaRecorder.addEventListener('error', () => {
          toast('The recording was interrupted. Please try again.', 'error');
          stopRecording(true);
        });
        mediaRecorder.addEventListener('stop', () => {
          const recorderType = (mediaRecorder?.mimeType || recordingChunks[0]?.type || 'audio/webm').split(';')[0];
          releaseMicrophone();
          setRecordingState(false);
          recordingLive.hidden = true;
          if (discardRecording) { resetVoicePreview(); return; }
          const blob = new Blob(recordingChunks, { type: recorderType });
          if (!blob.size) { resetVoicePreview(); toast('No audio was captured. Please try again.', 'error'); return; }
          if (blob.size > maxBytes) { resetVoicePreview(); toast(`Voice messages can be up to ${sizeLabel(maxBytes)}.`, 'error'); return; }
          const extension = { 'audio/mp4': 'm4a', 'audio/ogg': 'ogg', 'audio/wav': 'wav', 'audio/aac': 'aac' }[recorderType] || 'webm';
          recordedFile = new File([blob], `voice-message-${Date.now()}.${extension}`, { type: recorderType });
          voiceObjectUrl = URL.createObjectURL(blob);
          voicePreview.src = voiceObjectUrl;
          voicePreview.load();
          voiceSize.textContent = `${sizeLabel(blob.size)} · ready to send`;
          voiceReady.hidden = false;
          voiceRecorder.hidden = false;
          input.focus();
        }, { once: true });
        voiceRecorder.hidden = false;
        voiceReady.hidden = true;
        recordingLive.hidden = false;
        recordingTime.textContent = '00:00';
        recordingTime.dateTime = 'PT0S';
        recordingStartedAt = Date.now();
        setRecordingState(true);
        mediaRecorder.start(250);
        recordingTimer = setInterval(() => {
          const seconds = Math.min(120, Math.floor((Date.now() - recordingStartedAt) / 1000));
          recordingTime.textContent = formatRecordingTime(seconds);
          recordingTime.dateTime = `PT${seconds}S`;
          if (seconds >= 120) { stopRecording(false); toast('The two-minute recording limit was reached.'); }
        }, 250);
      } catch (err) {
        releaseMicrophone();
        setRecordingState(false);
        resetVoicePreview();
        const message = err?.name === 'NotAllowedError' ? 'Microphone access was blocked. Allow it in your browser, then try again.'
          : err?.name === 'NotFoundError' ? 'No microphone was found on this device.'
          : err?.name === 'NotReadableError' ? 'Your microphone is being used by another app.'
          : 'The microphone could not be started. Please try again.';
        toast(message, 'error');
      }
    };
    const appendMessage = m => {
      if (!m || dm.querySelector(`[data-message-id="${m.id}"]`)) return;
      const shouldScroll = nearBottom() || m.mine;
      $('#dmEmpty')?.setAttribute('hidden', '');
      const row = document.createElement('div');
      row.className = `msg${m.mine ? ' mine' : ''}`;
      row.dataset.messageId = m.id;
      const bubble = document.createElement('div');
      bubble.className = 'bub';
      if (m.attachment) {
        if (m.attachment.audio) {
          const audioWrap = document.createElement('div'); audioWrap.className = 'dm-audio';
          const audioIcon = document.createElement('span'); audioIcon.className = 'dm-audio-icon'; audioIcon.innerHTML = icon('mic', 20);
          const audioMain = document.createElement('span'); audioMain.className = 'dm-audio-main';
          const audio = document.createElement('audio'); audio.controls = true; audio.preload = 'metadata'; audio.src = m.attachment.url;
          const detail = document.createElement('small'); detail.textContent = `Voice message · ${m.attachment.size_label}`;
          const download = document.createElement('a'); download.className = 'dm-audio-download'; download.href = m.attachment.url; download.download = ''; download.setAttribute('aria-label', 'Download voice message'); download.innerHTML = icon('download', 17);
          audioMain.append(audio, detail); audioWrap.append(audioIcon, audioMain, download); bubble.append(audioWrap);
        } else if (m.attachment.image) {
          const a = document.createElement('a'); a.href = m.attachment.url;
          a.className = 'dm-image'; a.target = '_blank'; a.rel = 'noopener';
          const img = document.createElement('img'); img.src = m.attachment.url; img.alt = m.attachment.name; img.loading = 'lazy'; a.append(img);
          bubble.append(a);
        } else {
          const a = document.createElement('a'); a.href = m.attachment.url;
          a.className = 'dm-file'; a.download = '';
          const fi = document.createElement('span'); fi.className = 'dm-file-icon'; fi.innerHTML = icon('file', 21);
          const copy = document.createElement('span');
          const name = document.createElement('b'); name.textContent = m.attachment.name;
          const size = document.createElement('small'); size.textContent = m.attachment.size_label;
          copy.append(name, size); a.append(fi, copy);
          const dl = document.createElement('span'); dl.innerHTML = icon('download', 18); a.append(...dl.childNodes);
          bubble.append(a);
        }
      }
      if (m.body) {
        const body = document.createElement('div'); body.className = 'dm-message-text'; body.textContent = m.body; bubble.append(body);
      }
      const metaRow = document.createElement('div'); metaRow.className = 'dm-message-meta';
      const time = document.createElement('time'); time.textContent = m.time; metaRow.append(time);
      if (m.mine) { const tick = document.createElement('span'); tick.innerHTML = icon('check', 13); metaRow.append(...tick.childNodes); }
      bubble.append(metaRow); row.append(bubble); dm.insertBefore(row, typing);
      last = Math.max(last, Number(m.id)); dm.dataset.last = last;
      if (shouldScroll) requestAnimationFrame(() => scrollBottom(true));
    };

    dmForm.addEventListener('submit', async e => {
      e.preventDefault();
      if (mediaRecorder?.state === 'recording') { toast('Stop the recording before sending.'); return; }
      if (!input.value.trim() && !fileInput.files.length && !recordedFile) { input.focus(); return; }
      send.disabled = true;
      try {
        const payload = new FormData(dmForm);
        if (recordedFile) payload.set('attachment', recordedFile);
        const r = await api(`/messages/${peer}`, payload);
        appendMessage(r.message);
        input.value = ''; input.style.height = ''; clearFile(); resetVoicePreview();
        emojiPanel.hidden = true; emojiButton.setAttribute('aria-expanded', 'false');
      } catch (err) { toast(err.message, 'error'); }
      finally { send.disabled = false; input.focus(); }
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); dmForm.requestSubmit(); }
    });
    input.addEventListener('input', () => {
      input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 112) + 'px';
      const now = Date.now();
      if (input.value.trim() && now - typingSentAt > 1400) { typingSentAt = now; api(`/api/messages/${peer}/typing`).catch(() => {}); }
    });
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0]; if (!file) return clearFile();
      resetVoicePreview();
      if (file.size > maxBytes) { toast(`Attachments can be up to ${sizeLabel(maxBytes)}.`, 'error'); clearFile(); return; }
      $('#dmAttachmentName').textContent = file.name; $('#dmAttachmentSize').textContent = sizeLabel(file.size); preview.hidden = false;
      preview.querySelector('.dm-preview-thumb')?.remove();
      preview.querySelector('.dm-preview-icon')?.removeAttribute('hidden');
      if (file.type.startsWith('image/')) {
        const img = document.createElement('img'); img.className = 'dm-preview-thumb'; img.src = URL.createObjectURL(file); img.dataset.objectUrl = img.src;
        preview.querySelector('.dm-preview-icon').setAttribute('hidden', ''); preview.prepend(img);
      }
    });
    $('#dmAttachmentRemove')?.addEventListener('click', clearFile);
    recordButton.addEventListener('click', () => mediaRecorder?.state === 'recording' ? stopRecording(false) : startRecording());
    $('#dmRecordStop')?.addEventListener('click', () => stopRecording(false));
    $('#dmRecordCancel')?.addEventListener('click', () => stopRecording(true));
    $('#dmVoiceDiscard')?.addEventListener('click', () => { resetVoicePreview(); input.focus(); });
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
      recordButton.disabled = true;
      recordButton.title = 'Voice recording is not supported in this browser';
    }
    emojiButton.addEventListener('click', e => { e.stopPropagation(); emojiPanel.hidden = !emojiPanel.hidden; emojiButton.classList.toggle('active', !emojiPanel.hidden); emojiButton.setAttribute('aria-expanded', String(!emojiPanel.hidden)); });
    emojiPanel.addEventListener('click', e => {
      const b = e.target.closest('[data-dm-emoji]'); if (!b) return;
      const start = input.selectionStart, end = input.selectionEnd;
      input.setRangeText(b.dataset.dmEmoji, start, end, 'end'); input.dispatchEvent(new Event('input')); input.focus();
    });
    document.addEventListener('click', e => { if (!e.target.closest('#dmEmojiPanel') && !e.target.closest('#dmEmojiButton')) { emojiPanel.hidden = true; emojiButton.classList.remove('active'); emojiButton.setAttribute('aria-expanded', 'false'); } });

    const poll = async () => {
      if (!pollBusy) {
        pollBusy = true;
        try {
          const r = await api(`/api/messages/${peer}?after=${last}`, {}, 'GET');
          r.messages.forEach(appendMessage); typing.hidden = !r.typing;
          if (r.typing && nearBottom()) requestAnimationFrame(() => scrollBottom(false));
        } catch (e) { /* transient network failure; retry */ }
        finally { pollBusy = false; }
      }
      setTimeout(poll, document.hidden ? 5000 : 2000);
    };
    addEventListener('pagehide', () => { clearInterval(recordingTimer); releaseMicrophone(); if (voiceObjectUrl) URL.revokeObjectURL(voiceObjectUrl); }, { once: true });
    scrollBottom(false); setTimeout(poll, 900);
  }

  /* ---------- course/lesson & misc ---------- */
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.requestSubmit()));
  const chat = $('.chat'); if (chat && !chat.classList.contains('dm')) chat.scrollTop = 0;
})();

/* ---------- landing: scroll reveal, count-up, live reaction counters ---------- */
(() => {
  const els = document.querySelectorAll('.reveal');
  if (els.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12 });
    els.forEach(el => io.observe(el));
  } else els.forEach(el => el.classList.add('in'));
  document.querySelectorAll('[data-count]').forEach(el => {
    const end = +el.dataset.count; if (!end) return;
    const t0 = performance.now(), dur = 1400;
    const step = t => { const k = Math.min(1, (t - t0) / dur); el.textContent = Math.round(end * (1 - Math.pow(1 - k, 3))).toLocaleString(); if (k < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  });
  const ticks = [...document.querySelectorAll('.stage .tick')];
  if (ticks.length && !matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => {
    const el = ticks[Math.floor(Math.random() * ticks.length)];
    el.textContent = (+el.textContent + 1 + Math.floor(Math.random() * 3));
    el.classList.add('bump'); setTimeout(() => el.classList.remove('bump'), 260);
  }, 700);
})();
