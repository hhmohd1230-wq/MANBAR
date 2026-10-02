<?php $title = 'My orders'; ?>
<div class="pagehead"><div><h1>My orders &amp; services</h1><p>Track what you’ve requested and what others asked from you.</p></div><a class="btn btn-primary" href="<?= e(url('marketplace/new')) ?>"><?= icon('plus', 17) ?> Offer a service</a></div>
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
