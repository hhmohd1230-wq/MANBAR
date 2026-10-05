<?php /** @var array $w */ ?>
<section class="home-discovery" aria-labelledby="discoverTitle">
  <div class="section-heading section-heading-inline" data-tour="discover">
    <div><span class="section-kicker">Campus pulse</span><h2 id="discoverTitle">Find what is happening now</h2></div>
    <a class="section-link" href="<?= e(url('people')) ?>">Browse the community <?= icon('arrow-right', 15) ?></a>
  </div>
  <div class="discovery-grid">
    <article class="discovery-panel discovery-tags">
      <div class="discovery-title"><span class="discovery-icon"><?= icon('trending', 19) ?></span><div><span>Trending now</span><small>Topics gaining attention</small></div></div>
      <?php if ($w['tags']): ?><div class="tag-cloud"><?php foreach ($w['tags'] as $t => $n): ?><a class="chip" href="<?= e(url('feed?tag=' . urlencode($t))) ?>">#<?= e($t) ?> <span class="faint"><?= $n ?></span></a><?php endforeach ?></div>
      <?php else: ?><p class="muted small">Start a conversation and add a tag to help others find it.</p><?php endif ?>
    </article>

    <article class="discovery-panel">
      <div class="discovery-title"><span class="discovery-icon"><?= icon('user-plus', 19) ?></span><div><span>People to know</span><small>Students and mentors to follow</small></div></div>
      <?php if ($w['people']): ?><div class="people-row"><?php foreach (array_slice($w['people'], 0, 3) as $p): ?>
        <div class="person-compact"><a href="<?= e(url('profile/' . $p['id'])) ?>"><?= avatar($p, 38) ?></a><div class="grow"><a class="nm" href="<?= e(url('profile/' . $p['id'])) ?>"><?= e(excerpt($p['full_name'], 22)) ?></a><div class="xs muted"><?= e(excerpt($p['major'] ?: $p['headline'] ?: 'Student', 26)) ?></div></div><button class="mini-action" data-follow="<?= (int) $p['id'] ?>" aria-label="Follow <?= e($p['full_name']) ?>"><?= icon('plus', 15) ?></button></div>
      <?php endforeach ?></div><?php else: ?><p class="muted small">You are all caught up with follow suggestions.</p><?php endif ?>
    </article>

    <article class="discovery-panel">
      <div class="discovery-title"><span class="discovery-icon"><?= icon('calendar', 19) ?></span><div><span>Coming up</span><small>Events from your community</small></div></div>
      <?php if ($w['events']): ?><div class="event-stack"><?php foreach (array_slice($w['events'], 0, 2) as $ev): ?><a href="<?= e(url('post/' . $ev['id'])) ?>"><span class="event-mark"><?= icon('calendar', 16) ?></span><span class="grow"><b><?= e(excerpt($ev['title'], 42)) ?></b><small><?= e(time_ago($ev['created_at'])) ?></small></span><?= icon('arrow-right', 15) ?></a><?php endforeach ?></div>
      <?php else: ?><p class="muted small">New events will appear here as they are posted.</p><?php endif ?>
    </article>
  </div>
</section>
