/* Admin console helpers: Rank XP preview, reason prompts, warning presets, scam case options. */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- Rank XP: add / remove / set, with a live preview ---------- */
  var xp = $('[data-xp-form]');
  if (xp) {
    var score = +xp.dataset.score, levels = JSON.parse(xp.dataset.levels || '[]');
    var amount = xp.elements.amount, level = $('[data-xp-level]', xp), box = $('[data-xp-amount]', xp), out = $('[data-xp-preview]', xp);
    var rankOf = function (v) { var l = levels[0]; levels.forEach(function (x) { if (v >= x[0]) l = x; }); return l ? l[1] : ''; };
    var mode = function () { return (xp.querySelector('input[name="mode"]:checked') || {}).value || 'add'; };
    var target = function () {
      var m = mode(), a = Math.max(0, +amount.value || 0);
      return Math.max(0, m === 'set' ? +level.value : m === 'remove' ? score - a : score + a);
    };
    var paint = function () {
      var m = mode();
      box.hidden = m === 'set'; level.hidden = m !== 'set';
      if (m === 'set') amount.value = level.value;
      var t = target(), d = t - score;
      out.innerHTML = d === 0 ? '' : '<span>' + score.toLocaleString() + ' XP · ' + rankOf(score) + '</span> → <b' + (d < 0 ? ' class="is-down"' : '') + '>' + t.toLocaleString() + ' XP · ' + rankOf(t) + '</b> <span>(' + (d > 0 ? '+' : '') + d.toLocaleString() + ')</span>';
    };
    xp.addEventListener('change', paint);
    amount.addEventListener('input', paint);
    $$('[data-xp-chip]', xp).forEach(function (c) { c.addEventListener('click', function () { amount.value = c.dataset.xpChip; paint(); }); });
    xp.addEventListener('submit', function (e) {
      if (mode() === 'set') amount.value = level.value;
      if (target() === score) { e.preventDefault(); out.innerHTML = '<span>Nothing would change. Pick an amount or a different rank.</span>'; }
    });
    paint();
  }

  /* ---------- hide / delete: ask for a reason that is sent to the author ---------- */
  $$('[data-reason-form]').forEach(function (f) {
    f.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-ask]');
      if (!b) return;
      e.preventDefault();
      var why = window.prompt(b.dataset.ask, '');
      if (why === null) return;   // cancelled
      var field = f.elements.reason || f.elements.note;
      if (field) field.value = why.trim();
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = b.name; h.value = b.value;
      f.appendChild(h);
      f.submit();
    });
  });

  /* ---------- warning presets ---------- */
  $$('[data-fill-warn]').forEach(function (b) {
    b.addEventListener('click', function () { var t = b.form.elements.note; t.value = b.dataset.fillWarn; t.focus(); });
  });

  /* ---------- scam case: ban length only for "Ban the seller" ---------- */
  var len = $('[data-ban-len]');
  if (len) {
    var sync = function () { var c = $('input[name="outcome"]:checked'); len.hidden = !c || c.value !== 'ban'; };
    $$('[data-outcome]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
  }
})();
