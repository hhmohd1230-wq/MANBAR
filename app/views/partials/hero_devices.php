<?php
/**
 * Landing-page showcase: a laptop running MANBAR and a row of phones with animated, laughing students.
 * Pure HTML/SVG/CSS (see public/assets/css/landing.css) — no images or libraries needed.
 */
if (!function_exists('student_svg')) {
    /** Flat-style student bust. $o: look (ghutra|hijab|curly|long), skin, shirt, accent, bg */
    function student_svg(array $o): string
    {
        $skin = $o['skin']; $shirt = $o['shirt']; $acc = $o['accent'] ?? '#2fa862'; $bg = $o['bg'] ?? '#dff6e8';
        $shade = $o['shade'] ?? 'rgba(0,0,0,.12)';
        $look = $o['look'];
        $s = '<svg class="st-svg" viewBox="0 0 120 130" aria-hidden="true">';
        $s .= '<circle cx="60" cy="64" r="52" fill="' . $bg . '"/>';
        $s .= '<g class="st-body">';
        // shoulders / clothing
        if ($look === 'ghutra') {
            $s .= '<path d="M14 132C14 100 34 88 60 88s46 12 46 44Z" fill="#fbfbf7"/><path d="M60 88v44" stroke="#e5e5dd" stroke-width="2"/><circle cx="60" cy="99" r="2.2" fill="#d9d9cf"/>';
        } elseif ($look === 'hijab') {
            $s .= '<path d="M14 132C14 100 34 88 60 88s46 12 46 44Z" fill="' . $shirt . '"/>';
        } else {
            $s .= '<path d="M14 132C14 100 34 88 60 88s46 12 46 44Z" fill="' . $shirt . '"/><path d="M48 90q12 10 24 0" fill="none" stroke="' . $shade . '" stroke-width="3" stroke-linecap="round"/>';
        }
        $s .= '<rect x="52" y="72" width="16" height="18" rx="7" fill="' . $skin . '"/><rect x="52" y="72" width="16" height="9" rx="4" fill="' . $shade . '"/>';
        // hand holding a phone with MANBAR on screen
        $s .= '<g class="st-phone"><rect x="80" y="86" width="19" height="31" rx="4" fill="#1d2a24"/><rect x="82" y="89" width="15" height="24" rx="2" fill="#34b06b"/><path d="M85.500 101h8M85.500 104.500h5" stroke="#fff" stroke-width="1.600" stroke-linecap="round"/><circle cx="89.500" cy="95" r="2.600" fill="#fff"/><ellipse cx="88" cy="118" rx="9" ry="7" fill="' . $skin . '"/></g>';
        $s .= '</g><g class="st-head">';
        // back layers
        if ($look === 'hijab') {
            $s .= '<path d="M60 24C35 24 29 45 29 62c0 22 11 36 31 38 20-2 31-16 31-38 0-17-6-38-31-38Z" fill="' . $acc . '"/><path d="M33 84c4 12 14 20 27 21 13-1 23-9 27-21-6 9-15 13-27 13s-21-4-27-13Z" fill="' . $shade . '"/>';
        } elseif ($look === 'long') {
            $s .= '<path d="M33 58c-2-20 10-32 27-32s29 12 27 32l3 40c-6 4-14 5-20 2l-2-30H52l-2 30c-6 3-14 2-20-2Z" fill="#3b2418"/>';
        } elseif ($look === 'ghutra') {
            $s .= '<path d="M29 60C29 36 43 26 60 26s31 10 31 34l5 44c-6 3-13 3-18 0l-1-40H43l-1 40c-5 3-12 3-18 0Z" fill="#ffffff"/><path d="M35 70l-4 30M85 70l4 30" stroke="#ececec" stroke-width="2"/>';
        }
        // ears (hidden under headscarves)
        if ($look === 'curly' || $look === 'long') $s .= '<circle cx="36" cy="63" r="5" fill="' . $skin . '"/><circle cx="84" cy="63" r="5" fill="' . $skin . '"/>';
        // face
        $rx = $look === 'hijab' ? 21.5 : 24; $ry = $look === 'hijab' ? 24 : 26;
        $s .= '<ellipse cx="60" cy="62" rx="' . $rx . '" ry="' . $ry . '" fill="' . $skin . '"/>';
        // front hair / headwear
        if ($look === 'curly') {
            foreach ([[40, 46, 9], [49, 38, 10], [60, 35, 10], [71, 38, 10], [80, 46, 9], [44, 40, 7], [76, 40, 7]] as [$x, $y, $r]) $s .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="#1e1410"/>';
        } elseif ($look === 'long') {
            $s .= '<path d="M36 58c0-18 11-27 24-27s25 9 24 27c-7-9-15-13-24-13-6 7-14 11-24 13Z" fill="#3b2418"/>';
        } elseif ($look === 'hijab') {
            $s .= '<path d="M38.500 52c4-14 39-14 43 0-7-8-36-8-43 0Z" fill="' . $shade . '"/>';
        } elseif ($look === 'ghutra') {
            $s .= '<path d="M35 56c2-16 12-23 25-23s23 7 25 23c-6-8-15-11-25-11s-19 3-25 11Z" fill="#f4f4f0"/>';
            $s .= '<ellipse cx="60" cy="34" rx="26" ry="6.500" fill="none" stroke="#1b1b1b" stroke-width="3"/><ellipse cx="60" cy="31" rx="24" ry="5.500" fill="none" stroke="#2b2b2b" stroke-width="2.400"/>';
            $s .= '<path d="M40 76c2 13 10 20 20 20s18-7 20-20c-4 8-11 12-20 12s-16-4-20-12Z" fill="#2a1d16" opacity=".85"/>';
        }
        // brows, happy closed eyes, cheeks
        $s .= '<path d="M45 49q5-4 10-1M65 48q5-3 10 1" fill="none" stroke="#2a1a12" stroke-width="2.200" stroke-linecap="round"/>';
        $s .= '<g class="st-eyes"><path d="M46 59q5-6 10 0M64 59q5-6 10 0" fill="none" stroke="#2a1a12" stroke-width="2.800" stroke-linecap="round"/></g>';
        if (!empty($o['glasses'])) $s .= '<g fill="none" stroke="#1c1c1c" stroke-width="2"><circle cx="51" cy="58" r="8"/><circle cx="69" cy="58" r="8"/><path d="M59 58h2M43 57l-6-2M77 57l6-2"/></g>';
        $s .= '<circle cx="45" cy="68" r="4.500" fill="#ff7b7b" opacity=".45"/><circle cx="75" cy="68" r="4.500" fill="#ff7b7b" opacity=".45"/>';
        // laughing mouth (animated)
        $s .= '<g class="st-mouth"><path d="M48.500 69Q60 70 71.500 69Q70.500 85 60 85Q49.500 85 48.500 69Z" fill="#6b1f2a"/><path d="M49.500 69.200L70.500 69.200L69.800 72.400L50.200 72.400Z" fill="#fff"/><path d="M53.500 80Q60 75 66.500 80Q64 85 60 85Q56 85 53.500 80Z" fill="#ff6b81"/></g>';
        $s .= '</g></svg>';
        return $s;
    }
}

$students = [
    ['look' => 'ghutra', 'skin' => '#c98e62', 'shirt' => '#fff', 'bg' => '#dff6e8', 'name' => 'Hamdan', 'text' => 'Our app won the hackathon! 🏆', 'emo' => ['😂', '🎉', '💚'], 'n' => 128, 'theme' => 't1'],
    ['look' => 'hijab', 'skin' => '#eab98f', 'shirt' => '#1f2a44', 'accent' => '#2fa862', 'bg' => '#fff3d6', 'name' => 'Mariam', 'text' => 'Found my whole team in 1 day 🤝', 'emo' => ['😂', '💡', '💚'], 'n' => 96, 'theme' => 't2'],
    ['look' => 'curly', 'skin' => '#8d5a3b', 'shirt' => '#1fa5a0', 'bg' => '#e3f0fd', 'glasses' => true, 'name' => 'Yousef', 'text' => 'Sold my first logo design 😎', 'emo' => ['🤣', '🔥', '👍'], 'n' => 74, 'theme' => 't3'],
    ['look' => 'hijab', 'skin' => '#f3cfae', 'shirt' => '#7b4bd1', 'accent' => '#e0587b', 'bg' => '#fde6ed', 'name' => 'Noura', 'text' => 'Level up! I’m an Innovator now ✨', 'emo' => ['😂', '🎉', '⭐'], 'n' => 152, 'theme' => 't4'],
];
$logo = e(asset('img/logo.svg'));
?>
<div class="stage" aria-hidden="true">
  <div class="stage-in">
    <!-- floating badges around the devices -->
    <div class="fbadge fb1"><span>💡</span> New idea · 24 reactions</div>
    <div class="fbadge fb2"><span>🏆</span> Badge unlocked: Idea Machine</div>
    <div class="fbadge fb3"><span>✨</span> AI: “Post this as a Team request”</div>

    <!-- ===== Laptop ===== -->
    <div class="laptop">
      <div class="lid">
        <i class="cam"></i>
        <div class="screen">
          <div class="app">
            <div class="app-side">
              <div class="app-brand"><img src="<?= $logo ?>" alt=""><b>MAN<em>BAR</em></b></div>
              <span class="nv on">🏠 Home feed</span><span class="nv">🚀 Projects</span><span class="nv">🛍️ Marketplace</span><span class="nv">📘 Learning</span><span class="nv">🧭 Mentors</span><span class="nv">👥 People</span>
              <div class="app-level"><small>LEVEL 3</small><b>Contributor</b><i><s></s></i></div>
            </div>
            <div class="app-main">
              <div class="app-top"><span class="app-search">🔍 Search ideas, people, projects…</span><span class="app-new">+ New post</span><span class="app-bell">🔔<i>3</i></span><span class="app-av">YA</span></div>
              <div class="app-compose"><span class="av av1">YA</span><span class="typing"><span>Looking for a UI designer for our study app…</span></span><span class="app-ai">✨ AI assist</span></div>
              <div class="app-feed">
                <div class="feed-track">
                  <?php for ($dup = 0; $dup < 2; $dup++): foreach ([
                      ['av2', 'Mariam Al Mansoori', '2h', '💡 Idea', 'A study-buddy matcher for finals week 📚', 'Match students by course, schedule and learning style.', ['💡', '💚', '🎉'], 24],
                      ['av3', 'Khalid Al Suwaidi', '5h', '🤝 Team', 'Need 2 teammates for the UAE hackathon 🚀', 'Flutter + Firebase — looking for a designer & a pitcher.', ['🔥', '👍', '💚'], 41],
                      ['av4', 'Dr. Layla Hassan', '1d', '📣 Announcement', 'AI Club kick-off & research opportunities', 'Wednesday 1 pm · Lab B-204 · no experience needed.', ['🎉', '💡', '👍'], 63],
                      ['av5', 'Aisha Rahman', '1d', '🏆 Showcase', 'Our short film was selected for the festival! 🎬', 'Huge thanks to the team from Media & Engineering.', ['😂', '🎉', '💚'], 87],
                  ] as [$av, $nm, $t, $tag, $ttl, $txt, $em, $n]): ?>
                    <div class="fpost">
                      <div class="fp-head"><span class="av <?= $av ?>"><?= e(initials($nm)) ?></span><span class="fp-who"><b><?= e($nm) ?></b><small><?= $t ?> ago</small></span><span class="fp-tag"><?= $tag ?></span></div>
                      <b class="fp-title"><?= e($ttl) ?></b>
                      <p><?= e($txt) ?></p>
                      <div class="fp-foot"><span class="fp-emo"><?php foreach ($em as $x) echo '<i>' . $x . '</i>' ?></span><b class="tick" data-n="<?= $n ?>"><?= $n ?></b><span class="fp-acts">👍 React · 💬 Comment · ↗ Share</span></div>
                    </div>
                  <?php endforeach; endfor ?>
                </div>
              </div>
            </div>
            <div class="app-toast t-a">🎉 <b>Mariam</b> joined your project</div>
            <div class="app-toast t-b">💚 <b>12 people</b> reacted to your idea</div>
            <div class="app-hearts"><i>💚</i><i>🎉</i><i>💡</i><i>😂</i><i>💚</i></div>
          </div>
        </div>
      </div>
      <div class="base"><i></i></div>
    </div>

    <!-- ===== Phones with laughing students ===== -->
    <div class="phones">
      <?php foreach ($students as $i => $st): ?>
        <div class="phone p<?= $i + 1 ?>" style="--d:<?= -$i * .37 ?>s">
          <div class="ph-frame">
            <i class="ph-notch"></i>
            <div class="ph-bar"><img src="<?= $logo ?>" alt=""><b>MANBAR</b><span>🔔</span></div>
            <div class="ph-scene <?= $st['theme'] ?>">
              <?= student_svg($st) ?>
              <span class="haha h1">Haha!</span><span class="haha h2">😂</span>
              <span class="rise r1"><?= $st['emo'][0] ?></span><span class="rise r2"><?= $st['emo'][1] ?></span><span class="rise r3"><?= $st['emo'][2] ?></span>
            </div>
            <div class="ph-post">
              <div class="pp-head"><span class="av av<?= $i + 2 ?>"><?= e(mb_substr($st['name'], 0, 1)) ?></span><b><?= e($st['name']) ?></b></div>
              <p><?= e($st['text']) ?></p>
              <div class="pp-foot"><span><?= implode('', $st['emo']) ?></span><b class="tick" data-n="<?= $st['n'] ?>"><?= $st['n'] ?></b></div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</div>
