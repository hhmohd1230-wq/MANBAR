<?php
$title = $s['title'];
$sid = (int) $s['id'];
$isReq = $s['kind'] === 'request';
$pk = service_packages($s);
$includes = service_lines($s['includes']);
$skills = service_skills($s['skills']);
$proof = array_values(array_filter($files, fn($f) => (int) $f['is_proof'] === 1));
$samples = array_values(array_filter($files, fn($f) => (int) $f['is_proof'] !== 1));
$spotsLeft = max(0, (int) $s['slots'] - count($members));
$open = $myReq && in_array($myReq['status'], ['pending', 'offered', 'accepted', 'delivered'], true);
$tracking = !$isReq && $myReq && !in_array($myReq['status'], ['declined', 'cancelled'], true);   // show the buyer's order steps
?>
<?php partial('market_assets') ?>
<?php partial('back_button', ['href' => 'marketplace' . ($isReq ? '?kind=request' : ''), 'label' => 'Back to marketplace']) ?>
<?php if ($isAdmin && !$mine) partial('market_admin_bar', ['adminMode' => $adminMode, 'back' => "marketplace/$sid"]); ?>

<section class="card lc-hero2 mk-hero2">
  <div class="lc-banner<?= empty($s['cover_image']) ? ' is-plain' : '' ?>" style="<?= service_cover_css($s) ?>">
    <div class="lc-banner-top">
      <span class="lc-pill"><?= icon(SERVICE_ICONS[$s['category']] ?? 'tag', 14) ?> <?= e(SERVICE_CATEGORIES[$s['category']] ?? 'Other') ?></span>
      <span class="lc-pill<?= $isReq ? ' is-warn' : '' ?>"><?= $isReq ? icon('users', 14) . ' Looking for people' : icon('briefcase', 14) . ' Service' ?></span>
      <?php if ($s['status'] !== 'active'): ?><span class="lc-pill is-warn"><?= e(ucfirst($s['status'])) ?></span><?php endif ?>
      <?php if ($canEdit): ?><form class="lc-cover-form" method="post" action="<?= e(url("marketplace/$sid/cover")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
        <label class="lc-pill is-btn"><input type="file" name="cover" accept="image/png,image/jpeg,image/webp,image/gif" data-autosubmit hidden><?= icon('image', 14) ?> <?= empty($s['cover_image']) ? 'Add a cover picture' : 'Change cover' ?></label>
        <a class="lc-pill is-btn" href="<?= e(url("marketplace/$sid/edit")) ?>"><?= icon('edit', 14) ?> Edit listing</a></form><?php endif ?>
    </div>
    <div class="lc-banner-body">
      <h1><?= e($s['title']) ?></h1>
      <a class="lc-hostline" href="<?= e(url('profile/' . $s['user_id'])) ?>"><?= avatar($s, 30) ?><span><?= $isReq ? 'Posted' : 'Offered' ?> by <b><?= e($s['full_name']) ?></b><?= $s['role'] !== 'student' ? ' · ' . e(role_label($s['role'])) : '' ?></span></a>
    </div>
  </div>
  <div class="mk-quick">
    <?php if ($isReq): ?>
      <div><dt><?= icon('users', 14) ?> People needed</dt><dd><?= (int) $s['slots'] ?></dd></div>
      <div><dt><?= icon('user-plus', 14) ?> Joined</dt><dd><?= count($members) ?></dd></div>
      <div><dt><?= icon('clock', 14) ?> Timeline</dt><dd><?= (int) $s['delivery_days'] ?> days</dd></div>
      <div><dt><?= icon('wallet', 14) ?> Budget</dt><dd><?= e(service_price_label($s)) ?></dd></div>
    <?php else: ?>
      <div><dt><?= icon('star', 14) ?> Rating</dt><dd><?= $reviews ? number_format($avg, 1) . ' <small>(' . count($reviews) . ')</small>' : '<small>New</small>' ?></dd></div>
      <div><dt><?= icon('check-circle', 14) ?> Delivered</dt><dd><?= $orders ?> <small>time<?= $orders === 1 ? '' : 's' ?></small></dd></div>
      <div><dt><?= icon('clock', 14) ?> Delivery</dt><dd><?= (int) $s['delivery_days'] ?> <small>day<?= (int) $s['delivery_days'] === 1 ? '' : 's' ?></small></dd></div>
      <div><dt><?= icon('refresh', 14) ?> Revisions</dt><dd><?= (int) $s['revisions'] ?: '—' ?></dd></div>
    <?php endif ?>
  </div>
</section>

<div class="page-grid">
  <div class="stack gap-l">
    <section class="card">
      <h2 class="lc-h2"><?= $isReq ? 'What we need' : 'About this service' ?></h2>
      <div class="prose mk-desc"><?= rich($s['description']) ?></div>
      <?php if ($includes): ?>
        <h3 class="mk-h3"><?= $isReq ? 'You will' : 'What you get' ?></h3>
        <ul class="mk-checks"><?php foreach ($includes as $l): ?><li><?= icon('check', 15) ?> <?= e($l) ?></li><?php endforeach ?></ul>
      <?php endif ?>
      <?php if ($skills): ?><h3 class="mk-h3"><?= $isReq ? 'Skills we’re looking for' : 'Skills' ?></h3><div class="mk-skills"><?php foreach ($skills as $k): ?><span><?= e($k) ?></span><?php endforeach ?></div><?php endif ?>
    </section>

    <?php if ($files || $canEdit): ?>
    <section class="card" id="portfolio">
      <div class="card-title"><h2 class="lc-h2"><?= $isReq ? 'Files & brief' : 'Portfolio & proof of work' ?></h2><span class="chip sm"><?= count($files) ?></span></div>
      <?php if ($proof): ?>
        <div class="mk-proof-head"><?= icon('award', 16) ?> <b>Past work</b><span class="muted small">Projects <?= e(first_name($s['full_name'])) ?> already delivered</span></div>
        <?php partial('service_portfolio', ['items' => $proof, 'sid' => $sid, 'canEdit' => $canEdit]) ?>
      <?php endif ?>
      <?php if ($samples): ?>
        <?php if ($proof): ?><div class="mk-proof-head"><?= icon('paperclip', 16) ?> <b>Samples &amp; files</b></div><?php endif ?>
        <?php partial('service_portfolio', ['items' => $samples, 'sid' => $sid, 'canEdit' => $canEdit]) ?>
      <?php endif ?>
      <?php if (!$files): ?><p class="muted small">Show buyers what you can do: add pictures of past work, PDFs, a whole project folder or links.</p><?php endif ?>
      <?php if ($canEdit): ?>
        <details class="mk-add"<?= $files ? '' : ' open' ?>>
          <summary><?= icon('upload', 16) ?> Add files, a folder or links</summary>
          <form method="post" action="<?= e(url("marketplace/$sid/files")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
            <?php partial('service_upload_fields') ?>
            <button class="btn btn-primary mt" type="submit"><?= icon('plus', 16) ?> Add to portfolio</button>
          </form>
        </details>
      <?php endif ?>
    </section>
    <?php endif ?>

    <?php if ($mine && $incoming): ?>
    <section class="card" id="requests"><div class="card-title"><h2 class="lc-h2"><?= $isReq ? 'Applications' : 'Orders for this service' ?></h2><span class="chip sm"><?= count($incoming) ?></span></div>
      <div class="stack"><?php foreach ($incoming as $r): $pkgRow = null; foreach ($pk as $p) if ($p['key'] === $r['package']) $pkgRow = $p; $money = $paidBy[(int) $r['id']] ?? ['paid' => 0, 'held' => 0]; ?>
        <div class="mk-req">
          <a href="<?= e(url('profile/' . $r['buyer_id'])) ?>"><?= avatar($r, 44) ?></a>
          <div class="grow">
            <div class="row wrap gap-s"><b><?= e($r['full_name']) ?></b><span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span><?php if ($pkgRow): ?><span class="chip sm"><?= e($pkgRow['name']) ?></span><?php endif ?><span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span></div>
            <div class="xs muted"><?= e($r['major'] ?: '') ?></div><p class="small" style="margin:4px 0 0"><?= e($r['message'] ?: 'No message.') ?></p>
            <?php if (!$isReq): ?>
              <?php if ($r['status'] === 'pending'): ?>
                <details class="mk-offerform" open><summary><?= icon('briefcase', 15) ?> Send an offer</summary>
                  <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>"><?= csrf_field() ?><input type="hidden" name="act" value="offer">
                    <div class="grid3"><div class="field"><label class="f">Price (AED)</label><input class="input" type="number" name="price" min="0" max="100000" value="<?= (int) ($pkgRow['price'] ?? $s['price']) ?>"></div>
                      <div class="field"><label class="f">Delivery (days)</label><input class="input" type="number" name="days" min="1" max="90" value="<?= (int) ($pkgRow['days'] ?? $s['delivery_days']) ?>"></div>
                      <div class="field"><label class="f">When they pay</label><select class="select" name="terms"><?php foreach (PAY_TERMS as $k => [$l]): if ($k === 'free') continue; ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach ?></select></div></div>
                    <div class="field"><label class="f">Note (optional)</label><input class="input" name="note" maxlength="500" placeholder="What’s included, when you can start…"></div>
                    <div class="row wrap"><button class="btn btn-primary btn-sm" type="submit"><?= icon('send', 14) ?> Send offer</button><span class="xs muted">Price 0 = free. Paid online orders are held by MANBAR until the buyer confirms.</span></div>
                  </form>
                  <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>" class="mt"><?= csrf_field() ?><input type="hidden" name="act" value="decline"><button class="btn btn-ghost btn-sm" type="submit">Decline request</button></form>
                </details>
              <?php elseif ($r['status'] === 'offered'): ?>
                <div class="mk-dealline"><?= icon('clock', 14) ?> Offer sent: <b><?= (int) $r['agreed_price'] ? 'AED ' . (int) $r['agreed_price'] : 'free' ?></b> · <?= (int) $r['agreed_days'] ?> days · <?= e(strtolower(PAY_TERMS[$r['pay_terms']][0] ?? '')) ?>. Waiting for them to accept.</div>
              <?php elseif ($r['status'] === 'accepted'): ?>
                <div class="mk-dealline is-go"><?= icon('briefcase', 14) ?> In progress · <?= $money['held'] ? 'AED ' . $money['held'] . ' paid and held by MANBAR' : ((int) $r['agreed_price'] ? 'AED ' . (int) $r['agreed_price'] . ' ' . ($r['pay_terms'] === 'cash' ? 'in cash' : 'on delivery') : 'free order') ?></div>
                <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>" class="row wrap mt"><?= csrf_field() ?><input type="hidden" name="act" value="deliver"><input class="input" name="note" maxlength="500" placeholder="Delivery note or link (optional)" style="flex:1;min-width:200px"><button class="btn btn-primary btn-sm" type="submit"><?= icon('check', 14) ?> Mark delivered</button></form>
              <?php elseif ($r['status'] === 'delivered'): ?>
                <div class="mk-dealline"><?= icon('clock', 14) ?> Delivered · waiting for <?= e(first_name($r['full_name'])) ?> to confirm<?= $money['held'] ? ' and release AED ' . $money['held'] : '' ?>.</div>
              <?php elseif ($r['status'] === 'completed'): ?>
                <div class="mk-dealline is-done"><?= icon('check-circle', 14) ?> Completed<?= $money['paid'] ? ' · AED ' . $money['paid'] . ' earned' : '' ?></div>
              <?php endif ?>
            <?php endif ?>
          </div>
          <div class="mk-req-side">
            <?php if ($isReq): ?><form method="post" action="<?= e(url("marketplace/$sid/request/{$r['id']}")) ?>" class="row wrap"><?= csrf_field() ?>
              <?php if ($r['status'] === 'pending'): ?><button class="btn btn-primary btn-sm" name="decision" value="accept">Accept</button><button class="btn btn-ghost btn-sm" name="decision" value="decline">Decline</button>
              <?php elseif ($r['status'] === 'accepted'): ?><button class="btn btn-sm" name="decision" value="complete"><?= icon('check', 14) ?> Mark done</button><?php endif ?></form><?php endif ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('messages/' . $r['buyer_id'])) ?>"><?= icon('message', 14) ?> Chat</a>
          </div>
        </div><?php endforeach ?></div></section>
    <?php endif ?>

    <?php if (!$isReq): ?>
    <section class="card" id="reviews"><div class="card-title"><h2 class="lc-h2">Reviews</h2></div>
      <?php if ($reviews): ?>
        <div class="mk-rsum">
          <div class="mk-rbig"><b><?= number_format($avg, 1) ?></b><?= stars($avg) ?><span class="small muted"><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></span></div>
          <div class="mk-rbars"><?php for ($i = 5; $i >= 1; $i--): $pc = count($reviews) ? round($dist[$i] / count($reviews) * 100) : 0; ?>
            <div><span><?= $i ?> ★</span><span class="mk-rbar"><i style="width:<?= $pc ?>%"></i></span><span class="muted"><?= $dist[$i] ?></span></div><?php endfor ?></div>
        </div>
      <?php endif ?>
      <?php if ($canReview): ?>
        <form method="post" action="<?= e(url("marketplace/$sid/review")) ?>" class="card flat mb" style="background:var(--g50)" data-ai-writing-form data-ai-context="marketplace review"><?= csrf_field() ?>
          <b>How was your order?</b>
          <div class="row" style="margin:8px 0"><select class="select" name="rating" style="width:auto"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= str_repeat('★', $i) ?> <?= $i ?></option><?php endfor ?></select></div>
          <textarea class="textarea" name="comment" data-ai-writing="body" style="min-height:70px" placeholder="Share a few words (optional)"></textarea><div class="writing-assist" data-writing-assist hidden aria-live="polite"></div><button class="btn btn-primary mt" type="submit">Submit review</button>
        </form><?php endif ?>
      <div class="mk-reviews"><?php foreach ($reviews as $r): ?>
        <div class="mk-review"><?= avatar($r, 40) ?><div class="grow"><div class="row wrap gap-s"><b><?= e($r['full_name']) ?></b><?= stars((float) $r['rating']) ?><span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span></div><?php if ($r['major']): ?><div class="xs muted"><?= e($r['major']) ?></div><?php endif ?><p><?= e($r['comment']) ?></p></div></div><?php endforeach ?>
        <?php if (!$reviews): ?><p class="muted small">Reviews appear after a completed order.</p><?php endif ?></div>
    </section>
    <?php endif ?>
  </div>

  <aside class="aside">
    <div class="card mk-order">
      <?php if ($isReq): ?>
        <span class="lc-action-k">Spots</span>
        <b class="lc-action-big"><?= count($members) ?> of <?= (int) $s['slots'] ?> joined</b>
        <div class="mk-slots"><?php for ($i = 0; $i < (int) $s['slots']; $i++): $m = $members[$i] ?? null; ?><span class="<?= $m ? 'is-on' : '' ?>" title="<?= $m ? e($m['full_name']) : 'Open spot' ?>"><?= $m ? avatar($m, 34) : icon('plus', 14) ?></span><?php endfor ?></div>
        <div class="small muted mk-order-meta"><?= icon('wallet', 14) ?> Budget <?= e(service_price_label($s)) ?> · <?= icon('clock', 14) ?> <?= (int) $s['delivery_days'] ?> days</div>
      <?php elseif ($pk): ?>
        <span class="lc-action-k">Choose a package</span>
      <?php else: ?>
        <div class="price" style="font-size:30px"><?= e(service_price_label($s)) ?></div>
        <div class="small muted mk-order-meta"><?= icon('clock', 14) ?> <?= (int) $s['delivery_days'] ?>-day delivery · <?= icon('refresh', 14) ?> <?= (int) $s['revisions'] ?> revision<?= (int) $s['revisions'] === 1 ? '' : 's' ?></div>
      <?php endif ?>

      <?php if ($canEdit): ?>
        <?php if ($pk): ?><div class="mk-pkgs is-static"><?php foreach ($pk as $p): ?><div class="mk-pkg"><span class="mk-pkg-top"><b><?= e($p['name']) ?></b><b>AED <?= (int) $p['price'] ?></b></span><span class="small muted"><?= e($p['desc']) ?></span><span class="xs muted"><?= icon('clock', 12) ?> <?= (int) $p['days'] ?> days</span></div><?php endforeach ?></div><?php endif ?>
        <div class="stack gap-s mt"><span class="chip"><?= $mine ? 'This is your listing' : 'Admin: you can manage this listing' ?></span>
          <a class="btn btn-block" href="<?= e(url("marketplace/$sid/edit")) ?>"><?= icon('edit', 16) ?> Edit listing</a>
          <form method="post" action="<?= e(url("marketplace/$sid/toggle")) ?>"><?= csrf_field() ?><button class="btn btn-block btn-ghost"><?= $s['status'] === 'active' ? 'Pause listing' : 'Re-activate' ?></button></form>
          <form method="post" action="<?= e(url("marketplace/$sid/toggle")) ?>" data-confirm="Delete this listing and its files?"><?= csrf_field() ?><input type="hidden" name="delete" value="1"><button class="btn btn-danger btn-block btn-sm"><?= icon('trash', 14) ?> Delete</button></form></div>
      <?php elseif ($tracking && ($open || $myReq['status'] === 'completed')): ?>
        <?php partial('order_tracker', ['r' => $myReq, 's' => $s, 'money' => $paidBy[(int) $myReq['id']] ?? []]) ?>
        <?php if ($myReq['status'] === 'completed' && $s['status'] === 'active'): ?><details class="mk-again"><summary><?= icon('refresh', 15) ?> Order again</summary><?php partial('service_request_form', ['s' => $s, 'pk' => $pk, 'isReq' => $isReq]) ?></details><?php endif ?>
      <?php elseif ($open): ?>
        <div class="chip st-<?= e($myReq['status']) ?> mt">Your application is <?= e($myReq['status']) ?></div><a class="btn btn-block mt" href="<?= e(url('messages/' . $s['user_id'])) ?>"><?= icon('message', 16) ?> Chat with <?= e(first_name($s['full_name'])) ?></a>
      <?php elseif ($s['status'] === 'active' && (!$isReq || $spotsLeft)): ?>
        <?php partial('service_request_form', ['s' => $s, 'pk' => $pk, 'isReq' => $isReq]) ?>
      <?php elseif ($isReq && !$spotsLeft): ?>
        <div class="chip mt"><?= icon('check-circle', 14) ?> All spots are filled</div>
      <?php endif ?>
    </div>

    <div class="card mk-seller-card">
      <div class="card-title"><h3><?= $isReq ? 'Posted by' : 'About the provider' ?></h3></div>
      <a class="person" href="<?= e(url('profile/' . $s['user_id'])) ?>"><?= avatar($s, 52) ?><div class="grow"><span class="nm"><?= e($s['full_name']) ?></span><?= verified_badge($s) ?><div class="xs muted"><?= e($s['headline'] ?: $s['major'] ?: role_label($s['role'])) ?></div></div></a>
      <?php if (!$isReq): ?>
        <div class="mk-trustbox t-<?= e($trust['key']) ?>">
          <span class="mk-score is-lg" style="--p:<?= (int) $trust['score'] ?>"><b><?= (int) $trust['score'] ?></b></span>
          <span><b><?= e($trust['label']) ?></b><span class="xs muted">Trust score out of 100, from real orders, reviews and vouches</span></span>
        </div>
        <dl class="mk-tstats">
          <div><dt>Orders delivered</dt><dd><?= (int) $trust['done'] ?></dd></div>
          <div><dt>On-time delivery</dt><dd><?= $trust['on_time'] !== null ? (int) $trust['on_time'] . '%' : '—' ?></dd></div>
          <div><dt>Rating</dt><dd><?= $trust['reviews'] ? number_format($trust['rating'], 1) . ' ★' : '—' ?></dd></div>
          <div><dt>Responds</dt><dd class="sm"><?= e(response_label($trust['response_h'])) ?></dd></div>
          <div><dt>Repeat clients</dt><dd><?= (int) $trust['repeat'] ?></dd></div>
          <div><dt>Teammate vouches</dt><dd><?= (int) $trust['vouches'] ?></dd></div>
        </dl>
      <?php endif ?>
      <ul class="mk-since">
        <?php if ($trust['since']): ?><li><?= icon('briefcase', 14) ?> <?= $isReq ? 'On the marketplace since' : 'Providing services since' ?> <b><?= e(since_label($trust['since'])) ?></b></li><?php endif ?>
        <?php if ($s['member_since']): ?><li><?= icon('calendar', 14) ?> MANBAR member since <b><?= e(date('M Y', strtotime($s['member_since']))) ?></b></li><?php endif ?>
        <?php if ($trust['clients']): ?><li><?= icon('users', 14) ?> Worked with <b><?= (int) $trust['clients'] ?> client<?= $trust['clients'] === 1 ? '' : 's' ?></b></li><?php endif ?>
      </ul>
      <?php foreach ($vouches as $v): ?>
        <blockquote class="mk-vouch"><p>“<?= e(excerpt($v['body'], 150)) ?>”</p><footer><?= avatar($v, 22) ?> <?= e($v['full_name']) ?><?= $v['project'] ? ' · ' . e(excerpt($v['project'], 30)) : '' ?></footer></blockquote>
      <?php endforeach ?>
      <?php if (!$mine): ?><div class="row mt"><a class="btn btn-sm btn-block" href="<?= e(url('profile/' . $s['user_id'])) ?>">View profile</a><a class="btn btn-sm btn-ghost" href="<?= e(url('messages/' . $s['user_id'])) ?>" aria-label="Message"><?= icon('message', 14) ?></a><button class="btn btn-sm btn-ghost" data-report="service" data-id="<?= $sid ?>" aria-label="Report"><?= icon('flag', 14) ?></button></div>
        <div class="sr-line"><?php partial('scam_report', ['target' => ['id' => $s['user_id'], 'full_name' => $s['full_name']], 'me' => $u['id'], 'back' => 'marketplace/' . $sid, 'btnClass' => 'sr-link', 'label' => 'Something wrong? Report a scam']) ?></div><?php endif ?>
    </div>

    <?php if ($more): ?>
    <div class="card"><div class="card-title"><h3>More from <?= e(first_name($s['full_name'])) ?></h3></div>
      <?php foreach ($more as $m): ?><a class="mk-mini" href="<?= e(url('marketplace/' . $m['id'])) ?>"><span class="mk-mini-cover" style="<?= service_cover_css($m) ?>"></span><span class="grow"><b><?= e($m['title']) ?></b><span class="xs muted"><?= e(service_price_label($m)) ?><?= (float) $m['rating'] ? ' · ★ ' . number_format((float) $m['rating'], 1) : '' ?></span></span></a><?php endforeach ?>
    </div>
    <?php endif ?>
  </aside>
</div>
