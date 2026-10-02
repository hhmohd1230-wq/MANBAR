<?php
$title = 'Home';
$qs = fn(array $o) => url('feed?' . http_build_query(array_filter(array_merge(['type' => $type, 'tag' => $tag, 'q' => $q, 'sort' => $sort === 'new' ? '' : $sort], $o), fn($v) => $v !== '' && $v !== null)));
?>
<div class="page-grid">
  <div class="stack gap-l">
    <?php if (!$compose && $page === 1 && !$type && !$tag && !$q && (int) $u['points'] <= 40): ?>
    <div class="banner">
      <h2>Welcome to MANBAR, <?= e(explode(' ', $u['full_name'])[0]) ?> 🌿</h2>
      <p>Post your first idea, follow a few classmates and join a project. Every step earns points and badges.</p>
      <a class="btn btn-white" href="<?= e(url('feed?compose=idea')) ?>"><?= icon('bulb', 18) ?> Share an idea</a>
    </div>
    <?php endif ?>
    <?php partial('composer', ['u' => $u, 'compose' => $compose]) ?>

    <div class="stack" style="gap:12px">
      <div class="row between wrap">
        <div class="pill-tabs">
          <a class="chip <?= !$type ? 'on' : 'outline' ?>" href="<?= e($qs(['type' => ''])) ?>">All</a>
          <?php foreach (POST_TYPES as $k => $t): ?><a class="chip <?= $type === $k ? 'on' : 'outline' ?>" href="<?= e($qs(['type' => $k])) ?>"><?= icon($t['icon'], 14) ?> <?= e($t['label']) ?></a><?php endforeach ?>
        </div>
        <div class="tabs"><a class="<?= $sort === 'new' ? 'on' : '' ?>" href="<?= e($qs(['sort' => ''])) ?>">Latest</a><a class="<?= $sort === 'top' ? 'on' : '' ?>" href="<?= e($qs(['sort' => 'top'])) ?>">Top</a><a class="<?= $sort === 'following' ? 'on' : '' ?>" href="<?= e($qs(['sort' => 'following'])) ?>">Following</a></div>
      </div>
      <?php if ($tag || $q): ?><div class="row"><span class="muted">Showing results for</span><?php if ($tag): ?><span class="chip on">#<?= e($tag) ?></span><?php endif ?><?php if ($q): ?><span class="chip on">“<?= e($q) ?>”</span><?php endif ?><a class="small" href="<?= e(url('feed')) ?>">Clear</a></div><?php endif ?>
    </div>

    <div class="feed" id="feed" data-next="<?= $more ? $page + 1 : 0 ?>" data-qs="<?= e(http_build_query(array_filter(['type' => $type, 'tag' => $tag, 'q' => $q, 'sort' => $sort === 'new' ? '' : $sort]))) ?>">
      <?php foreach ($posts as $p) partial('post_card', ['p' => $p, 'u' => $u]) ?>
      <?php if (!$posts): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>Nothing here yet</h3><p>Be the first to post in this space.</p><a class="btn btn-primary" href="<?= e(url('feed?compose=' . ($type ?: 'idea'))) ?>">Create a post</a></div><?php endif ?>
    </div>
    <?php if ($more): ?><div class="pager"><button class="btn btn-ghost" id="loadMore"><?= icon('refresh', 16) ?> Load more</button></div><?php endif ?>
  </div>
  <?php partial('aside', ['w' => $w, 'u' => $u]) ?>
</div>
