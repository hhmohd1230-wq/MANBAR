<?php /** Left half of the sign-in screens: brand, ring mark, promise. */ $logo = e(asset('img/logo.svg')); ?>
  <section class="c-auth-art" aria-label="About MANBAR">
    <a class="c-brand" href="<?= e(url('')) ?>" aria-label="MANBAR home"><img id="cBrandMark" src="<?= $logo ?>" alt="" width="34" height="34"><span class="c-brand-word">MANBAR</span><span class="c-brand-ar" lang="ar">منبر</span></a>
    <div class="c-auth-hero">
      <div class="c-auth-mark c-focus" style="--i:0" aria-hidden="true">
        <svg viewBox="0 0 200 200"><circle class="t1" cx="100" cy="100" r="97" pathLength="120"/><circle class="t2" cx="100" cy="100" r="88" pathLength="100"/></svg>
        <img src="<?= $logo ?>" alt="">
      </div>
      <h1 class="c-focus" style="--i:1">The stage for every student idea.</h1>
      <p class="c-focus" style="--i:2">One trusted place for ideas, teams, services, courses and mentors at your university.</p>
      <div class="c-auth-mods c-focus" style="--i:3"><span><?= icon('bulb', 14) ?> Ideas</span><span><?= icon('rocket', 14) ?> Projects</span><span><?= icon('store', 14) ?> Marketplace</span><span><?= icon('book', 14) ?> Courses</span><span><?= icon('compass', 14) ?> Mentors</span></div>
    </div>
    <div class="c-auth-foot">Al Ain University · Capstone project</div>
  </section>
