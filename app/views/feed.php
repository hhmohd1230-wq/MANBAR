<?php
$title = 'Home';
$qs = fn(array $o) => url('feed?' . http_build_query(array_filter(array_merge(['type' => $type, 'tag' => $tag, 'q' => $q, 'sort' => $sort === 'new' ? '' : $sort], $o), fn($v) => $v !== '' && $v !== null)));
$firstName = explode(' ', trim($u['full_name']))[0];
?>
<div class="home-page">
  <?php if ($isHome): ?>
  <section class="home-hero" aria-labelledby="welcomeTitle" data-hero-rotator>
    <div class="home-hero-backgrounds" aria-hidden="true">
      <span class="home-hero-bg is-active" style="background-image:url('<?= e(asset('img/home-campus-library-v2.png')) ?>')"></span>
      <span class="home-hero-bg" style="background-image:url('<?= e(asset('img/home-campus-collaboration-v1.png')) ?>')"></span>
      <span class="home-hero-bg" style="background-image:url('<?= e(asset('img/home-campus-makerspace-v1.png')) ?>')"></span>
    </div>
    <div class="home-hero-copy">
      <span class="home-eyebrow"><?= icon('sparkles', 15) ?> Your campus, connected</span>
      <h1 id="welcomeTitle">Welcome to MANBAR, <?= e($firstName) ?></h1>
      <p>Turn an idea into a project, find the right people, or learn something useful. Start wherever feels natural.</p>
      <div class="home-hero-actions">
        <button class="btn btn-hero" type="button" data-open-guide><?= icon('sparkles', 18) ?> Ask MANBAR AI</button>
        <a class="home-text-link" href="<?= e(url('feed?compose=idea')) ?>">Share an idea <?= icon('arrow-right', 15) ?></a>
      </div>
    </div>
    <div class="home-hero-note" aria-label="Your MANBAR progress">
      <span class="hero-note-label">Your progress</span>
      <strong><?= (int) $u['points'] ?></strong>
      <span>points earned</span>
      <a href="<?= e(url('achievements')) ?>">View achievements <?= icon('arrow-right', 14) ?></a>
    </div>
    <div class="home-hero-dots" aria-label="Choose welcome background">
      <button class="is-active" type="button" data-hero-slide="0" aria-label="Show library background" aria-current="true"></button>
      <button type="button" data-hero-slide="1" aria-label="Show collaboration background"></button>
      <button type="button" data-hero-slide="2" aria-label="Show makerspace background"></button>
    </div>
  </section>

  <section class="home-spaces" aria-labelledby="spacesTitle">
    <div class="section-heading" data-tour="spaces-overview">
      <span class="section-kicker">Main features</span>
      <h2 id="spacesTitle">Choose where you want to begin</h2>
      <p>Every part of MANBAR is built around student work, support, and collaboration.</p>
    </div>
    <div class="feature-square-grid">
      <button class="feature-square feature-square-ai" type="button" data-tour="ai" data-open-guide>
        <span class="feature-top"><span class="feature-number">01</span><span class="feature-icon"><?= icon('sparkles', 24) ?></span></span>
        <span class="feature-art feature-art-ai" aria-hidden="true"><span class="ai-orbit"></span><span class="ai-bubble ai-bubble-left"><?= icon('chat', 34) ?></span><span class="ai-core"><?= icon('sparkles', 43) ?></span><i class="feature-spark spark-a"></i><i class="feature-spark spark-b"></i><i class="feature-spark spark-c"></i></span>
        <span class="feature-copy"><strong>Ask MANBAR AI</strong><span>Tell us your goal. Get the best next step, a cleaner draft, and the right place to post.</span></span>
        <span class="feature-go">Start a conversation <?= icon('arrow-right', 16) ?></span>
      </button>
      <a class="feature-square feature-square-projects" data-tour="projects" href="<?= e(url('projects')) ?>">
        <span class="feature-top"><span class="feature-number">02</span><span class="feature-icon"><?= icon('rocket', 24) ?></span></span>
        <span class="feature-art feature-art-projects" aria-hidden="true"><span class="project-board"><i></i><i></i><i></i><i></i><i></i><i></i></span><span class="project-avatar project-avatar-a"><?= icon('users', 20) ?></span><span class="project-avatar project-avatar-b"><?= icon('user-plus', 18) ?></span><span class="project-dot dot-a"></span><span class="project-dot dot-b"></span><span class="project-dot dot-c"></span></span>
        <span class="feature-copy"><strong>Projects</strong><span>Build a team, join an open idea, and keep shared work moving.</span></span>
        <span class="feature-go">Explore projects <?= icon('arrow-right', 16) ?></span>
      </a>
      <a class="feature-square feature-square-market" data-tour="marketplace" href="<?= e(url('marketplace')) ?>">
        <span class="feature-top"><span class="feature-number">03</span><span class="feature-icon"><?= icon('store', 24) ?></span></span>
        <span class="feature-art feature-art-market" aria-hidden="true"><span class="market-store"><span class="market-awning"><i></i><i></i><i></i><i></i><i></i></span><?= icon('store', 64) ?></span><span class="market-chip chip-tools"><?= icon('settings', 21) ?></span><span class="market-chip chip-cap"><?= icon('cap', 21) ?></span><span class="market-chip chip-work"><?= icon('briefcase', 21) ?></span></span>
        <span class="feature-copy"><strong>Marketplace</strong><span>Offer your skills or find trusted student services on campus.</span></span>
        <span class="feature-go">Browse services <?= icon('arrow-right', 16) ?></span>
      </a>
      <a class="feature-square feature-square-learn" data-tour="learning" href="<?= e(url('learn')) ?>">
        <span class="feature-top"><span class="feature-number">04</span><span class="feature-icon"><?= icon('book', 24) ?></span></span>
        <span class="feature-art feature-art-learn" aria-hidden="true"><span class="book-stack"><i></i><i></i><i></i></span><span class="learn-sheet"><b></b><b></b><b></b><em><?= icon('play', 18) ?></em></span><span class="learn-idea"><?= icon('bulb', 22) ?></span></span>
        <span class="feature-copy"><strong>Learning center</strong><span>Learn from short courses and practical resources shared by the community.</span></span>
        <span class="feature-go">Start learning <?= icon('arrow-right', 16) ?></span>
      </a>
      <a class="feature-square feature-square-mentors" data-tour="mentors" href="<?= e(url('mentors')) ?>">
        <span class="feature-top"><span class="feature-number">05</span><span class="feature-icon"><?= icon('compass', 24) ?></span></span>
        <span class="feature-art feature-art-mentors" aria-hidden="true"><span class="mentor-main"><?= icon('user-plus', 40) ?></span><span class="mentor-small mentor-one"><?= icon('users', 19) ?></span><span class="mentor-small mentor-two"><?= icon('chat', 18) ?></span><span class="mentor-node node-one"></span><span class="mentor-node node-two"></span><span class="mentor-node node-three"></span></span>
        <span class="feature-copy"><strong>Mentors</strong><span>Ask for guidance from teachers and experienced members who can help.</span></span>
        <span class="feature-go">Find a mentor <?= icon('arrow-right', 16) ?></span>
      </a>
      <a class="feature-square feature-square-people" data-tour="people" href="<?= e(url('people')) ?>">
        <span class="feature-top"><span class="feature-number">06</span><span class="feature-icon"><?= icon('users', 24) ?></span></span>
        <span class="feature-art feature-art-people" aria-hidden="true"><span class="people-ring"></span><span class="people-person person-main"><?= icon('users', 32) ?></span><span class="people-person person-left"><?= icon('users', 19) ?></span><span class="people-person person-right"><?= icon('users', 19) ?></span><span class="people-person person-low"><?= icon('users', 16) ?></span><i class="people-dot people-dot-a"></i><i class="people-dot people-dot-b"></i><i class="people-dot people-dot-c"></i></span>
        <span class="feature-copy"><strong>People</strong><span>Meet classmates across majors and follow the people you want to learn from.</span></span>
        <span class="feature-go">Meet the community <?= icon('arrow-right', 16) ?></span>
      </a>
    </div>
  </section>

  <?php partial('home_discovery', ['w' => $w]) ?>
  <?php endif ?>

  <section class="community-section" aria-labelledby="feedTitle">
    <div class="section-heading section-heading-inline" data-tour="feed">
      <div><span class="section-kicker"><?= $isHome ? 'Community feed' : 'Browse posts' ?></span><h2 id="feedTitle"><?= $isHome ? 'Ideas from around MANBAR' : 'Community feed' ?></h2></div>
      <?php if (!$isHome): ?><a class="section-link" href="<?= e(url('feed')) ?>"><?= icon('arrow-left', 15) ?> Back to home</a><?php endif ?>
    </div>

    <div data-tour="composer"><?php partial('composer', ['u' => $u, 'compose' => $compose]) ?></div>

    <div class="feed-controls">
      <div class="pill-tabs">
        <a class="chip <?= !$type ? 'on' : 'outline' ?>" href="<?= e($qs(['type' => ''])) ?>">All</a>
        <?php foreach (POST_TYPES as $k => $t): ?><a class="chip <?= $type === $k ? 'on' : 'outline' ?>" href="<?= e($qs(['type' => $k])) ?>"><?= icon($t['icon'], 14) ?> <?= e($t['label']) ?></a><?php endforeach ?>
      </div>
      <div class="tabs"><a class="<?= $sort === 'new' ? 'on' : '' ?>" href="<?= e($qs(['sort' => ''])) ?>">Latest</a><a class="<?= $sort === 'top' ? 'on' : '' ?>" href="<?= e($qs(['sort' => 'top'])) ?>">Top</a><a class="<?= $sort === 'following' ? 'on' : '' ?>" href="<?= e($qs(['sort' => 'following'])) ?>">Following</a></div>
    </div>
    <?php if ($tag || $q): ?><div class="feed-query row"><span class="muted">Showing results for</span><?php if ($tag): ?><span class="chip on">#<?= e($tag) ?></span><?php endif ?><?php if ($q): ?><span class="chip on">“<?= e($q) ?>”</span><?php endif ?><a class="small" href="<?= e(url('feed')) ?>">Clear</a></div><?php endif ?>

    <div class="feed home-feed" id="feed" data-next="<?= $more ? $page + 1 : 0 ?>" data-qs="<?= e(http_build_query(array_filter(['type' => $type, 'tag' => $tag, 'q' => $q, 'sort' => $sort === 'new' ? '' : $sort]))) ?>">
      <?php foreach ($posts as $p) partial('post_card', ['p' => $p, 'u' => $u]) ?>
      <?php if (!$posts): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>Nothing here yet</h3><p>Be the first to post in this space.</p><a class="btn btn-primary" href="<?= e(url('feed?compose=' . ($type ?: 'idea'))) ?>">Create a post</a></div><?php endif ?>
    </div>
    <?php if ($more): ?><div class="pager"><button class="btn btn-ghost" id="loadMore"><?= icon('refresh', 16) ?> Load more</button></div><?php endif ?>
  </section>
</div>
