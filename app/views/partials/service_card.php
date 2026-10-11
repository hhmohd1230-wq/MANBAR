<?php
/** One marketplace listing card. Needs $s (listing row with rating, reviews, orders, joined, files) and optional $t (seller_trust()). */
$t = $t ?? null;
$isReq = $s['kind'] === 'request';
$left = $isReq ? max(0, (int) $s['slots'] - (int) ($s['joined'] ?? 0)) : 0;
?>
<article class="card mk-card<?= $isReq ? ' is-request' : '' ?>">
  <a class="mk-cover<?= empty($s['cover_image']) ? ' is-plain' : '' ?>" href="<?= e(url('marketplace/' . $s['id'])) ?>" style="<?= service_cover_css($s) ?>" tabindex="-1" aria-hidden="true">
    <?php if (empty($s['cover_image'])): ?><span class="mk-cover-ico"><?= icon(SERVICE_ICONS[$s['category']] ?? 'tag', 34) ?></span><?php endif ?>
    <span class="mk-cover-tags">
      <span class="mk-tag"><?= e(SERVICE_CATEGORIES[$s['category']] ?? 'Other') ?></span>
      <?php if ($isReq): ?><span class="mk-tag is-hiring"><?= icon('users', 13) ?> Looking for <?= (int) $s['slots'] ?></span><?php endif ?>
    </span>
    <?php if (!empty($s['files'])): ?><span class="mk-cover-files"><?= icon('paperclip', 13) ?> <?= (int) $s['files'] ?></span><?php endif ?>
  </a>
  <div class="mk-body">
    <h3><a href="<?= e(url('marketplace/' . $s['id'])) ?>"><?= e($s['title']) ?></a></h3>
    <div class="mk-seller"><a href="<?= e(url('profile/' . $s['user_id'])) ?>"><?= avatar($s, 26) ?><span><?= e($s['full_name']) ?></span></a><?= verified_badge($s) ?>
      <?php if ($t && !$isReq): ?><span class="mk-trust t-<?= e($t['key']) ?>"><?= icon('shield', 12) ?> <?= e($t['label']) ?></span><?php endif ?></div>
    <div class="mk-meta">
      <?php if ($isReq): ?>
        <span class="mk-spots"><b><?= (int) ($s['joined'] ?? 0) ?>/<?= (int) $s['slots'] ?></b> joined</span>
        <span><?= $left ? icon('user-plus', 13) . ' ' . $left . ' spot' . ($left === 1 ? '' : 's') . ' left' : icon('check-circle', 13) . ' Team full' ?></span>
      <?php else: ?>
        <?php if ($s['reviews']): ?><span class="mk-rate"><?= icon('star', 13) ?> <b><?= number_format((float) $s['rating'], 1) ?></b> <span class="muted">(<?= (int) $s['reviews'] ?>)</span></span><?php else: ?><span class="muted">New</span><?php endif ?>
        <?php if ($s['orders']): ?><span><?= icon('check-circle', 13) ?> <?= (int) $s['orders'] ?> delivered</span><?php endif ?>
      <?php endif ?>
      <span><?= icon('clock', 13) ?> <?= (int) $s['delivery_days'] ?>d</span>
    </div>
  </div>
  <div class="mk-foot"><span class="mk-price-k"><?= $isReq ? 'Budget' : 'Price' ?></span><b class="mk-price"><?= e(service_price_label($s)) ?></b></div>
</article>
