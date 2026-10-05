<?php
$title = $reset ? 'Set a new password' : 'Set a password';
$cinema = 'login';
?>
<canvas class="c-field" id="cField" aria-hidden="true"></canvas>
<div class="c-auth">
  <?php partial('auth_art') ?>
  <section class="c-auth-main tone-paper">
    <div class="c-auth-box c-focus" style="--i:2">
      <h2><?= $reset ? 'Set a new password' : ($has ? 'Change your password' : 'Set a password') ?></h2>
      <p><?php if ($reset): ?>Choose a new password for <b><?= e($u['email']) ?></b>.<?php else: ?>Next time, type <b><?= e($u['email']) ?></b> and sign in with this password, with no code to wait for. If you ever forget it, a code still works.<?php endif ?></p>

      <form method="post" action="<?= e(url('auth/password')) ?>" class="c-otp-form">
        <?= csrf_field() ?>
        <input type="hidden" name="username" value="<?= e($u['email']) ?>" autocomplete="username">
        <label class="c-mail-l" for="pw1">New password</label>
        <div class="c-pw">
          <input class="c-pw-in" id="pw1" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="128" required autofocus aria-describedby="pwHint" data-strength>
          <button class="c-pw-eye" type="button" data-reveal="pw1" aria-label="Show password" aria-pressed="false"><?= icon('eye', 18) ?></button>
        </div>
        <div class="c-strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
        <p class="c-hint c-mail-note" id="pwHint">At least 8 characters. A short sentence is easy to remember and hard to guess.</p>
        <label class="c-mail-l" for="pw2">Repeat password</label>
        <div class="c-pw">
          <input class="c-pw-in" id="pw2" name="password2" type="password" autocomplete="new-password" minlength="8" maxlength="128" required>
        </div>
        <button class="c-btn c-btn-lg c-btn-block" type="submit">Save password</button>
      </form>

      <form method="post" action="<?= e(url('auth/password/skip')) ?>" class="c-otp-foot">
        <?= csrf_field() ?>
        <button class="c-textbtn" type="submit"><?= $has || $reset ? 'Cancel' : 'Skip for now: I’ll use a code each time' ?></button>
      </form>
    </div>
  </section>
</div>
