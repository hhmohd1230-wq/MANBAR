<?php $title = 'MANBAR'; $f = fn($n) => number_format($n); ?>
<header class="pub-nav">
  <a class="brand" href="<?= e(url('')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="42" height="42"><span>MAN<b>BAR</b><small>منبر · Student platform</small></span></a>
  <div class="row"><a class="btn btn-ghost hide-s" href="#modules">Features</a><a class="btn btn-ghost hide-s" href="#compare">Why MANBAR</a><a class="btn btn-primary" href="<?= e(url('login')) ?>">Sign in <?= icon('arrow-right', 16) ?></a></div>
</header>

<section class="hero">
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
        <div><b><?= $f($stats['students']) ?></b><span>Students & teachers</span></div>
        <div><b><?= $f($stats['posts']) ?></b><span>Posts shared</span></div>
        <div><b><?= $f($stats['projects']) ?></b><span>Active projects</span></div>
        <div><b><?= $f($stats['courses']) ?></b><span>Courses</span></div>
      </div>
    </div>
    <div class="mock" aria-hidden="true">
      <div class="m m4 tone-amber"><span class="type-badge"><?= icon('bulb', 14) ?> Idea</span> 24 reactions</div>
      <div class="m m1 tone-amber">
        <div class="row"><span class="avatar" style="width:42px;height:42px;background:hsl(150 45% 42%)">YA</span><div><b>Yaman AlNasri</b><div class="xs muted">Software Engineering · 2h ago</div></div><span class="type-badge" style="margin-left:auto"><?= icon('bulb', 14) ?> Idea</span></div>
        <div class="post-title" style="font-size:17px">A study-buddy matcher for finals week 📚</div>
        <p class="small muted" style="margin:0 0 10px">Match students by course, schedule and learning style. Looking for a UI designer and a backend dev to build the MVP!</p>
        <div class="row"><span class="chip">#edtech</span><span class="chip">#web</span><span class="react-stack" style="margin-left:auto"><span>💡</span><span>💚</span><span>🎉</span></span><b class="small">24</b></div>
      </div>
      <div class="m m2">
        <div class="row between"><b>Campus Hackathon App</b><span class="status-pill st-open">Open</span></div>
        <div class="progress" style="margin:12px 0 8px"><i style="width:68%"></i></div>
        <div class="row between small muted"><span>12 of 18 tasks done</span><span class="team-avatars"><span class="avatar" style="width:26px;height:26px;background:hsl(140 45% 42%);font-size:10px">RA</span><span class="avatar" style="width:26px;height:26px;background:hsl(170 45% 42%);font-size:10px">GA</span><span class="avatar" style="width:26px;height:26px;background:hsl(120 45% 42%);font-size:10px">TA</span></span></div>
      </div>
      <div class="m m3">
        <div class="row"><div class="badge-ico tone-amber" style="width:46px;height:46px;margin:0;border-radius:15px"><?= icon('trophy', 24) ?></div><div><b>Idea Machine</b><div class="xs muted">Badge unlocked · +25 pts</div></div></div>
      </div>
      <div class="m m5"><span class="badge-ico tone-green" style="width:40px;height:40px;margin:0;border-radius:13px"><?= icon('sparkles', 20) ?></span><div><b class="small">AI assistant</b><div class="xs muted">“Post this as a Team request”</div></div></div>
    </div>
  </div>
</section>

<section class="sec" id="modules"><div class="sec-in">
  <div class="sec-head"><div class="eyebrow">Everything in one place</div><h2>Eight modules, one student community</h2><p>From the first spark of an idea to a finished project and a portfolio you can show to employers.</p></div>
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
      <div class="feat tone-<?= $tone ?>"><div class="feat-ico"><?= icon($ic, 28) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
    <?php endforeach ?>
  </div>
</div></section>

<section class="sec alt"><div class="sec-in">
  <div class="sec-head"><h2>Up and running in a minute</h2><p>No passwords to remember — your university Google account is your key.</p></div>
  <div class="steps">
    <div class="step"><h3>Sign in with Google</h3><p class="muted">Use your <b>@aau.ac.ae</b> account. Only verified university members get in.</p></div>
    <div class="step"><h3>Your profile fills itself</h3><p class="muted">Your student number (like <span class="code">202020280</span>) is matched with the university roster to pre-fill your name and major.</p></div>
    <div class="step"><h3>Share, build, learn</h3><p class="muted">Post your first idea, join a project, enroll in a course — and watch your portfolio grow.</p></div>
  </div>
</div></section>

<section class="sec" id="compare"><div class="sec-in">
  <div class="sec-head"><div class="eyebrow">The gap we fill</div><h2>No other platform does it all — on campus</h2><p>LinkedIn, Fiverr, Discord and Moodle each cover a piece. MANBAR brings them together for students.</p></div>
  <div style="overflow-x:auto"><table class="compare">
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

<section class="cta">
  <h2>Ready to put your ideas on the map?</h2>
  <p>Join MANBAR with your university account and start building with your campus today.</p>
  <a class="btn btn-white btn-lg" href="<?= e(url('login')) ?>">Sign in with your university email <?= icon('arrow-right', 18) ?></a>
</section>
<footer class="pub-foot">© <?= date('Y') ?> MANBAR — Capstone project, College of Engineering, Al Ain University. Built by Yaman AlNasri, Tamim Alzein, Ghaith Alsalim, Muhammad Toufeeq &amp; Rami Albaini.</footer>
