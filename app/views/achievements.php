<?php
$title = 'Achievements';
$earned = count(array_filter($badges, fn($badge) => (int) $badge['earned']));
$totalBadges = count($badges);
$toNext = $lvl['next'] ? max(0, (int) $lvl['next_at'] - (int) $rankScore) : 0;
$rankPercent = max(1, (int) ceil($rank / max(1, $memberCount) * 100));
$rankTrack = count($levels) > 1 ? (($lvl['n'] - 1) / (count($levels) - 1)) * 100 : 100;
$tierNames = ['standard' => 'Core', 'rare' => 'Rare', 'epic' => 'Epic', 'legendary' => 'Legendary'];
?>

<div class="achievements-page" data-achievements>
  <header class="achievement-pagehead">
    <div>
      <h1>Build your MANBAR legacy</h1>
      <p>Every useful contribution moves your rank forward. Complete quests, collect badges and climb the campus leaderboard.</p>
    </div>
    <a class="achievement-profile-link" href="<?= e(url('profile/' . $u['id'] . '?tab=badges')) ?>"><?= icon('user', 17) ?> View badge showcase</a>
  </header>

  <section class="achievement-command rank-theme-<?= e(rank_key((int) $lvl['n'])) ?>" aria-labelledby="current-rank-title">
    <div class="rank-vfx" aria-hidden="true">
      <span class="rank-vfx-aura"></span>
      <span class="rank-vfx-orbit"></span>
      <span class="rank-vfx-sweep"></span>
      <span class="rank-vfx-particles"><?php for ($spark = 0; $spark < 9; $spark++): ?><i></i><?php endfor ?></span>
    </div>
    <div class="achievement-crest" aria-hidden="true">
      <span class="rank-emblem rank-<?= e(rank_key((int) $lvl['n'])) ?> rank-emblem-hero"></span>
      <small>RANK <?= (int) $lvl['n'] ?> · STAR <?= (int) $lvl['star'] ?></small>
    </div>
    <div class="achievement-level-copy">
      <p class="achievement-level-label">Current rank</p>
      <h2 id="current-rank-title"><?= e($lvl['name']) ?></h2>
      <div class="rank-star-line achievement-star-line"><?= rank_stars_html($lvl, 'rank-stars achievement-rank-stars') ?><b><?= e($lvl['name']) ?> · Star <?= (int) $lvl['star'] ?> of <?= RANK_STARS_PER_LEAGUE ?></b></div>
      <p><?= $lvl['next'] ? 'Your next milestone is ' . e($lvl['next']) . '. Every star needs ' . RANK_STAR_XP . ' verified reputation XP.' : 'You reached Celestial III, MANBAR’s highest rank. Your contribution now sets the standard for the campus.' ?></p>
      <div class="achievement-xp-head"><span><b><?= number_format((int) $rankScore) ?></b> rank XP</span><span><?= $lvl['next'] ? number_format($toNext) . ' XP to ' . e($lvl['next']) : 'Maximum rank reached' ?></span></div>
      <div class="achievement-xp" role="progressbar" aria-label="Progress to <?= e($lvl['next'] ?: $lvl['name']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $lvl['pct'] ?>"><i style="--xp:<?= (int) $lvl['pct'] ?>%"></i></div>
    </div>
    <dl class="achievement-readout">
      <div><dt>Campus rank</dt><dd>#<?= (int) $rank ?></dd><small>Top <?= (int) $rankPercent ?>% of <?= (int) $memberCount ?></small></div>
      <div><dt>Badge vault</dt><dd><?= (int) $earned ?><span>/<?= (int) $totalBadges ?></span></dd><small><?= (int) round($earned / max(1, $totalBadges) * 100) ?>% collected</small></div>
      <div><dt>Trust score</dt><dd><?= $reputation['trust_percent'] === null ? '—' : (int) $reputation['trust_percent'] . '%' ?></dd><small><?= (int) $reputation['review_count'] + (int) $reputation['recommendation_count'] + (int) $reputation['vouch_count'] ?> verified ratings</small></div>
    </dl>
  </section>

  <section class="reputation-ledger" aria-labelledby="reputation-ledger-title">
    <div><h2 id="reputation-ledger-title">What builds your rank</h2><p>Rank XP combines contribution with trust. Reviews and recommendations only count after completed work.</p></div>
    <dl>
      <div><dt><?= icon('sparkles', 15) ?> Activity</dt><dd><?= number_format((int) $reputation['activity']) ?></dd></div>
      <div><dt><?= icon('heart', 15) ?> Community appreciation</dt><dd><?= number_format((int) $reputation['appreciation']) ?></dd></div>
      <div><dt><?= icon('star', 15) ?> Client reviews</dt><dd><?= number_format((int) $reputation['reviews']) ?></dd></div>
      <div><dt><?= icon('users', 15) ?> Project vouches</dt><dd><?= number_format((int) $reputation['vouches']) ?></dd></div>
      <div><dt><?= icon('award', 15) ?> Recommendations</dt><dd><?= number_format((int) $reputation['recommendations']) ?></dd></div>
      <div><dt><?= icon('check-circle', 15) ?> Completed work</dt><dd><?= number_format((int) $reputation['completed_work']) ?></dd></div>
    </dl>
  </section>

  <section class="rank-road" aria-labelledby="rank-road-title" style="--rank-progress:<?= number_format($rankTrack, 2, '.', '') ?>%">
    <div class="achievement-section-head">
      <div><h2 id="rank-road-title">Your rank journey</h2><p>Three 150-XP stars complete each league. Finish Star III to promote into the next rank.</p></div>
      <?php if ($rankGap > 0): ?><span><?= icon('trending', 15) ?> <?= (int) $rankGap ?> XP from the next campus position</span><?php endif ?>
    </div>
    <div class="rank-road-scroll">
      <ol class="rank-road-track">
        <?php foreach ($levels as $i => [$minimum, $name]):
          $number = $i + 1;
          $state = $number < $lvl['n'] ? 'is-reached' : ($number === $lvl['n'] ? 'is-current' : 'is-locked');
        ?>
          <li class="<?= $state ?>" aria-current="<?= $number === $lvl['n'] ? 'step' : 'false' ?>">
            <span class="rank-road-node"><span class="rank-emblem rank-<?= e(rank_key($number)) ?>"></span></span>
            <b><?= e($name) ?></b>
            <?= rank_stars_html(['star' => $number < $lvl['n'] ? 3 : ($number === $lvl['n'] ? $lvl['star'] : 0)], 'rank-stars rank-road-stars') ?>
            <small>Stars I · II · III · <?= number_format((int) $minimum) ?> XP entry</small>
          </li>
        <?php endforeach ?>
      </ol>
    </div>
  </section>

  <div class="achievement-layout">
    <main class="achievement-main">
      <section class="quest-board" aria-labelledby="quest-title">
        <div class="achievement-section-head">
          <div><h2 id="quest-title">Next quests</h2><p>Your closest badge unlocks, calculated from your real activity.</p></div>
          <span><?= icon('target', 15) ?> <?= count($quests) ?> active</span>
        </div>
        <?php if ($quests): ?>
          <div class="quest-list">
            <?php foreach ($quests as $index => $badge): $progress = $badge['progress']; ?>
              <article class="quest-item tier-<?= e($badge['tier']) ?> <?= $index === 0 ? 'is-priority' : '' ?>">
                <div class="quest-token tone-<?= e($badge['tone']) ?>"><?= icon($badge['icon'], $index === 0 ? 27 : 22) ?></div>
                <div class="quest-copy">
                  <div class="quest-title"><h3><?= e($badge['name']) ?></h3><span><?= e($tierNames[$badge['tier']] ?? 'Core') ?></span></div>
                  <p><?= e($progress['label']) ?></p>
                  <div class="quest-progress-head"><span><?= (int) $progress['current'] ?> / <?= (int) $progress['target'] ?></span><b><?= (int) $progress['pct'] ?>%</b></div>
                  <div class="quest-progress"><i style="--quest:<?= (int) $progress['pct'] ?>%"></i></div>
                </div>
                <div class="quest-art tone-<?= e($badge['tone']) ?>" aria-hidden="true"><?= icon($badge['icon'], $index === 0 ? 80 : 54) ?></div>
                <a class="quest-action" href="<?= e($progress['url']) ?>" aria-label="Continue quest: <?= e($badge['name']) ?>">Continue <?= icon('arrow-right', 14) ?></a>
              </article>
            <?php endforeach ?>
          </div>
        <?php else: ?>
          <div class="quest-complete"><?= icon('award', 30) ?><div><h3>Every badge unlocked</h3><p>You completed the current collection. Keep contributing while the next challenge set is prepared.</p></div></div>
        <?php endif ?>
      </section>

      <section class="badge-vault" aria-labelledby="badge-vault-title">
        <div class="achievement-section-head badge-vault-head">
          <div><h2 id="badge-vault-title">Badge vault</h2><p>Earned badges stay on your profile as proof of your campus contribution.</p></div>
          <div class="badge-filters" role="group" aria-label="Filter badges">
            <button type="button" class="is-active" data-badge-filter="all" aria-pressed="true">All <span><?= (int) $totalBadges ?></span></button>
            <button type="button" data-badge-filter="earned" aria-pressed="false">Earned <span><?= (int) $earned ?></span></button>
            <button type="button" data-badge-filter="locked" aria-pressed="false">Locked <span><?= (int) ($totalBadges - $earned) ?></span></button>
          </div>
        </div>
        <div class="achievement-badge-grid" aria-live="polite">
          <?php foreach ($badges as $badge): $progress = $badge['progress']; $isEarned = (int) $badge['earned']; ?>
            <article class="achievement-badge tier-<?= e($badge['tier']) ?> <?= $isEarned ? 'is-earned' : 'is-locked' ?>" data-badge-state="<?= $isEarned ? 'earned' : 'locked' ?>">
              <div class="achievement-badge-top">
                <span class="badge-tier"><?= e($tierNames[$badge['tier']] ?? 'Core') ?></span>
                <span class="badge-state"><?= $isEarned ? icon('check-circle', 16) . ' Earned' : icon('lock', 14) . ' Locked' ?></span>
              </div>
              <div class="achievement-badge-medal tone-<?= e($badge['tone']) ?>"><?= icon($badge['icon'], 28) ?></div>
              <h3><?= e($badge['name']) ?></h3>
              <p><?= e($badge['description']) ?></p>
              <?php if ($isEarned): ?>
                <small class="badge-earned-date"><?= $badge['earned_at'] ? 'Unlocked ' . e(time_ago($badge['earned_at'])) : 'Part of your collection' ?></small>
              <?php else: ?>
                <div class="badge-mini-progress"><i style="--badge:<?= (int) $progress['pct'] ?>%"></i></div>
                <small><?= (int) $progress['current'] ?> / <?= (int) $progress['target'] ?> · <?= e($progress['label']) ?></small>
              <?php endif ?>
            </article>
          <?php endforeach ?>
        </div>
        <p class="badge-filter-empty" hidden>No badges match this filter yet.</p>
      </section>
    </main>

    <aside class="achievement-sidebar">
      <section class="achievement-panel leaderboard-panel">
        <div class="achievement-panel-head"><div><h2>Campus leaderboard</h2><p>Top contributors by total XP</p></div><?= icon('trophy', 21) ?></div>
        <div class="achievement-leaderboard">
          <?php foreach ($board as $i => $person): $isMe = (int) $person['id'] === (int) $u['id']; ?>
            <div class="leader-row <?= $isMe ? 'is-you' : '' ?>">
              <span class="leader-position rank r<?= $i + 1 ?>"><?= $i + 1 ?></span>
              <a href="<?= e(url('profile/' . $person['id'])) ?>"><?= avatar($person, 38) ?></a>
              <a class="leader-name" href="<?= e(url('profile/' . $person['id'])) ?>"><b><?= e($person['full_name']) ?><?= $isMe ? ' (You)' : '' ?></b><small><?= e($person['major'] ?: role_label($person['role'])) ?></small></a>
              <strong><?= number_format((int) $person['reputation_score']) ?> <small>XP</small></strong>
            </div>
          <?php endforeach ?>
        </div>
      </section>

      <section class="achievement-panel">
        <div class="achievement-panel-head"><div><h2>Recent XP</h2><p>Your latest progress</p></div><?= icon('trending', 21) ?></div>
        <div class="xp-history">
          <?php foreach ($log as $entry): ?>
            <div><span><?= icon('sparkles', 14) ?></span><p><b><?= e($entry['reason']) ?></b><small><?= e(time_ago($entry['created_at'])) ?></small></p><strong>+<?= (int) $entry['points'] ?></strong></div>
          <?php endforeach ?>
          <?php if (!$log): ?><div class="achievement-empty"><?= icon('target', 22) ?><p><b>Your journey starts here</b><small>Complete a quest to earn your first XP.</small></p></div><?php endif ?>
        </div>
      </section>

      <details class="achievement-panel points-guide">
        <summary><span><?= icon('info', 18) ?> How to earn XP</span><?= icon('chevron-down', 17) ?></summary>
        <div class="points-guide-list">
          <?php foreach ([['Five-star teammate vouch', 'up to +150'], ['Five-star client review', '+100'], ['Verified recommendation', 'up to +60'], ['Complete a project', '+30'], ['Finish a course', '+25'], ['Complete your profile', '+20'], ['Start a project', '+15'], ['Publish a post', '+10'], ['Join a project team', '+10'], ['Receive a reaction', '+1 / +2']] as [$action, $points]): ?>
            <div><span><?= e($action) ?></span><b><?= e($points) ?></b></div>
          <?php endforeach ?>
        </div>
      </details>
    </aside>
  </div>
</div>
