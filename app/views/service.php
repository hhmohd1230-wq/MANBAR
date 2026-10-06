<?php $title = $s['title']; $price = $s['price'] ? 'AED ' . (int) $s['price'] : 'Free'; ?>
<div class="page-grid">
  <div class="stack gap-l">
    <div class="card" style="padding:0;overflow:hidden">
      <div class="item-cover" style="height:140px;<?= theme_css(crc32($s['category']) % 6) ?>"><span class="chip sm" style="background:rgba(255,255,255,.92)"><?= e(SERVICE_CATEGORIES[$s['category']] ?? 'Other') ?></span><span class="ic-big"><?= icon(SERVICE_ICONS[$s['category']] ?? 'tag', 36) ?></span></div>
      <div style="padding:24px 28px 28px">
        <div class="row between wrap"><h1 style="margin:0"><?= e($s['title']) ?></h1><span class="status-pill st-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></div>
        <div class="row" style="margin:10px 0"><?= $reviews ? stars($avg) . ' <b>' . number_format($avg, 1) . '</b> <span class="muted small">(' . count($reviews) . ' reviews)</span>' : '<span class="muted small">No reviews yet</span>' ?></div>
        <div class="prose" style="font-size:15.5px"><?= rich($s['description']) ?></div>
      </div>
    </div>

    <?php if ($mine && $incoming): ?>
    <div class="card"><div class="card-title"><h3><?= icon('briefcase', 18) ?> Requests for this service</h3></div>
      <div class="stack"><?php foreach ($incoming as $r): ?>
        <div class="row" style="align-items:flex-start;gap:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
          <a href="<?= e(url('profile/' . $r['buyer_id'])) ?>"><?= avatar($r, 44) ?></a>
          <div class="grow"><b><?= e($r['full_name']) ?></b> <span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span> <span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span><p class="small" style="margin:4px 0 0"><?= e($r['message'] ?: 'No message.') ?></p></div>
          <form method="post" action="<?= e(url('marketplace/' . $s['id'] . '/request/' . $r['id'])) ?>" class="row"><?= csrf_field() ?>
            <?php if ($r['status'] === 'pending'): ?><button class="btn btn-primary btn-sm" name="decision" value="accept">Accept</button><button class="btn btn-ghost btn-sm" name="decision" value="decline">Decline</button>
            <?php elseif ($r['status'] === 'accepted'): ?><button class="btn btn-primary btn-sm" name="decision" value="complete"><?= icon('check', 14) ?> Mark delivered</button><a class="btn btn-ghost btn-sm" href="<?= e(url('messages/' . $r['buyer_id'])) ?>">Message</a><?php endif ?></form>
        </div><?php endforeach ?></div></div>
    <?php endif ?>

    <div class="card" id="reviews"><div class="card-title"><h3><?= icon('star', 18) ?> Reviews</h3></div>
      <?php if ($canReview): ?>
        <form method="post" action="<?= e(url('marketplace/' . $s['id'] . '/review')) ?>" class="card flat mb" style="background:var(--g50)" data-ai-writing-form data-ai-context="marketplace review"><?= csrf_field() ?>
          <b>How was your order?</b>
          <div class="row" style="margin:8px 0"><select class="select" name="rating" style="width:auto"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= str_repeat('★', $i) ?> <?= $i ?></option><?php endfor ?></select></div>
          <textarea class="textarea" name="comment" data-ai-writing="body" style="min-height:70px" placeholder="Share a few words (optional)"></textarea><div class="writing-assist" data-writing-assist hidden aria-live="polite"></div><button class="btn btn-primary mt" type="submit">Submit review</button>
        </form><?php endif ?>
      <div class="stack"><?php foreach ($reviews as $r): ?>
        <div class="row" style="align-items:flex-start;gap:12px"><?= avatar($r, 40) ?><div class="grow"><b><?= e($r['full_name']) ?></b> <?= stars((float) $r['rating']) ?> <span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span><p style="margin:3px 0 0"><?= e($r['comment']) ?></p></div></div><?php endforeach ?>
        <?php if (!$reviews): ?><p class="muted small">Reviews appear after a completed order.</p><?php endif ?></div>
    </div>
  </div>
  <aside class="aside">
    <div class="card"><div class="price" style="font-size:30px"><?= $price ?></div><div class="small muted"><?= icon('clock', 14) ?> <?= (int) $s['delivery_days'] ?>-day delivery</div><hr class="divider">
      <?php if ($mine): ?>
        <div class="stack gap-s"><span class="chip">This is your service</span>
        <form method="post" action="<?= e(url('marketplace/' . $s['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-block"><?= $s['status'] === 'active' ? 'Pause listing' : 'Re-activate' ?></button></form>
        <form method="post" action="<?= e(url('marketplace/' . $s['id'] . '/toggle')) ?>" data-confirm="Delete this service?"><?= csrf_field() ?><input type="hidden" name="delete" value="1"><button class="btn btn-danger btn-block btn-sm"><?= icon('trash', 14) ?> Delete</button></form></div>
      <?php elseif ($myReq && in_array($myReq['status'], ['pending', 'accepted'], true)): ?>
        <div class="chip st-<?= e($myReq['status']) ?>">Your request is <?= e($myReq['status']) ?></div><a class="btn btn-block mt" href="<?= e(url('messages/' . $s['user_id'])) ?>"><?= icon('message', 16) ?> Message provider</a>
      <?php elseif ($s['status'] === 'active'): ?>
        <form method="post" action="<?= e(url('marketplace/' . $s['id'] . '/request')) ?>" data-ai-writing-form data-ai-context="marketplace request"><?= csrf_field() ?><label class="f">Message to the provider</label><textarea class="textarea" name="message" data-ai-writing="body" style="min-height:90px" placeholder="Describe what you need and when…"></textarea><div class="writing-assist" data-writing-assist hidden aria-live="polite"></div><button class="btn btn-primary btn-block mt" type="submit"><?= icon('send', 16) ?> Request service</button></form>
      <?php endif ?>
    </div>
    <div class="card"><div class="card-title"><h3>Provider</h3></div>
      <div class="person"><a href="<?= e(url('profile/' . $s['user_id'])) ?>"><?= avatar($s, 52) ?></a><div class="grow"><a class="nm" href="<?= e(url('profile/' . $s['user_id'])) ?>"><?= e($s['full_name']) ?></a><?= verified_badge($s) ?><div class="xs muted"><?= e($s['headline'] ?: $s['major'] ?: role_label($s['role'])) ?></div></div></div>
      <?php if (!$mine): ?><div class="row mt"><a class="btn btn-sm btn-block" href="<?= e(url('profile/' . $s['user_id'])) ?>">View profile</a><button class="btn btn-sm btn-ghost" data-report="service" data-id="<?= (int) $s['id'] ?>"><?= icon('flag', 14) ?></button></div><?php endif ?></div>
  </aside>
</div>
