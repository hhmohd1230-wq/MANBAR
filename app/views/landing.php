<?php $title = 'MANBAR'; $f = fn($n) => number_format($n); ?>
<header class="pub-nav">
  <a class="brand" href="<?= e(url('')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="42" height="42"><span>MAN<b>BAR</b><small>منبر · Student platform</small></span></a>
  <div class="row"><a class="btn btn-ghost hide-s" href="#modules">Features</a><a class="btn btn-ghost hide-s" href="#compare">Why MANBAR</a><a class="btn btn-primary" href="<?= e(url('login')) ?>">Sign in <?= icon('arrow-right', 16) ?></a></div>
</header>

<section class="hero">
  <div class="hero-anim" aria-hidden="true"><span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span><span class="sweep"></span><span class="grid-dots"></span><i class="leaf" style="--x:40%;--t:14s;--d:-12s;--s:1.2"></i><i class="leaf" style="--x:13%;--t:14s;--d:-0s;--s:1.2"></i><i class="leaf dot" style="--x:9%;--t:16s;--d:-16s;--s:1.4"></i><i class="leaf dot" style="--x:37%;--t:15s;--d:-3s;--s:1"></i><i class="leaf" style="--x:5%;--t:23s;--d:-8s;--s:1"></i><i class="leaf" style="--x:23%;--t:17s;--d:-9s;--s:1"></i><i class="leaf" style="--x:79%;--t:18s;--d:-12s;--s:1.4"></i><i class="leaf" style="--x:24%;--t:16s;--d:-15s;--s:1"></i><i class="leaf" style="--x:72%;--t:17s;--d:-0s;--s:1"></i><i class="leaf dot" style="--x:67%;--t:16s;--d:-13s;--s:1.2"></i><i class="leaf dot" style="--x:57%;--t:20s;--d:-5s;--s:0.8"></i><i class="leaf dot" style="--x:35%;--t:13s;--d:-2s;--s:0.6"></i><i class="leaf ring" style="--x:82%;--t:17s;--d:-16s;--s:1.4"></i><i class="leaf ring" style="--x:91%;--t:18s;--d:-4s;--s:0.8"></i><i class="leaf" style="--x:54%;--t:16s;--d:-20s;--s:1.2"></i><i class="leaf dot" style="--x:25%;--t:18s;--d:-13s;--s:1.4"></i><i class="leaf dot" style="--x:83%;--t:21s;--d:-6s;--s:1"></i><i class="leaf" style="--x:9%;--t:24s;--d:-7s;--s:1"></i></div>
  <div class="hero-grid">
    <div>
      <div class="eyebrow"><i>New</i> Built for Al Ain University students</div>
      <h1>Your campus, <em>one platform</em> to build what’s next.</h1>
      <p class="lead">Share ideas, find teammates, run projects, offer services, learn new skills and meet mentors — all in a trusted space for verified university students.</p>
      <div class="row wrap">
        <a class="btn btn-primary btn-lg" href="<?= e(url('login')) ?>"><?= icon('user', 20) ?> Sign in with your @aau.ac.ae</a>
        <a class="btn btn-white btn-lg" href="#modules">Explore features</a>
      </div>
      <div class="hero-stats">
        <div><b data-count=\"<?= (int) $stats['students'] ?>\"><?= $f($stats['students']) ?></b><span>Students & teachers</span></div>
        <div><b data-count=\"<?= (int) $stats['posts'] ?>\"><?= $f($stats['posts']) ?></b><span>Posts shared</span></div>
        <div><b data-count=\"<?= (int) $stats['projects'] ?>\"><?= $f($stats['projects']) ?></b><span>Active projects</span></div>
        <div><b data-count=\"<?= (int) $stats['courses'] ?>\"><?= $f($stats['courses']) ?></b><span>Courses</span></div>
      </div>
    </div>
    <?php partial('hero_devices') ?>
  </div>
</section>

<section class="sec" id="modules"><div class="sec-in">
  <div class="sec-head reveal"><div class="eyebrow">Everything in one place</div><h2>Eight modules, one student community</h2><p>From the first spark of an idea to a finished project and a portfolio you can show to employers.</p></div>
  <div class="feat-grid">
    <?php foreach ([
      ['bulb', 'amber', 'Idea feed', 'Post ideas, react, comment and share. Get feedback from classmates and teachers in seconds.'],
      ['rocket', 'green', 'Project collaboration', 'Turn ideas into projects, accept applicants, assign tasks on a board and chat with your team.'],
      ['users', 'blue', 'People directory', 'Find students by skill, major or interest and follow the people who inspire you.'],
      ['store', 'orange', 'Student marketplace', 'Sell and buy services — design, coding, tutoring, writing — with ratings and reviews.'],
      ['book', 'teal', 'Learning center', 'Teachers publish courses and lessons; students enroll and track their progress.'],
      ['compass', 'violet', 'Mentorship', 'Request a mentor from faculty, alumni and experts and schedule real sessions.'],
      ['trophy', 'rose', 'Achievements', 'Earn points, badges and levels for contributing — and climb the leaderboard.'],
      ['sparkles', 'green', 'AI assistant', 'Auto-corrects your writing and guides you to the right place to post your idea.'],
    ] as [$ic, $tone, $t, $d]): ?>
      <div class="feat reveal tone-<?= $tone ?>"><div class="feat-ico"><?= icon($ic, 28) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
    <?php endforeach ?>
  </div>
</div></section>

<section class="sec alt"><div class="sec-in">
  <div class="sec-head reveal"><h2>Up and running in a minute</h2><p>No passwords to remember — your university Google account is your key.</p></div>
  <div class="steps">
    <div class="step reveal"><h3>Sign in with Google</h3><p class="muted">Use your <b>@aau.ac.ae</b> account. Only verified university members get in.</p></div>
    <div class="step reveal"><h3>Your profile fills itself</h3><p class="muted">Your student number (like <span class="code">202020280</span>) is matched with the university roster to pre-fill your name and major.</p></div>
    <div class="step reveal"><h3>Share, build, learn</h3><p class="muted">Post your first idea, join a project, enroll in a course — and watch your portfolio grow.</p></div>
  </div>
</div></section>

<section class="sec" id="compare"><div class="sec-in">
  <div class="sec-head reveal"><div class="eyebrow">The gap we fill</div><h2>No other platform does it all — on campus</h2><p>LinkedIn, Fiverr, Discord and Moodle each cover a piece. MANBAR brings them together for students.</p></div>
  <div class="reveal" style="overflow-x:auto"><table class="compare">
    <thead><tr><th>Feature</th><th class="us">MANBAR</th><th>LinkedIn</th><th>Fiverr</th><th>Discord</th><th>Moodle</th></tr></thead>
    <tbody>
    <?php
    $Y = '<span class="y">' . icon('check-circle', 20) . '</span>'; $N = '<span class="n">' . icon('x', 18) . '</span>'; $L = '<span class="l">Limited</span>';
    foreach ([['Idea sharing', 'Y', 'L', 'N', 'L', 'N'], ['Project collaboration', 'Y', 'N', 'N', 'L', 'N'], ['Campus-only access', 'Y', 'N', 'N', 'N', 'Y'], ['Student marketplace', 'Y', 'N', 'Y', 'N', 'N'], ['Mentorship system', 'Y', 'L', 'N', 'N', 'N'], ['Learning center', 'Y', 'N', 'N', 'N', 'Y'], ['Portfolio profiles', 'Y', 'Y', 'Y', 'N', 'L'], ['Achievements system', 'Y', 'N', 'N', 'N', 'N'], ['Skill search', 'Y', 'L', 'Y', 'N', 'N']] as $row) {
        echo '<tr><td>' . e(array_shift($row)) . '</td>';
        foreach ($row as $i => $v) echo '<td class="' . ($i === 0 ? 'us' : '') . '">' . ['Y' => $Y, 'N' => $N, 'L' => $L][$v] . '</td>';
        echo '</tr>';
    } ?>
    </tbody></table></div>
</div></section>

<section class="cta reveal">
  <h2>Ready to put your ideas on the map?</h2>
  <p>Join MANBAR with your university account and start building with your campus today.</p>
  <a class="btn btn-white btn-lg" href="<?= e(url('login')) ?>">Sign in with your university email <?= icon('arrow-right', 18) ?></a>
</section>
<footer class="pub-foot">© <?= date('Y') ?> MANBAR — Capstone project, College of Engineering, Al Ain University. Built by Yaman AlNasri, Tamim Alzein, Ghaith Alsalim, Muhammad Toufeeq &amp; Rami Albaini.</footer>
