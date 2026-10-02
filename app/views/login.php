<?php
$title = 'Sign in';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$loginUri = (cfg('base_url') ?: $scheme . '://' . $_SERVER['HTTP_HOST']) . url('auth/google');
?>
<div class="auth">
  <section class="auth-art">
    <a class="brand" href="<?= e(url('')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="46" height="46"><span>MAN<b>BAR</b><small>منبر · Student platform</small></span></a>
    <div>
      <h2>The stage for every student idea.</h2>
      <p>One trusted place for ideas, teams, services, courses and mentors at your university.</p>
      <div class="pts"><span><?= icon('bulb', 16) ?> Ideas</span><span><?= icon('rocket', 16) ?> Projects</span><span><?= icon('store', 16) ?> Marketplace</span><span><?= icon('book', 16) ?> Courses</span><span><?= icon('compass', 16) ?> Mentors</span></div>
    </div>
    <div class="small" style="opacity:.8">Al Ain University · Capstone project</div>
  </section>
  <section class="auth-main">
    <div class="auth-box">
      <h1>Welcome 👋</h1>
      <p class="muted">Sign in with your university Google account (<b><?= e($domains) ?></b>). Your student number is matched with the university roster to fill your profile for you.</p>

      <?php if ($client_id): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <div id="g_id_onload" data-client_id="<?= e($client_id) ?>" data-ux_mode="redirect" data-login_uri="<?= e($loginUri) ?>" data-auto_prompt="false"></div>
        <div class="g-btn-wrap"><div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline" data-text="signin_with" data-size="large" data-logo_alignment="left" data-width="360"></div></div>
      <?php else: ?>
        <div class="g-btn-wrap"><button class="g-fake" type="button" disabled style="opacity:.6;cursor:not-allowed"><svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.9 2.4 30.4 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.200 5.300-4.700 7l7.300 5.700c4.300-4 6.800-9.900 6.800-17.200z"/><path fill="#FBBC05" d="M10.500 28.700c-.5-1.400-.8-2.900-.8-4.700s.3-3.300.8-4.700l-7.900-6.100C.9 16.500 0 20.100 0 24s.9 7.500 2.600 10.800l7.900-6.100z"/><path fill="#34A853" d="M24 48c6.500 0 11.900-2.100 15.900-5.800l-7.300-5.700c-2 1.400-4.700 2.300-8.600 2.300-6.300 0-11.600-4.100-13.500-9.800l-7.900 6.100C6.500 42.600 14.600 48 24 48z"/></svg> Continue with Google</button></div>
        <p class="hint tc">Google Sign-In is not configured yet. Set <span class="code">GOOGLE_CLIENT_ID</span> (see README) to switch it on.</p>
      <?php endif ?>

      <?php if (cfg('dev_login')): ?>
        <div class="or">demo mode</div>
        <form class="devbox" method="post" action="<?= e(url('auth/dev')) ?>">
          <?= csrf_field() ?>
          <b class="small row"><?= icon('lock', 16) ?> Local demo login <span class="chip sm">dev only</span></b>
          <p class="hint" style="margin-top:6px">Try the student-number flow: type <span class="code">202020280</span> and your name and details are filled in from the roster. Turn this off in production.</p>
          <div class="field" style="margin:12px 0 10px"><input class="input" name="identifier" placeholder="Student number or @aau.ac.ae email" autocomplete="off" required></div>
          <button class="btn btn-primary btn-block" type="submit">Continue <?= icon('arrow-right', 16) ?></button>
          <?php if ($demo): ?>
            <div class="small muted" style="margin-top:14px;font-weight:600">…or jump in as a demo account</div>
            <div class="demo-list">
              <?php foreach ($demo as $d): ?>
                <button type="submit" name="identifier" value="<?= e($d['email']) ?>" formnovalidate><?= avatar($d, 30) ?><span class="grow"><b class="small"><?= e($d['full_name']) ?></b><br><span class="xs muted"><?= e($d['email']) ?></span></span><span class="role-pill <?= e($d['role']) ?>"><?= e(role_label($d['role'])) ?></span></button>
              <?php endforeach ?>
            </div>
          <?php endif ?>
        </form>
      <?php endif ?>
      <p class="hint tc" style="margin-top:22px">By continuing you agree to use MANBAR respectfully. Only verified university accounts can join.</p>
    </div>
  </section>
</div>
