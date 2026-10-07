<?php
$title = 'MANBAR';
$cinema = 'landing';
$n = fn(string $k) => (int) ($stats[$k] ?? 0);
$logo = e(asset('img/logo.svg'));
$login = e(url('login'));
?>
<a class="c-skip" href="#main">Skip to content</a>

<div class="c-intro" id="cIntro" aria-hidden="true">
  <div class="c-mark" id="cMark">
    <?php partial('logo_motion') ?>
    <span class="c-mark-num" id="cRingNum">000</span>
  </div>
  <div class="c-intro-cap"><span>MANBAR</span><span lang="ar">منبر</span><span id="cIntroState">Loading campus</span></div>
</div>

<canvas class="c-field" id="cField" aria-hidden="true"></canvas>

<header class="c-nav" id="cNav">
  <a class="c-brand" href="<?= e(url('')) ?>" aria-label="MANBAR home"><img id="cBrandMark" src="<?= $logo ?>" alt="" width="34" height="34"><span class="c-brand-word">MANBAR</span><span class="c-brand-ar" lang="ar">منبر</span></a>
  <nav class="c-nav-links" aria-label="Page sections"><a href="#journey">How it works</a><a href="#modules">Modules</a><a href="#access">Access</a><a href="#compare">Compare</a></nav>
  <a class="c-btn c-btn-sm" href="<?= $login ?>">Sign in</a>
</header>

<main id="main">
  <section class="c-hero" data-tone="void" aria-labelledby="heroTitle">
    <div class="c-hero-copy">
      <h1 id="heroTitle" class="c-hero-title c-focus" style="--i:0"><img id="cHeroLogo" src="<?= $logo ?>" alt="" width="120" height="120"><span>MANBAR</span></h1>
      <p class="c-tagline c-focus" style="--i:1">The stage for every <em>student idea.</em></p>
      <p class="c-lead c-focus" style="--i:2">Post an idea, find your team, run the project, sell a skill and learn from your teachers. One platform, open only to verified Al Ain University students.</p>
      <div class="c-actions c-focus" style="--i:3">
        <a class="c-btn c-btn-lg" href="<?= $login ?>"><?= icon('lock', 18) ?> Sign in with @aau.ac.ae</a>
        <a class="c-link" href="#journey">Watch an idea travel <?= icon('arrow-right', 16) ?></a>
      </div>
    </div>
    <?php partial('hero_devices') ?>
    <dl class="c-readout c-focus" style="--i:4" aria-label="Live numbers from the MANBAR database">
      <div class="c-readout-live"><dt>Live</dt><dd>AAU campus</dd></div>
      <div><dt>Members</dt><dd data-count="<?= $n('students') ?>"><?= number_format($n('students')) ?></dd></div>
      <div><dt>Posts</dt><dd data-count="<?= $n('posts') ?>"><?= number_format($n('posts')) ?></dd></div>
      <div><dt>Active projects</dt><dd data-count="<?= $n('projects') ?>"><?= number_format($n('projects')) ?></dd></div>
      <div><dt>Courses</dt><dd data-count="<?= $n('courses') ?>"><?= number_format($n('courses')) ?></dd></div>
    </dl>
  </section>

  <section class="c-journey" id="journey" data-tone="forest" aria-labelledby="journeyTitle">
    <div class="c-wrap">
      <header class="c-head">
        <h2 id="journeyTitle">One idea, all the way through.</h2>
        <p>Follow Mariam’s study-buddy idea from a first post to a finished project on her portfolio. Every step below is a real MANBAR screen.</p>
      </header>
      <div class="c-journey-grid">
        <ol class="c-steps">
          <li class="c-step is-on" data-step="1"><h3>She posts it to the feed.</h3><p>A rough idea, a few tags, done in a minute. Classmates and teachers react and comment the same day.</p></li>
          <li class="c-step" data-step="2"><h3>The assistant cleans it up and points the way.</h3><p>Spelling and grammar are fixed before it goes live, and MANBAR notices she is really asking for teammates.</p></li>
          <li class="c-step" data-step="3"><h3>The right people apply.</h3><p>Students with the skills she listed apply with a short note. She accepts Omar for the backend and Fatima for marketing.</p></li>
          <li class="c-step" data-step="4"><h3>The team works on one board.</h3><p>Tasks move from To do to Done, the team chats in the project room, and public updates keep followers in the loop.</p></li>
          <li class="c-step" data-step="5"><h3>The result stays on her profile.</h3><p>The finished project, the team and the badges she earned become a portfolio she can show employers.</p></li>
        </ol>
        <div class="c-scene" aria-hidden="true">
          <div class="c-scene-glass" id="cScene" data-step="1">
            <div class="c-scene-bar"><img src="<?= $logo ?>" alt=""><span>manbar · aau</span><span class="c-scene-n">Step <b id="cStepN">1</b> of 5</span></div>

            <div class="c-panel" data-panel="1">
              <div class="c-post">
                <div class="c-post-h"><span class="c-av" style="--h:152">MA</span><span><b>Mariam Al Mansoori</b><small>Software Engineering · just now</small></span><span class="c-tag">Idea</span></div>
                <b class="c-post-t">A study-buddy matcher for finals week</b>
                <p data-js="body">Match students for revision by course, free time and study style. I have a rough UI in Figma. Looking for a backend dev.</p>
                <div class="c-post-tags" data-js="tags"><span>#edtech</span><span>#web</span><span>#design</span></div>
                <div class="c-post-f"><span class="c-reacts" data-js="react"><i>💡</i><i>💚</i><i>🤝</i></span><b data-js="count">24</b><span>7 comments</span></div>
              </div>
              <div class="c-comment" data-js="comment"><span class="c-av sm" style="--h:118">ON</span><p><b>Omar Al Nuaimi</b> Love this. A score from shared courses plus free slots could work. Happy to help with the API.</p></div>
            </div>

            <div class="c-panel" data-panel="2">
              <div class="c-ai">
                <div class="c-ai-draft"><span class="c-ai-k">Draft</span><p class="c-draft" data-js="draft"><span data-t="Looking for a backend dev to "></span><span class="w" data-bad="recieve" data-good="receive"><s>recieve</s> <ins>receive</ins></span><span data-t=" match requests and build the "></span><span class="w" data-bad="sheduling" data-good="scheduling"><s>sheduling</s> <ins>scheduling</ins></span><span data-t=" logic."></span></p></div>
                <div class="c-ai-route" data-js="route">
                  <span class="c-ai-k"><?= icon('sparkles', 14) ?> Assistant</span>
                  <p>This reads like a <b>team request</b>. Post it to <b>Projects</b> so students with Python and SQL skills see it.</p>
                  <div class="c-route"><span>Feed</span><svg viewBox="0 0 80 12"><path d="M2 6h70" /><path d="M66 1l7 5-7 5" /></svg><span class="on" data-js="dest">Projects · Team</span></div>
                  <div class="c-ai-btns"><span class="c-mini on" data-js="move">Move to Projects</span><span class="c-mini">Keep in feed</span></div>
                </div>
              </div>
            </div>

            <div class="c-panel" data-panel="3">
              <div class="c-apply">
                <div class="c-apply-h"><b>Study-Buddy Matcher</b><small><span data-js="seats">3</span> of 4 seats · needs Python, SQL, APIs, UI Design</small></div>
                <div class="c-app on"><span class="c-av" style="--h:118">ON</span><span><b>Omar Al Nuaimi</b><small>Python · SQL · APIs</small></span><span class="c-mini on" data-js="acc"><?= icon('check', 12) ?> Accepted</span></div>
                <div class="c-app on"><span class="c-av" style="--h:96">FK</span><span><b>Fatima Al Kaabi</b><small>Marketing · Copywriting</small></span><span class="c-mini on" data-js="acc"><?= icon('check', 12) ?> Accepted</span></div>
                <div class="c-app"><span class="c-av" style="--h:170">YK</span><span><b>Yousef Khan</b><small>JavaScript · React · Node.js</small></span><span class="c-mini">Review</span></div>
              </div>
            </div>

            <div class="c-panel" data-panel="4">
              <div class="c-board">
                <div class="c-col"><b>To do <i>2</i></b><span>Write the pitch</span><span>User testing with 10 students</span></div>
                <div class="c-col"><b>Doing <i data-js="doing">2</i></b><span>Set up the repository and CI</span><span class="mv" data-js="card">Build the first prototype</span></div>
                <div class="c-col" data-js="done"><b>Done <i data-js="donen">2</i></b><span>Define the MVP scope</span><span>Design the main screens</span></div>
              </div>
              <div class="c-timeline"><span data-js="bar" style="--w:.62"></span><small>Week 4 of 6 · MVP</small></div>
            </div>

            <div class="c-panel" data-panel="5">
              <div class="c-proof">
                <div class="c-proof-h"><span class="c-av lg" style="--h:152">MA</span><span><b>Mariam Al Mansoori</b><small>UI/UX enthusiast · Future product designer</small></span><span class="c-pts" data-js="pts">+120 pts</span></div>
                <div class="c-proof-row" data-js="badges"><span class="c-badge"><?= icon('users', 16) ?> Team Player</span><span class="c-badge"><?= icon('flag', 16) ?> Project Leader</span><span class="c-badge"><?= icon('bulb', 16) ?> Idea Machine</span></div>
                <div class="c-level"><span>Gold rank · 250 XP</span><span>Platinum at 500 XP</span><i><s data-js="lvl" style="--w:.71"></s></i></div>
                <div class="c-proof-item"><b>Study-Buddy Matcher</b><small>Project owner · team of 3 · Python, SQL, UI Design</small></div>
              </div>
            </div>
            <i class="c-cursor" data-js="cursor"><svg viewBox="0 0 24 24"><path d="M5 2.5l14.5 9-6.6 1.4-3.3 6.6z"/></svg></i>
          </div>
          <p class="c-scene-note">Walkthrough uses MANBAR’s demo content.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="c-modules" id="modules" data-tone="paper" aria-labelledby="modTitle">
    <div class="c-wrap">
      <header class="c-head">
        <h2 id="modTitle">Eight modules. One campus account.</h2>
        <p>Everything a student project needs, from the first spark to a portfolio, without juggling four different apps.</p>
      </header>
      <div class="c-bento">
        <article class="c-tile t-feed">
          <h3>Idea feed</h3><p>Post ideas, questions, events and showcases. React, comment, share and save.</p>
          <div class="c-frag c-frag-feed c-rec" id="cRec" aria-hidden="true">
            <span class="c-rec-badge"><i></i>Demo recording <b data-js="clock">00:00</b></span>
            <div class="c-compose">
              <div class="c-compose-in"><span class="c-av sm" style="--h:152">MA</span><span class="c-compose-text" data-js="text">Describe your idea: what problem does it solve, who is it for?</span><span class="c-compose-post" data-js="post"><?= icon('send', 13) ?> Post</span></div>
              <div class="c-compose-types" data-js="types"><?php foreach (POST_TYPES as $k => $pt): if ($pt['staff']) continue; ?><span data-type="<?= e($k) ?>" class="<?= $k === 'idea' ? 'on' : '' ?>"><?= icon($pt['icon'], 13) ?> <?= e($pt['label']) ?></span><?php endforeach ?></div>
            </div>
            <div class="c-post sm" data-js="slot"><div class="c-post-h"><span class="c-av" style="--h:140">KS</span><span><b>Khalid Al Suwaidi</b><small>Team request · 5h</small></span></div><b class="c-post-t">Need 2 teammates for the UAE hackathon</b><p>Flutter and Firebase. Looking for a designer and someone to pitch.</p><div class="c-post-f"><span class="c-reacts"><i>🤝</i><i>👍</i><i>💚</i></span><b>41</b><span>12 comments</span></div></div>
            <div class="c-post sm" data-js="slot"><div class="c-post-h"><span class="c-av" style="--h:110">LH</span><span><b>Dr. Layla Hassan</b><small>Announcement · 1d</small></span></div><b class="c-post-t">AI Club kick-off and research opportunities</b><p>Wednesday 1 pm, Lab B-204. No experience needed.</p><div class="c-post-f"><span class="c-reacts"><i>🎉</i><i>💡</i><i>👍</i></span><b>63</b><span>9 comments</span></div></div>
            <i class="c-cursor" data-js="cursor"><svg viewBox="0 0 24 24"><path d="M5 2.5l14.5 9-6.6 1.4-3.3 6.6z"/></svg></i>
          </div>
        </article>
        <article class="c-tile t-ai">
          <h3>AI assistant</h3><p>Fixes your writing before you post and sends your idea to the right page.</p>
          <div class="c-frag" aria-hidden="true"><div class="c-route sm"><span>“I can design logos”</span><svg viewBox="0 0 80 12"><path d="M2 6h70" /><path d="M66 1l7 5-7 5" /></svg><span class="on">Marketplace</span></div></div>
        </article>
        <article class="c-tile t-proj">
          <h3>Projects</h3><p>Apply, accept, assign tasks and chat with your team.</p>
          <div class="c-frag c-minib" aria-hidden="true"><span style="--p:100%">Scope</span><span style="--p:100%">Design</span><span style="--p:55%">Prototype</span><span style="--p:10%">Pitch</span></div>
        </article>
        <article class="c-tile t-market">
          <h3>Marketplace</h3><p>Sell and buy student services, in AED or free.</p>
          <div class="c-frag c-svc" aria-hidden="true"><b>Logo and brand kit</b><small>Mariam · Design &amp; Creative</small><span><i>60 AED</i><i>3 days</i></span></div>
        </article>
        <article class="c-tile t-learn">
          <h3>Learning center</h3><p>Teachers publish courses; you enroll and track progress.</p>
          <div class="c-frag c-course" aria-hidden="true"><b>Intro to Machine Learning</b><small>Dr. Layla Hassan · 3 lessons</small><i><s style="--w:66%"></s></i></div>
        </article>
        <article class="c-tile t-people">
          <h3>People</h3><p>Search by skill, major or interest and follow people.</p>
          <div class="c-frag c-skills" aria-hidden="true"><span class="on">Flutter</span><span>Figma</span><span>Python</span><span>AutoCAD</span><span>Translation</span></div>
        </article>
        <article class="c-tile t-mentor">
          <h3>Mentorship</h3><p>Request a mentor from faculty and experts, then book real sessions.</p>
          <div class="c-frag c-slot" aria-hidden="true"><span class="c-av" style="--h:20">RS</span><span><b>Dr. Rania Saleh</b><small>Entrepreneurship · Pitch review</small></span><span class="c-when"><b>Thu</b><small>11:30</small></span></div>
        </article>
        <article class="c-tile t-ach">
          <h3>Achievements</h3><p>Reputation XP, 24 badges and 8 ranks, from Bronze to Celestial.</p>
          <div class="c-frag c-levels" aria-hidden="true"><?php foreach (['Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond', 'Legend', 'Master', 'Celestial'] as $i => $lv): ?><span class="<?= $i < 3 ? 'on' : '' ?>"><?= $lv ?></span><?php endforeach ?></div>
        </article>
      </div>
    </div>
  </section>

  <section class="c-access" id="access" data-tone="paper" aria-labelledby="accTitle">
    <div class="c-wrap c-access-grid">
      <header class="c-head">
        <h2 id="accTitle">Your university account is the key.</h2>
        <p>No new password. Sign in with your <b>@aau.ac.ae</b> Google account, and MANBAR matches your student number with the university roster to fill in your profile.</p>
        <ul class="c-checks">
          <li><?= icon('shield', 18) ?> Only verified university members get in.</li>
          <li><?= icon('user', 18) ?> Name, major and year come from the roster.</li>
          <li><?= icon('building', 18) ?> Built to add more UAE universities later.</li>
        </ul>
      </header>
      <div class="c-match" id="cMatch" aria-hidden="true">
        <div class="c-match-field"><span class="c-g"><svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.9 2.4 30.4 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.7 7l7.3 5.7c4.3-4 6.8-9.9 6.8-17.2z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-2.9-.8-4.7s.3-3.3.8-4.7l-7.9-6.1C.9 16.5 0 20.1 0 24s.9 7.5 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.3-5.7c-2 1.4-4.7 2.3-8.6 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg></span><span class="c-type" id="cType"></span><span class="c-at">@aau.ac.ae</span></div>
        <div class="c-match-line"><i></i></div>
        <div class="c-match-card">
          <small>Matched with the AAU roster</small>
          <div class="c-match-who"><span class="c-av lg" style="--h:152">MA</span><span><b>Mariam Al Mansoori</b><small>Student 202310101</small></span></div>
          <dl><div><dt>Major</dt><dd>Software Engineering</dd></div><div><dt>Faculty</dt><dd>College of Engineering</dd></div><div><dt>Status</dt><dd class="ok">Verified</dd></div></dl>
        </div>
        <p class="c-scene-note">Demo roster entry.</p>
      </div>
    </div>
  </section>

  <section class="c-compare" id="compare" data-tone="paper" aria-labelledby="cmpTitle">
    <div class="c-wrap">
      <header class="c-head">
        <h2 id="cmpTitle">Four apps’ worth of campus, in one place.</h2>
        <p>LinkedIn, Fiverr, Discord and Moodle each cover a piece. MANBAR brings the pieces together for students.</p>
      </header>
      <div class="c-table-wrap">
        <table class="c-table">
          <thead><tr><th scope="col">Feature</th><th scope="col" class="us">MANBAR <span class="c-score" aria-hidden="true"><b data-js="score">9</b>/9</span></th><th scope="col">LinkedIn</th><th scope="col">Fiverr</th><th scope="col">Discord</th><th scope="col">Moodle</th></tr></thead>
          <tbody>
          <?php
          $mark = ['Y' => '<span class="m y" title="Yes"><span class="sr">Yes</span></span>', 'L' => '<span class="m l" title="Limited"><span class="sr">Limited</span></span>', 'N' => '<span class="m n" title="No"><span class="sr">No</span></span>'];
          foreach ([['Idea sharing', 'Y', 'L', 'N', 'L', 'N'], ['Project collaboration', 'Y', 'N', 'N', 'L', 'N'], ['Campus-only access', 'Y', 'N', 'N', 'N', 'Y'], ['Student marketplace', 'Y', 'N', 'Y', 'N', 'N'], ['Mentorship system', 'Y', 'L', 'N', 'N', 'N'], ['Learning center', 'Y', 'N', 'N', 'N', 'Y'], ['Portfolio profiles', 'Y', 'Y', 'Y', 'N', 'L'], ['Achievements system', 'Y', 'N', 'N', 'N', 'N'], ['Skill search', 'Y', 'L', 'Y', 'N', 'N']] as $r => $row) {
              echo '<tr style="--r:' . $r . '"><th scope="row">' . e(array_shift($row)) . '</th>';
              foreach ($row as $i => $v) echo '<td style="--c:' . $i . '"' . ($i === 0 ? ' class="us"' : '') . '>' . $mark[$v] . '</td>';
              echo '</tr>';
          } ?>
          </tbody>
        </table>
        <p class="c-legend"><span><span class="m y"></span> Yes</span><span><span class="m l"></span> Limited</span><span><span class="m n"></span> No</span></p>
      </div>
    </div>
  </section>

  <section class="c-close" data-tone="void" aria-labelledby="closeTitle">
    <div class="c-wrap c-close-in">
      <img class="c-close-mark" src="<?= $logo ?>" alt="" width="88" height="88">
      <h2 id="closeTitle">Your idea needs a stage.</h2>
      <p>Join with your university account and start building with your campus today.</p>
      <a class="c-btn c-btn-lg" href="<?= $login ?>"><?= icon('lock', 18) ?> Sign in with @aau.ac.ae</a>
    </div>
    <footer class="c-foot">
      <span>© <?= date('Y') ?> MANBAR · منبر</span>
      <span>Capstone project, College of Engineering, Al Ain University. Built by Yaman AlNasri, Tamim Alzein, Ghaith Alsalim, Muhammad Toufeeq and Rami Albaini.</span>
    </footer>
  </section>
</main>
