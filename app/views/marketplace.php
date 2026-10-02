<?php $title = 'Marketplace'; ?>
<div class="pagehead"><div><h1>Student marketplace</h1><p>Offer your skills or hire a classmate — design, code, tutoring, writing and more.</p></div>
  <div class="row"><a class="btn btn-ghost" href="<?= e(url('marketplace/orders')) ?>"><?= icon('briefcase', 17) ?> My orders</a><a class="btn btn-primary" href="<?= e(url('marketplace/new')) ?>"><?= icon('plus', 17) ?> Offer a service</a></div></div>
<div class="row wrap mb" style="gap:8px">
  <a class="chip <?= !$cat ? 'on' : 'outline' ?>" href="<?= e(url('marketplace')) ?>">All <?= array_sum($counts) ?></a>
  <?php foreach (SERVICE_CATEGORIES as $k => $l): ?><a class="chip <?= $cat === $k ? 'on' : 'outline' ?>" href="<?= e(url('marketplace?cat=' . $k)) ?>"><?= icon(SERVICE_ICONS[$k], 14) ?> <?= e($l) ?> <?php if (!empty($counts[$k])): ?><span class="faint"><?= (int) $counts[$k] ?></span><?php endif ?></a><?php endforeach ?>
</div>
<form class="row wrap mb" method="get"><?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif ?>
  <div class="search grow" style="min-width:240px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search services…"></div>
  <select class="select" name="sort" style="width:auto" data-autosubmit><option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option><option value="top" <?= $sort === 'top' ? 'selected' : '' ?>>Top rated</option><option value="cheap" <?= $sort === 'cheap' ? 'selected' : '' ?>>Lowest price</option></select>
</form>
<div class="cards">
  <?php foreach ($services as $s): ?>
  <article class="card item">
    <div class="item-cover" style="<?= theme_css(crc32($s['category']) % 6) ?>"><span class="chip sm" style="background:rgba(255,255,255,.9)"><?= e(SERVICE_CATEGORIES[$s['category']] ?? 'Other') ?></span><span class="ic-big"><?= icon(SERVICE_ICONS[$s['category']] ?? 'tag', 30) ?></span></div>
    <div class="item-body">
      <h3><a href="<?= e(url('marketplace/' . $s['id'])) ?>"><?= e($s['title']) ?></a></h3>
      <p><?= e(excerpt($s['description'], 100)) ?></p>
      <div class="row small"><?= $s['reviews'] ? stars((float) $s['rating']) . ' <b>' . number_format($s['rating'], 1) . '</b> <span class="muted">(' . (int) $s['reviews'] . ')</span>' : '<span class="muted">New</span>' ?></div>
      <div class="item-foot"><a class="row gap-s small" href="<?= e(url('profile/' . $s['user_id'])) ?>" style="color:var(--ink2)"><?= avatar($s, 28) ?> <?= e(explode(' ', $s['full_name'])[0]) ?><?= $s['role'] !== 'student' ? ' <span class="role-pill">' . e(role_label($s['role'])) . '</span>' : '' ?></a><div class="price"><?= $s['price'] ? 'AED ' . (int) $s['price'] : 'Free' ?> <small>/ <?= (int) $s['delivery_days'] ?>d</small></div></div>
    </div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$services): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No services yet</h3><p>Be the first to offer a skill to your campus.</p><a class="btn btn-primary" href="<?= e(url('marketplace/new')) ?>">Offer a service</a></div><?php endif ?>
