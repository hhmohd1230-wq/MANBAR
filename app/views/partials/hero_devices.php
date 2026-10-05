<?php
/**
 * Landing hero: a smoked-glass laptop and phone running MANBAR, tilted by the pointer (see public/assets/js/cinema.js).
 * Everything on the screens is HTML/SVG so it stays sharp and "live" (activity stream, counters, use-case diagram).
 */
$logo = e(asset('img/logo.svg'));
$activity = [
    ['FK', 96, 'Fatima Al Kaabi', 'joined <b>Study-Buddy Matcher</b>', '2m'],
    ['SA', 60, 'Saeed Al Dhaheri', 'posted an idea: <b>solar shade for the bus stops</b>', '6m'],
    ['LH', 110, 'Dr. Layla Hassan', 'published <b>Intro to Machine Learning</b>', '14m'],
    ['YK', 170, 'Yousef Khan', 'moved <b>Indoor map: floor 2</b> to Done', '21m'],
    ['LH2', 30, 'Lina Haddad', 'offered <b>Arabic ⇄ English proofreading</b>', '33m'],
    ['HK', 200, 'Hamdan Al Ketbi', 'earned the <b>Team Player</b> badge', '41m'],
    ['NH', 330, 'Noura Al Hammadi', 'asked: <b>anyone into health-tech research?</b>', '52m'],
    ['ON', 118, 'Omar Al Nuaimi', 'reacted 💡 to <b>Campus Events App</b>', '1h'],
];
?>
<div class="c-devices c-focus" style="--i:3" aria-hidden="true">
  <div class="c-rig" id="cRig">
    <!-- ===== laptop ===== -->
    <div class="c-laptop" id="cLaptop">
      <div class="c-lid">
        <i class="c-cam"></i>
        <div class="c-screen">
          <div class="c-ui">
            <div class="c-ui-top">
              <span class="c-ui-brand"><img src="<?= $logo ?>" alt=""><b>MANBAR</b></span>
              <span class="c-ui-search"><?= icon('search', 11) ?> Search ideas, people, projects…</span>
              <span class="c-ui-new"><?= icon('plus', 10) ?> New post</span>
              <span class="c-ui-bell"><?= icon('bell', 12) ?><i class="tick" data-n="3">3</i></span>
              <span class="c-av xs" style="--h:152">MA</span>
            </div>
            <div class="c-ui-body">
              <nav class="c-ui-rail">
                <span><?= icon('home', 11) ?> Feed</span><span class="on"><?= icon('rocket', 11) ?> Projects</span><span><?= icon('store', 11) ?> Marketplace</span><span><?= icon('book', 11) ?> Learning</span><span><?= icon('compass', 11) ?> Mentors</span><span><?= icon('users', 11) ?> People</span>
                <span class="c-ui-lvl"><small>Level 3</small><b>Contributor</b><i><s></s></i></span>
              </nav>
              <div class="c-ui-main">
                <div class="c-ui-proj">
                  <div><b>Campus Events App</b><small>Flutter · UI Design · Firebase · owner Khalid Al Suwaidi</small></div>
                  <span class="c-ui-team"><span class="c-av xs" style="--h:140">KS</span><span class="c-av xs" style="--h:152">MA</span><span class="c-av xs" style="--h:30">LH</span><span class="c-ui-chip">In progress</span></span>
                </div>
                <div class="c-ui-tl">
                  <div class="c-ui-tl-line"><s></s></div>
                  <span class="done" style="--x:4%"><i></i><b>Kick-off</b><small>Sep 14</small></span>
                  <span class="done" style="--x:34%"><i></i><b>Screens</b><small>Sep 28</small></span>
                  <span class="now" style="--x:64%"><i></i><b>Prototype</b><small>Oct 12</small></span>
                  <span style="--x:94%"><i></i><b>Hackathon</b><small>Oct 26</small></span>
                </div>
                <div class="c-ui-board">
                  <div><b>To do</b><span>Write the pitch</span><span>User testing</span></div>
                  <div><b>Doing</b><span class="hot">Build the first prototype</span><span>Repo and CI</span></div>
                  <div><b>Done</b><span>MVP scope</span><span>Main screens</span></div>
                </div>
              </div>
              <aside class="c-ui-side">
                <div class="c-ui-card">
                  <b class="c-ui-h">Campus activity</b>
                  <ul class="c-ui-live" id="cLive" data-items="<?= e(json_encode($activity, JSON_UNESCAPED_UNICODE)) ?>">
                    <?php foreach (array_slice($activity, 0, 3) as [$ini, $h, $who, $what, $t]): ?>
                      <li><span class="c-av xs" style="--h:<?= $h ?>"><?= e(substr($ini, 0, 2)) ?></span><p><b><?= e($who) ?></b> <?= $what ?></p><small><?= $t ?></small></li>
                    <?php endforeach ?>
                  </ul>
                </div>
                <div class="c-ui-card c-uc">
                  <b class="c-ui-h">Who does what</b>
                  <svg viewBox="0 0 200 120" class="c-uc-svg">
                    <g class="c-uc-links">
                      <path class="a1" d="M30 26 C 80 26, 90 18, 128 16"/><path class="a1" d="M30 26 C 80 30, 90 44, 128 44"/>
                      <path class="a2" d="M30 62 C 80 62, 90 70, 128 72"/><path class="a2" d="M30 62 C 70 60, 90 48, 128 44"/>
                      <path class="a3" d="M30 98 C 80 98, 90 100, 128 100"/>
                    </g>
                    <g class="c-uc-pulse">
                      <circle r="2.2"><animateMotion dur="2.4s" repeatCount="indefinite" path="M30 26 C 80 26, 90 18, 128 16"/></circle>
                      <circle r="2.2"><animateMotion dur="2.8s" begin=".6s" repeatCount="indefinite" path="M30 62 C 80 62, 90 70, 128 72"/></circle>
                      <circle r="2.2"><animateMotion dur="3.1s" begin="1.1s" repeatCount="indefinite" path="M30 98 C 80 98, 90 100, 128 100"/></circle>
                      <circle r="2.2"><animateMotion dur="2.6s" begin="1.6s" repeatCount="indefinite" path="M30 26 C 80 30, 90 44, 128 44"/></circle>
                    </g>
                    <g class="c-uc-actor a1"><circle cx="20" cy="26" r="9"/><text x="20" y="44">Student</text></g>
                    <g class="c-uc-actor a2"><circle cx="20" cy="62" r="9"/><text x="20" y="80">Teacher</text></g>
                    <g class="c-uc-actor a3"><circle cx="20" cy="98" r="9"/><text x="20" y="116">Admin</text></g>
                    <g class="c-uc-case"><rect x="128" y="8" width="66" height="16" rx="8"/><text x="161" y="19">Post idea</text></g>
                    <g class="c-uc-case"><rect x="128" y="36" width="66" height="16" rx="8"/><text x="161" y="47">Join project</text></g>
                    <g class="c-uc-case"><rect x="128" y="64" width="66" height="16" rx="8"/><text x="161" y="75">Teach course</text></g>
                    <g class="c-uc-case"><rect x="128" y="92" width="66" height="16" rx="8"/><text x="161" y="103">Import roster</text></g>
                  </svg>
                </div>
              </aside>
            </div>
          </div>
          <i class="c-glare" id="cGlareL"></i>
        </div>
      </div>
      <div class="c-deck"><i></i></div>
    </div>

    <!-- ===== phone ===== -->
    <div class="c-phone" id="cPhone">
      <div class="c-phone-frame">
        <i class="c-island"></i>
        <div class="c-phone-screen">
          <div class="c-ph-status"><b>9:41</b><span><i></i><i></i><i></i></span></div>
          <div class="c-ph-bar"><img src="<?= $logo ?>" alt=""><b>MANBAR</b><span><?= icon('bell', 12) ?></span></div>
          <div class="c-ph-compose"><span class="c-av xs" style="--h:152">MA</span><span>Share an idea…</span></div>
          <div class="c-ph-post">
            <div class="c-post-h"><span class="c-av xs" style="--h:280">AR</span><span><b>Aisha Rahman</b><small>Showcase · 1d</small></span></div>
            <b class="c-ph-t">Our short film made the festival</b>
            <div class="c-poster"><small>Campus Film Festival</small><b>Echoes of Al&nbsp;Ain</b><span>7 min · Media + Engineering</span></div>
            <div class="c-post-f"><span class="c-reacts"><i>🎉</i><i>💚</i><i>👍</i></span><b class="tick" data-n="87">87</b><span>23 comments</span></div>
          </div>
          <div class="c-ph-ai"><?= icon('sparkles', 11) ?> <span>Assistant: this looks like an <b>Event</b>. Add a date?</span></div>
          <div class="c-ph-tabs"><?= icon('home', 13) ?><?= icon('rocket', 13) ?><span class="c-ph-plus"><?= icon('plus', 13) ?></span><?= icon('chat', 13) ?><?= icon('user', 13) ?></div>
        </div>
        <i class="c-glare" id="cGlareP"></i>
      </div>
    </div>
  </div>
  <p class="c-preview">Product preview with demo content</p>
</div>
