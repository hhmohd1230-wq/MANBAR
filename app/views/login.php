<?php
$title = 'Sign in';
$cinema = 'login';
$logo = e(asset('img/logo.svg'));
$gmark = '<svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.9 2.4 30.4 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.7 7l7.3 5.7c4.3-4 6.8-9.9 6.8-17.2z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-2.9-.8-4.7s.3-3.3.8-4.7l-7.9-6.1C.9 16.5 0 20.1 0 24s.9 7.5 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.3-5.7c-2 1.4-4.7 2.3-8.6 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>';
?>
<canvas class="c-field" id="cField" aria-hidden="true"></canvas>
<div class="c-auth">
  <?php partial('auth_art') ?>

  <section class="c-auth-main tone-paper">
    <div class="c-auth-box c-focus" style="--i:2">
      <h2>Sign in or join</h2>
      <p>Use your university Google account. First time here? Your account is created automatically and your profile fills itself.</p>

      <form method="post" action="<?= e(url('auth/google/start')) ?>" class="c-g-form">
        <?= csrf_field() ?>
        <button class="c-gbtn" type="submit" <?= $client_id ? '' : 'disabled aria-describedby="gHint"' ?>><?= $gmark ?> Continue with Google</button>
      </form>

      <div class="c-or">or use your university email</div>

      <form method="post" action="<?= e(url('auth/email/start')) ?>" class="c-mail" id="cMail" novalidate>
        <?= csrf_field() ?>
        <label class="c-mail-l" for="mailLocal">University email</label>
        <div class="c-mail-row">
          <div class="c-mail-field">
            <input class="c-mail-in" id="mailLocal" name="email" inputmode="email" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="202020280 or name.surname" required aria-describedby="mailRole">
            <?php if (count($unis) > 1): ?>
              <label class="sr" for="mailDom">University</label>
              <select class="c-mail-dom" id="mailDom" name="domain"><?php foreach ($unis as $u): ?><option value="<?= e($u['domain']) ?>">@<?= e($u['domain']) ?></option><?php endforeach ?></select>
            <?php else: ?>
              <span class="c-mail-dom" id="mailDom" data-domain="<?= e($unis[0]['domain'] ?? '') ?>">@<?= e($unis[0]['domain'] ?? '') ?></span>
              <input type="hidden" name="domain" value="<?= e($unis[0]['domain'] ?? '') ?>">
            <?php endif ?>
          </div>
          <button class="c-btn" type="submit">Continue <?= icon('arrow-right', 16) ?></button>
        </div>
        <p class="c-role" id="mailRole" aria-live="polite"><span class="c-role-chip">Student number</span> joins as a <b>student</b> and fills your profile from the roster · <span class="c-role-chip">name.surname</span> joins as a <b>teacher</b></p>
        <p class="c-hint c-mail-note">We’ll email you a 6-digit code. After that you can set a password so you don’t need a code every time.</p>
      </form>

      <?php if (!$client_id): ?>
        <div class="c-setup" id="gHint">
          <b><?= icon('info', 16) ?> Google sign-in isn’t switched on yet</b>
          <p>Add your Google OAuth Client ID to <code>config/config.local.php</code> and register this redirect URI in Google Cloud:</p>
          <code class="c-setup-uri"><?= e($redirect_uri) ?></code>
        </div>
      <?php else: ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <div id="g_id_onload" data-client_id="<?= e($client_id) ?>" data-callback="manbarOneTap" data-nonce="<?= e($onetap_nonce) ?>" data-auto_prompt="true" data-auto_select="false" data-cancel_on_tap_outside="false" data-itp_support="true" data-use_fedcm_for_prompt="true" data-context="signin" <?= count($unis) === 1 ? 'data-hd="' . e($unis[0]['domain']) . '"' : '' ?>></div>
      <?php endif ?>

      <p class="c-terms">By continuing you agree to use MANBAR respectfully. Only verified university accounts can join.</p>
    </div>
  </section>
</div>
