<?php
$admin = $admin ?? false;
$title = $admin ? 'Admin console' : 'Enter your password';
$cinema = 'login';
?>
<canvas class="c-field" id="cField" aria-hidden="true"></canvas>
<div class="c-auth">
  <?php partial('auth_art') ?>
  <section class="c-auth-main tone-paper">
    <div class="c-auth-box c-focus" style="--i:2">
      <?php if ($admin): ?>
        <h2>Admin console</h2>
        <p>Enter the administrator password to open the dashboard.</p>
      <?php else: ?>
        <h2>Welcome back</h2>
        <p>Sign in as <b><?= e($email) ?></b>.</p>
      <?php endif ?>

      <form method="post" action="<?= e(url($admin ? 'auth/admin' : 'auth/email/password')) ?>" class="c-otp-form">
        <?= csrf_field() ?>
        <?php if (!$admin): ?><input type="hidden" name="username" value="<?= e($email) ?>" autocomplete="username"><?php endif ?>
        <label class="c-mail-l" for="pw">Password</label>
        <div class="c-pw">
          <input class="c-pw-in" id="pw" name="password" type="password" autocomplete="current-password" required autofocus>
          <button class="c-pw-eye" type="button" data-reveal="pw" aria-label="Show password" aria-pressed="false"><?= icon('eye', 18) ?></button>
        </div>
        <button class="c-btn c-btn-lg c-btn-block" type="submit"><?= $admin ? icon('shield', 16) . ' Open dashboard' : 'Sign in ' . icon('arrow-right', 16) ?></button>
      </form>

      <?php if (!$admin): ?>
        <div class="c-otp-foot">
          <form method="post" action="<?= e(url('auth/email/code')) ?>"><?= csrf_field() ?><button class="c-textbtn" type="submit">Email me a code instead</button></form>
          <form method="post" action="<?= e(url('auth/email/code')) ?>"><?= csrf_field() ?><input type="hidden" name="reset" value="1"><button class="c-textbtn" type="submit">Forgot password?</button></form>
        </div>
        <p class="c-terms"><a href="<?= e(url('login')) ?>">Not you? Use a different email</a></p>
      <?php else: ?>
        <p class="c-terms"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
      <?php endif ?>
    </div>
  </section>
</div>
