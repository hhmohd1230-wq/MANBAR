<?php /** @var array $w widgets; @var array $u */ ?>
<aside class="aside">
  <div class="card">
    <div class="card-title"><h3><?= icon('trending', 18) ?> Trending tags</h3></div>
    <?php if ($w['tags']): ?><div class="tag-cloud"><?php foreach ($w['tags'] as $t => $n): ?><a class="chip" href="<?= e(url('feed?tag=' . urlencode($t))) ?>">#<?= e($t) ?> <span class="faint"><?= $n ?></span></a><?php endforeach ?></div>
    <?php else: ?><p class="muted small">Tags appear as students post. Add #tags to your posts!</p><?php endif ?>
  </div>
  <?php if ($w['people']): ?>
  <div class="card">
    <div class="card-title"><h3><?= icon('user-plus', 18) ?> People to follow</h3><a class="small" href="<?= e(url('people')) ?>">See all</a></div>
    <div class="w-list">
      <?php foreach ($w['people'] as $p): ?>
        <div class="person"><a href="<?= e(url('profile/' . $p['id'])) ?>"><?= avatar($p, 40) ?></a>
          <div class="grow"><a class="nm" href="<?= e(url('profile/' . $p['id'])) ?>"><?= e($p['full_name']) ?></a><?= verified_badge($p) ?><div class="xs muted"><?= e($p['major'] ?: $p['headline'] ?: 'Student') ?></div></div>
          <button class="btn btn-sm" data-follow="<?= (int) $p['id'] ?>">Follow</button></div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>
  <?php if ($w['events']): ?>
  <div class="card">
    <div class="card-title"><h3><?= icon('calendar', 18) ?> Latest events</h3><a class="small" href="<?= e(url('feed?type=event')) ?>">All</a></div>
    <div class="w-list"><?php foreach ($w['events'] as $ev): ?><a href="<?= e(url('post/' . $ev['id'])) ?>" class="row" style="color:var(--ink)"><span class="badge-ico tone-orange" style="width:40px;height:40px;margin:0;border-radius:13px"><?= icon('calendar', 19) ?></span><span class="grow"><b class="small"><?= e(excerpt($ev['title'], 48)) ?></b><br><span class="xs muted"><?= e(time_ago($ev['created_at'])) ?></span></span></a><?php endforeach ?></div>
  </div>
  <?php endif ?>
  <div class="card">
    <div class="card-title"><h3><?= icon('trophy', 18) ?> Top contributors</h3><a class="small" href="<?= e(url('achievements')) ?>">Leaderboard</a></div>
    <div class="w-list"><?php foreach ($w['top'] as $i => $t): ?><div class="person"><span class="rank r<?= $i + 1 ?>"><?= $i + 1 ?></span><a href="<?= e(url('profile/' . $t['id'])) ?>"><?= avatar($t, 34) ?></a><a class="nm grow" href="<?= e(url('profile/' . $t['id'])) ?>"><?= e($t['full_name']) ?></a><b class="small" style="color:var(--g700)"><?= (int) $t['points'] ?> pts</b></div><?php endforeach ?></div>
  </div>
</aside>
