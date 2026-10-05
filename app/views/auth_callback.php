<?php
/** Google returns here with #id_token=…&state=… in the URL fragment. We post it back to the server (same-origin + CSRF). */
$title = 'Signing in';
$cinema = 'login';
?>
<canvas class="c-field" id="cField" aria-hidden="true"></canvas>
<main class="c-cb">
  <div class="c-cb-card c-focus" role="status" aria-live="polite">
    <div class="c-auth-mark" aria-hidden="true">
      <svg viewBox="0 0 200 200"><circle class="t1" cx="100" cy="100" r="97" pathLength="120"/><circle class="t2 spin" cx="100" cy="100" r="88" pathLength="100"/></svg>
      <img src="<?= e(asset('img/logo.svg')) ?>" alt="">
    </div>
    <h1 id="cbTitle">Signing you in…</h1>
    <p id="cbText">Google confirmed your account. Setting up your MANBAR profile.</p>
    <a class="c-btn" id="cbBack" href="<?= e(url('login')) ?>" hidden>Back to sign in</a>
  </div>
</main>
<script>
(() => {
  const h = new URLSearchParams(location.hash.slice(1));
  history.replaceState(null, '', location.pathname);   // drop the token from the address bar
  const title = document.getElementById('cbTitle'), text = document.getElementById('cbText'), back = document.getElementById('cbBack');
  const fail = msg => { title.textContent = 'We couldn’t sign you in'; text.textContent = msg; back.hidden = false; document.querySelector('.t2').classList.remove('spin'); };
  if (h.get('error')) return fail(h.get('error') === 'access_denied' ? 'Google sign-in was cancelled.' : 'Google returned an error: ' + h.get('error') + '.');
  if (!h.get('id_token')) return fail('Google did not return a sign-in token. Please try again.');
  const meta = n => document.querySelector(`meta[name="${n}"]`).content;
  fetch(meta('base') + '/auth/google/finish', {
    method: 'POST',
    headers: { 'Accept': 'application/json', 'X-CSRF-Token': meta('csrf'), 'X-Requested-With': 'fetch' },
    body: new URLSearchParams({ id_token: h.get('id_token'), state: h.get('state') || '' }),
  }).then(r => r.json()).then(d => d.ok ? location.replace(d.redirect) : fail(d.error || 'Please try again.'))
    .catch(() => fail('Network problem. Please try again.'));
})();
</script>
