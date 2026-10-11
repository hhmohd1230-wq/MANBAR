<?php $title = 'My orders'; ?>
<div class="pagehead"><div><h1>My orders &amp; services</h1><p>Track what you’ve requested and what others asked from you.</p></div><div class="row wrap"><a class="btn" href="<?= e(url('marketplace/earnings')) ?>"><?= icon('wallet', 17) ?> Wallet · AED <?= number_format($wallet['available']) ?><?= $wallet['held'] ? ' <span class="xs muted">(+' . number_format($wallet['held']) . ' on hold)</span>' : '' ?></a><a class="btn btn-primary" href="<?= e(url('marketplace/new')) ?>"><?= icon('plus', 17) ?> Offer a service</a></div></div>
<?php partial('market_assets') ?>
<?php if ($mine): ?>
<section class="card mk-mytrust">
  <span class="mk-score is-lg" style="--p:<?= (int) $trust['score'] ?>"><b><?= (int) $trust['score'] ?></b></span>
  <div class="grow"><b class="mk-mytrust-label"><?= e($trust['label']) ?></b><span class="small muted">Your trust score as a provider. It grows with delivered orders, on-time delivery, quick replies, good reviews and teammate vouches.</span></div>
  <dl class="mk-tstats is-row">
    <div><dt>Delivered</dt><dd><?= (int) $trust['done'] ?></dd></div>
    <div><dt>On time</dt><dd><?= $trust['on_time'] !== null ? (int) $trust['on_time'] . '%' : '—' ?></dd></div>
    <div><dt>Rating</dt><dd><?= $trust['reviews'] ? number_format($trust['rating'], 1) . ' ★' : '—' ?></dd></div>
    <div><dt>Responds</dt><dd class="sm"><?= e(response_label($trust['response_h'])) ?></dd></div>
  </dl>
</section>
<?php endif ?>
<div class="stack gap-l">
  <section><h3>Requests I received <span class="chip sm"><?= count($sold) ?></span></h3>
    <div class="table-wrap"><table class="t"><thead><tr><th>Buyer</th><th>Service</th><th>Status</th><th>When</th><th></th></tr></thead><tbody>
    <?php foreach ($sold as $r): ?><tr><td><span class="row gap-s"><?= avatar($r, 30) ?><?= e($r['buyer']) ?></span></td><td><a href="<?= e(url('marketplace/' . $r['sid'])) ?>"><?= e($r['title']) ?></a></td><td><span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td><td class="muted small"><?= e(time_ago($r['created_at'])) ?></td><td><a class="btn btn-sm" href="<?= e(url('marketplace/' . $r['sid'])) ?>">Manage</a></td></tr><?php endforeach ?>
    <?php if (!$sold): ?><tr><td colspan="5" class="muted tc" style="padding:28px">No incoming requests yet.</td></tr><?php endif ?></tbody></table></div></section>
  <section><h3>Services I ordered <span class="chip sm"><?= count($bought) ?></span></h3>
    <div class="table-wrap"><table class="t"><thead><tr><th>Service</th><th>Provider</th><th>Price</th><th>Status</th><th>When</th></tr></thead><tbody>
    <?php foreach ($bought as $r): ?><tr><td><a href="<?= e(url('marketplace/' . $r['sid'])) ?>"><?= e($r['title']) ?></a></td><td><?= e($r['provider']) ?></td><td><?= $r['price'] ? 'AED ' . (int) $r['price'] : 'Free' ?></td><td><span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td><td class="muted small"><?= e(time_ago($r['created_at'])) ?></td></tr><?php endforeach ?>
    <?php if (!$bought): ?><tr><td colspan="5" class="muted tc" style="padding:28px">You haven’t requested anything yet. <a href="<?= e(url('marketplace')) ?>">Browse the marketplace</a></td></tr><?php endif ?></tbody></table></div></section>
  <section><h3>My listings <span class="chip sm"><?= count($mine) ?></span></h3>
    <div class="cards"><?php foreach ($mine as $s): ?><a class="card" href="<?= e(url('marketplace/' . $s['id'])) ?>" style="color:var(--ink)"><div class="row between"><b><?= e($s['title']) ?></b><span class="status-pill st-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></div><div class="row between small muted" style="margin-top:8px"><span><?= $s['price'] ? 'AED ' . (int) $s['price'] : 'Free' ?></span><span><?= (int) $s['orders'] ?> orders</span></div></a><?php endforeach ?>
    <?php if (!$mine): ?><div class="muted small">No listings yet.</div><?php endif ?></div></section>
</div>
