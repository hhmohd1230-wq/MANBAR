<?php
$title = 'Marketplace';
$qs = fn(array $over) => url('marketplace' . (($p = http_build_query(array_filter(array_merge(['kind' => $kind, 'cat' => $cat, 'q' => $q, 'sort' => $sort !== 'new' ? $sort : ''], $over)))) ? '?' . $p : ''));
?>
<?php partial('market_assets') ?>
<?php if ($isAdmin) partial('market_admin_bar', ['adminMode' => $adminMode, 'back' => 'marketplace']); ?>

<section class="mk-hero hb hb-market">
  <div class="mk-hero-text">
    <span class="mk-kicker"><?= icon('store', 15) ?> AAU student marketplace</span>
    <h1>Hire a classmate, or find your team</h1>
    <p>Design, code, tutoring, writing, video and more, from people on your campus. Check their past work and trust score before you order.</p>
    <div class="mk-hero-cta">
      <a class="btn btn-primary btn-lg hb-btn is-main" href="<?= e(url('marketplace/new')) ?>"><span class="hb-ic"><?= icon('plus', 17) ?></span> Offer a service</a>
      <a class="btn btn-lg mk-btn-light hb-btn is-light tone-blue" href="<?= e(url('marketplace/new?kind=request')) ?>"><span class="hb-ic"><?= icon('user-plus', 17) ?></span> Find people</a>
      <a class="btn btn-lg btn-ghost mk-btn-ghost hb-btn is-ghost tone-amber" href="<?= e(url('marketplace/orders')) ?>"><span class="hb-ic"><?= icon('briefcase', 17) ?></span> My orders</a>
      <a class="btn btn-lg btn-ghost mk-btn-ghost hb-btn is-ghost tone-violet" href="<?= e(url('marketplace/earnings')) ?>"><span class="hb-ic"><?= icon('wallet', 17) ?></span> Wallet</a>
    </div>
  </div>
  <dl class="mk-hero-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('store', 14) ?></span>Listings</dt><dd data-count><?= (int) $stats['listings'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('users', 14) ?></span>Providers</dt><dd data-count><?= (int) $stats['providers'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('check-circle', 14) ?></span>Orders delivered</dt><dd data-count><?= (int) $stats['delivered'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('user-plus', 14) ?></span>Teams hiring</dt><dd data-count><?= (int) $stats['requests'] ?></dd></div>
  </dl>
</section>

<?php if ($top && !$q && !$cat && $kind !== 'request'): ?>
<section class="mk-top" aria-label="Most trusted providers">
  <div class="mk-sec-head"><h2><?= icon('award', 18) ?> Most trusted providers</h2><span class="small muted">Ranked by real orders, on-time delivery, reviews and vouches</span></div>
  <div class="mk-top-grid">
    <?php foreach ($top as $p): $tt = $p['trust']; ?>
      <a class="mk-pro" href="<?= e(url('profile/' . $p['id'])) ?>">
        <?= avatar($p, 52) ?>
        <span class="mk-pro-body"><b><?= e($p['full_name']) ?></b><span class="xs muted"><?= e($p['headline'] ?: $p['major']) ?></span>
          <span class="mk-pro-stats"><span><?= icon('star', 12) ?> <?= $tt['reviews'] ? number_format($tt['rating'], 1) : 'New' ?></span><span><?= (int) $tt['done'] ?> orders</span><?php if ($tt['on_time'] !== null): ?><span><?= (int) $tt['on_time'] ?>% on time</span><?php endif ?></span></span>
        <span class="mk-score" style="--p:<?= (int) $tt['score'] ?>"><b><?= (int) $tt['score'] ?></b></span>
      </a>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<nav class="mk-kinds" aria-label="Listing type">
  <a class="<?= !$kind ? 'on' : '' ?>" href="<?= e($qs(['kind' => ''])) ?>">Everything</a>
  <a class="<?= $kind === 'offer' ? 'on' : '' ?>" href="<?= e($qs(['kind' => 'offer'])) ?>"><?= icon('briefcase', 15) ?> Services</a>
  <a class="<?= $kind === 'request' ? 'on' : '' ?>" href="<?= e($qs(['kind' => 'request'])) ?>"><?= icon('users', 15) ?> Looking for people</a>
</nav>
<div class="mk-cats">
  <a class="chip <?= !$cat ? 'on' : 'outline' ?>" href="<?= e($qs(['cat' => ''])) ?>">All <?= array_sum($counts) ?></a>
  <?php foreach (SERVICE_CATEGORIES as $k => $l): ?><a class="chip <?= $cat === $k ? 'on' : 'outline' ?>" href="<?= e($qs(['cat' => $k])) ?>"><?= icon(SERVICE_ICONS[$k], 14) ?> <?= e($l) ?> <?php if (!empty($counts[$k])): ?><span class="faint"><?= (int) $counts[$k] ?></span><?php endif ?></a><?php endforeach ?>
</div>
<form class="mk-search" method="get"><?php foreach (['kind' => $kind, 'cat' => $cat] as $k => $v) if ($v) echo '<input type="hidden" name="' . $k . '" value="' . e($v) . '">'; ?>
  <div class="search grow"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search services, skills or people needed…"></div>
  <select class="select" name="sort" data-autosubmit aria-label="Sort"><option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option><option value="top" <?= $sort === 'top' ? 'selected' : '' ?>>Top rated</option><option value="orders" <?= $sort === 'orders' ? 'selected' : '' ?>>Most orders</option><option value="cheap" <?= $sort === 'cheap' ? 'selected' : '' ?>>Lowest price</option></select>
</form>

<div class="mk-grid">
  <?php foreach ($services as $s) partial('service_card', ['s' => $s, 't' => $trust[(int) $s['user_id']] ?? null]); ?>
</div>
<?php if (!$services): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3><?= $q || $cat || $kind ? 'Nothing matches yet' : 'No listings yet' ?></h3><p><?= $kind === 'request' ? 'Post what you need and let classmates apply.' : 'Be the first to offer a skill to your campus.' ?></p><a class="btn btn-primary" href="<?= e(url('marketplace/new' . ($kind === 'request' ? '?kind=request' : ''))) ?>"><?= $kind === 'request' ? 'Find people' : 'Offer a service' ?></a></div><?php endif ?>
