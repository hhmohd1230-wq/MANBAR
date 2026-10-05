<?php
$title = 'Check your email';
$cinema = 'login';
$code = $otp['dev_code'] ?? null;
?>
<canvas class="c-field" id="cField" aria-hidden="true"></canvas>
<div class="c-auth">
  <?php partial('auth_art') ?>
  <section class="c-auth-main tone-paper">
    <div class="c-auth-box c-focus" style="--i:2">
      <h2><?= $otp['reset'] ? 'Reset your password' : 'Check your email' ?></h2>
      <p>We sent a 6-digit code to <b><?= e($otp['email']) ?></b>. It expires in <?= LOGIN_CODE_MINUTES ?> minutes.</p>

      <?php if ($code): ?>
        <div class="c-setup c-devcode">
          <b><?= icon('info', 16) ?> Test mode: email isn’t connected yet</b>
          <p>Here is the code that would have been emailed (also saved in <code>storage/mail.log</code>):</p>
          <code class="c-setup-uri c-devcode-n"><?= e(substr($code, 0, 3) . ' ' . substr($code, 3)) ?></code>
        </div>
      <?php endif ?>

      <form method="post" action="<?= e(url('auth/email/verify')) ?>" class="c-otp-form" data-otp-form>
        <?= csrf_field() ?>
        <label class="c-mail-l" for="otp">Sign-in code</label>
        <input class="c-otp" id="otp" name="code" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" placeholder="••••••" required autofocus data-otp>
        <button class="c-btn c-btn-lg c-btn-block" type="submit">Verify and continue <?= icon('arrow-right', 16) ?></button>
      </form>

      <div class="c-otp-foot">
        <form method="post" action="<?= e(url('auth/email/code')) ?>">
          <?= csrf_field() ?>
          <button class="c-textbtn" type="submit" data-countdown="<?= (int) $wait ?>"<?= $wait ? ' disabled' : '' ?>>Resend code<span data-countdown-label><?= $wait ? " in {$wait}s" : '' ?></span></button>
        </form>
        <a class="c-textbtn" href="<?= e(url('login')) ?>">Use a different email</a>
      </div>
      <p class="c-terms">Can’t find it? Check your spam or junk folder. Codes only work once.</p>
    </div>
  </section>
</div>
