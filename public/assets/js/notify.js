/* Bell pop-up in the top bar + the Settings page (billing forms, theme and sidebar choices). */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var base = ($('meta[name="base"]') || {}).content || '';
  var csrf = ($('meta[name="csrf"]') || {}).content || '';

  /* ---------- notification pop-up ---------- */
  var btn = $('#notifBtn'), panel = $('#notifPanel');
  if (btn && panel) {
    var body = $('[data-nb-body]', panel), unreadEl = $('[data-nb-unread]', panel), readAll = $('[data-nb-readall]', panel);
    var filter = 'all';
    var setBadge = function (n) {
      var b = btn.querySelector('.badge-n');
      if (n > 0) { if (!b) { b = document.createElement('span'); b.className = 'badge-n'; btn.appendChild(b); } b.textContent = n; } else if (b) b.remove();
      unreadEl.hidden = !n; unreadEl.textContent = n + ' new';
      readAll.disabled = !n;
    };
    var applyFilter = function () {
      body.classList.toggle('is-new-only', filter === 'unread');
      // hide day labels with nothing under them
      $$('.nt-day', body).forEach(function (d) {
        var el = d.nextElementSibling, any = false;
        while (el && !el.classList.contains('nt-day')) { if (getComputedStyle(el).display !== 'none') any = true; el = el.nextElementSibling; }
        d.hidden = !any;
      });
      var none = filter === 'unread' && !$('.nt-item.is-unread', body), msg = $('.nb-none', body);
      if (none && !msg) body.insertAdjacentHTML('beforeend', '<div class="nb-none">No new notifications. You’re all caught up!</div>');
      if (!none && msg) msg.remove();
    };
    var load = function () {
      fetch(base + '/api/notifications', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (!d.ok) throw 0; body.innerHTML = d.html; setBadge(d.unread); applyFilter(); })
        .catch(function () { body.innerHTML = '<div class="nb-none">Couldn’t load notifications. <a href="' + btn.href + '">Open the page</a></div>'; });
    };
    // a centred pop-up window (like Followers / Following on profiles)
    var open = function () {
      $$('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
      if (panel.showModal) panel.showModal(); else panel.setAttribute('open', '');
      btn.setAttribute('aria-expanded', 'true');
      load();
    };
    var close = function () { if (panel.open) panel.close(); };
    panel.addEventListener('close', function () { btn.setAttribute('aria-expanded', 'false'); btn.focus(); });
    btn.addEventListener('click', function (e) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;   // let "open in new tab" work
      e.preventDefault();
      open();
    });
    $('[data-nb-close]', panel).addEventListener('click', close);
    panel.addEventListener('click', function (e) {   // a click on the dimmed backdrop closes it
      if (e.target !== panel) return;
      var r = panel.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) close();
    });
    $$('[data-nb-filter]', panel).forEach(function (t) {
      t.addEventListener('click', function () {
        filter = t.dataset.nbFilter;
        $$('[data-nb-filter]', panel).forEach(function (x) { x.classList.toggle('on', x === t); });
        applyFilter();
      });
    });
    readAll.addEventListener('click', function () {
      readAll.disabled = true;
      fetch(base + '/notifications/read-all', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-Token': csrf } })
        .then(function (r) { return r.json(); })
        .then(function () {
          $$('.nt-item.is-unread', body).forEach(function (i) { i.classList.remove('is-unread'); var d = $('.nt-dot', i); if (d) d.remove(); });
          setBadge(0); applyFilter();
        })
        .catch(function () { readAll.disabled = false; });
    });
  }

  /* ---------- settings: add a payment method ---------- */
  $$('.st-kinds [data-kind]').forEach(function (k) {
    k.addEventListener('click', function () {
      $$('.st-kinds [data-kind]').forEach(function (x) { x.classList.toggle('on', x === k); });
      $$('.st-add').forEach(function (f) { f.hidden = f.dataset.kind !== k.dataset.kind; });
    });
  });
  var cf = $('[data-billing-form]');
  if (cf) {
    var f = function (k) { return $('[data-cf="' + k + '"]', cf); }, show = function (k) { return $('[data-cc="' + k + '"]', cf); };
    var preview = $('[data-cc-preview]', cf), logoBox = $('[data-cc-logo]', cf);
    var logos = {};
    $$('.st-kinds svg').forEach(function (s) { var l = (s.getAttribute('aria-label') || '').toLowerCase(); if (l) logos[l] = s.outerHTML; });
    var brandOf = function (n) { return /^4/.test(n) ? 'visa' : /^(5[1-5]|2[2-7])/.test(n) ? 'mastercard' : /^3[47]/.test(n) ? 'amex' : ''; };
    var luhn = function (n) { var s = 0; n.split('').reverse().forEach(function (d, i) { var x = +d; if (i % 2) { x *= 2; if (x > 9) x -= 9; } s += x; }); return n.length >= 13 && s % 10 === 0; };
    f('num').addEventListener('input', function () {
      var d = f('num').value.replace(/\D/g, '').slice(0, 19), b = brandOf(d);
      f('num').value = d.replace(/(.{4})/g, '$1 ').trim();
      show('num').textContent = (d + '•'.repeat(Math.max(0, 16 - d.length))).replace(/(.{4})/g, '$1 ').trim();
      preview.className = 'st-cc st-cc-preview st-cc-' + (b || 'visa');
      var key = b === 'amex' ? 'american express' : b;
      if (key && logos[key]) logoBox.innerHTML = logos[key];
    });
    f('name').addEventListener('input', function () { show('name').textContent = f('name').value.toUpperCase() || 'YOUR NAME'; });
    f('exp').addEventListener('input', function () {
      var d = f('exp').value.replace(/\D/g, '').slice(0, 4);
      f('exp').value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
      show('exp').textContent = f('exp').value || 'MM/YY';
    });
    f('cvc').addEventListener('input', function () { f('cvc').value = f('cvc').value.replace(/\D/g, '').slice(0, 4); });
    var test = $('[data-cf-test]', cf);
    if (test) test.addEventListener('click', function () {
      var yy = String(new Date().getFullYear() + 3).slice(2);
      [['num', '4242 4242 4242 4242'], ['name', 'Test Student'], ['exp', '12/' + yy], ['cvc', '123']].forEach(function (p) { f(p[0]).value = p[1]; f(p[0]).dispatchEvent(new Event('input')); });
    });
    cf.addEventListener('submit', function (e) {
      var n = f('num').value.replace(/\D/g, ''), parts = f('exp').value.split('/'), mm = +parts[0], yy = +parts[1], err = $('[data-cf-err]', cf);
      var expOk = mm >= 1 && mm <= 12 && yy >= 0 && new Date(2000 + yy, mm) > new Date();
      var msg = !brandOf(n) ? 'We accept Visa, Mastercard and American Express.' : !luhn(n) ? 'Check the card number.' : !f('name').value.trim() ? 'Add the name on the card.' : !expOk ? 'Check the expiry date.' : f('cvc').value.length < 3 ? 'Check the CVC.' : '';
      if (msg) { e.preventDefault(); err.textContent = msg; err.hidden = false; return; }
      err.hidden = true;
      // only these leave the browser: brand, last 4 digits, expiry and the name
      cf.elements.brand.value = brandOf(n); cf.elements.last4.value = n.slice(-4);
      cf.elements.exp_month.value = mm; cf.elements.exp_year.value = 2000 + yy; cf.elements.holder.value = f('name').value.trim();
      ['num', 'name', 'exp', 'cvc'].forEach(function (k) { f(k).value = ''; });
    });
  }

  /* ---------- settings: theme and sidebar ---------- */
  var root = document.documentElement;
  var markChoices = function () {
    $$('[data-theme-choice]').forEach(function (b) { b.classList.toggle('on', b.dataset.themeChoice === (root.dataset.theme === 'dark' ? 'dark' : 'light')); });
    $$('[data-sidebar-choice]').forEach(function (b) { b.classList.toggle('on', b.dataset.sidebarChoice === (root.classList.contains('sidebar-pref-collapsed') ? 'collapsed' : 'open')); });
  };
  $$('[data-theme-choice]').forEach(function (b) {
    b.addEventListener('click', function () {
      if ((root.dataset.theme === 'dark' ? 'dark' : 'light') !== b.dataset.themeChoice) { var t = $('#themeToggle'); if (t) t.click(); }
      markChoices();
    });
  });
  $$('[data-sidebar-choice]').forEach(function (b) {
    b.addEventListener('click', function () {
      if ((root.classList.contains('sidebar-pref-collapsed') ? 'collapsed' : 'open') !== b.dataset.sidebarChoice) { var t = $('#sidebarCollapse'); if (t) t.click(); }
      markChoices();
    });
  });
  markChoices();
})();
