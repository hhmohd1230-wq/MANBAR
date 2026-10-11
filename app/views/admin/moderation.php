<?php
$title = 'Moderation';
$tabUrl = fn(string $t) => url('admin/moderation?tab=' . $t);
$stLabel = ['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'];
?>
<section class="ad-hero ad-hero-sm">
  <div>
    <span class="ad-kicker"><?= icon('flag', 15) ?> Moderation</span>
    <h1>Keep MANBAR safe and fair</h1>
    <p>Handle scam cases, review reported content and clean up posts. Every action is logged.</p>
  </div>
  <div class="ad-hero-stats">
    <div class="<?= $counts['scam'] ? 'is-alert' : '' ?>"><b><?= $counts['scam'] ?></b><span>open scam cases</span></div>
    <div><b><?= $counts['content'] ?></b><span>content reports</span></div>
  </div>
</section>

<nav class="ad-tabs" aria-label="Moderation sections">
  <a class="<?= $tab === 'scam' ? 'on' : '' ?>" href="<?= e($tabUrl('scam')) ?>"><?= icon('alert', 16) ?> Scam cases <?php if ($counts['scam']): ?><span class="is-alert"><?= $counts['scam'] ?></span><?php endif ?></a>
  <a class="<?= $tab === 'content' ? 'on' : '' ?>" href="<?= e($tabUrl('content')) ?>"><?= icon('flag', 16) ?> Content reports <?php if ($counts['content']): ?><span><?= $counts['content'] ?></span><?php endif ?></a>
  <a class="<?= $tab === 'posts' ? 'on' : '' ?>" href="<?= e($tabUrl('posts')) ?>"><?= icon('chat', 16) ?> All posts <span><?= $counts['posts'] ?></span></a>
</nav>

<?php if ($tab === 'scam'): ?>
  <?php if (!$cases): ?>
    <div class="card ad-empty"><span><?= icon('shield', 30) ?></span><b>No scam reports</b><p>When someone reports a scam from a listing, an order or a profile, the case shows up here with the order, the money and the seller’s history.</p></div>
  <?php else: ?>
    <div class="ad-cases">
      <?php foreach ($cases as $c): $open = $c['status'] === 'open'; ?>
        <a class="ad-case <?= $open ? 'is-open' : '' ?>" href="<?= e(url('admin/cases/' . $c['id'])) ?>">
          <span class="ad-case-top"><span class="ad-case-no">#<?= (int) $c['id'] ?></span><span class="au-st st-<?= e($c['status']) ?>"><?= e($stLabel[$c['status']] ?? $c['status']) ?></span></span>
          <b class="ad-case-title"><?= e($c['reason']) ?></b>
          <span class="ad-case-text"><?= e(excerpt((string) $c['details'], 120)) ?></span>
          <span class="ad-case-people">
            <span><?= avatar(['full_name' => $c['reporter'], 'email' => $c['reporter_email'], 'avatar_url' => $c['reporter_avatar']], 26) ?> <?= e(first_name($c['reporter'])) ?></span>
            <?= icon('arrow-right', 14) ?>
            <span class="is-accused"><?= avatar(['full_name' => $c['accused'], 'email' => $c['accused_email'], 'avatar_url' => $c['accused_avatar']], 26) ?> <?= e(first_name($c['accused'])) ?><?= $c['accused_status'] !== 'active' ? ' 🔒' : '' ?></span>
          </span>
          <span class="ad-case-foot"><?= $c['amount'] ? '<b>AED ' . (int) $c['amount'] . '</b> · ' : '' ?><?= e(time_ago($c['created_at'])) ?><?= !$open && $c['outcome'] ? ' · ' . e($c['outcome']) : '' ?></span>
        </a>
      <?php endforeach ?>
    </div>
  <?php endif ?>

<?php elseif ($tab === 'content'): ?>
  <div class="card ad-table-card">
    <?php if (!$reports): ?><div class="ad-empty"><span><?= icon('check-circle', 30) ?></span><b>No reports yet 🎉</b><p>Posts, comments, services and projects reported by members show up here.</p></div>
    <?php else: ?><div class="ad-rows">
      <?php foreach ($reports as $r): $pv = $r['preview']; ?>
        <div class="ad-row <?= $r['status'] === 'open' ? 'is-open' : '' ?>">
          <span class="ad-row-ic"><?= icon(['post' => 'chat', 'comment' => 'message', 'service' => 'store', 'project' => 'rocket'][$r['target_type']] ?? 'flag', 17) ?></span>
          <span class="grow ad-row-main">
            <span class="ad-row-top"><span class="ad-reason"><?= e($r['reason']) ?></span><span class="au-st st-<?= e($r['status']) ?>"><?= e($stLabel[$r['status']] ?? $r['status']) ?></span></span>
            <?php if ($pv): ?><b><?= e(excerpt($pv['text'], 90)) ?></b><small><?= e(ucfirst($r['target_type'])) ?> by <?= e($pv['author']) ?> · now <?= e($pv['status']) ?> · reported by <?= e($r['reporter']) ?> <?= e(time_ago($r['created_at'])) ?></small>
            <?php else: ?><b class="muted">This <?= e($r['target_type']) ?> was already deleted</b><small>reported by <?= e($r['reporter']) ?> <?= e(time_ago($r['created_at'])) ?></small><?php endif ?>
          </span>
          <?php if ($r['status'] === 'open'): ?>
            <form method="post" action="<?= e(url('admin/moderate')) ?>" class="ad-row-acts" data-reason-form><?= csrf_field() ?>
              <input type="hidden" name="type" value="<?= e($r['target_type']) ?>"><input type="hidden" name="id" value="<?= (int) $r['target_id'] ?>"><input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="tab" value="content"><input type="hidden" name="reason">
              <?php if ($pv): ?>
                <?php if ($r['target_type'] === 'post'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('post/' . $pv['id'])) ?>" target="_blank" rel="noopener" aria-label="Open"><?= icon('eye', 14) ?></a><?php endif ?>
                <button class="btn btn-sm btn-ghost" name="action" value="hide" data-ask="Hide it. Tell the author why? (optional)">Hide</button>
                <button class="btn btn-sm au-danger" name="action" value="delete" data-ask="Delete it permanently? Tell the author why (optional).">Delete</button>
              <?php endif ?>
              <button class="btn btn-sm btn-ghost" name="action" value="dismiss">Dismiss</button>
            </form>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div><?php endif ?>
  </div>

<?php else: ?>
  <form class="ad-filter" method="get"><input type="hidden" name="tab" value="posts">
    <div class="search grow"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($pq) ?>" placeholder="Search posts by title, text or author"></div>
    <select class="select" name="type" data-autosubmit><option value="">All types</option><?php foreach (POST_TYPES as $k => $pt): ?><option value="<?= e($k) ?>" <?= $ptype === $k ? 'selected' : '' ?>><?= e($pt['label']) ?></option><?php endforeach ?></select>
    <button class="btn btn-primary" type="submit"><?= icon('search', 15) ?> Search</button>
  </form>
  <div class="card ad-table-card">
    <?php if (!$recent): ?><div class="ad-empty"><span><?= icon('search', 30) ?></span><b>No posts match</b><p>Try another word or type.</p></div>
    <?php else: ?><div class="ad-rows">
      <?php foreach ($recent as $p): $pt = post_type($p['type']); ?>
        <div class="ad-row <?= $p['status'] !== 'visible' ? 'is-muted' : '' ?>">
          <a href="<?= e(url('admin/users/' . $p['user_id'])) ?>" title="Manage <?= e($p['full_name']) ?>"><?= avatar(['full_name' => $p['full_name'], 'email' => $p['email'], 'avatar_url' => $p['avatar_url']], 38) ?></a>
          <span class="grow ad-row-main">
            <span class="ad-row-top"><span class="chip sm tchip tone-<?= e($pt['tone']) ?>"><?= e($pt['label']) ?></span><?= $p['pinned'] ? '<span class="chip sm">📌 pinned</span>' : '' ?><?= $p['status'] !== 'visible' ? '<span class="au-st st-hidden">hidden</span>' : '' ?></span>
            <a href="<?= e(url('post/' . $p['id'])) ?>" target="_blank" rel="noopener"><b><?= e(excerpt($p['title'], 90)) ?></b></a>
            <small><a href="<?= e(url('admin/users/' . $p['user_id'])) ?>"><?= e($p['full_name']) ?></a> · <?= e(time_ago($p['created_at'])) ?> · <?= (int) $p['reactions'] ?> reactions · <?= (int) $p['comments'] ?> comments</small>
          </span>
          <form method="post" action="<?= e(url('admin/moderate')) ?>" class="ad-row-acts" data-reason-form><?= csrf_field() ?>
            <input type="hidden" name="type" value="post"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="tab" value="posts"><input type="hidden" name="reason">
            <?php if ($p['status'] === 'visible'): ?><button class="btn btn-sm btn-ghost" name="action" value="hide" data-ask="Hide this post. Tell the author why? (optional)"><?= icon('eye', 14) ?> Hide</button>
            <?php else: ?><button class="btn btn-sm btn-ghost" name="action" value="restore"><?= icon('refresh', 14) ?> Restore</button><?php endif ?>
            <button class="btn btn-sm au-danger" name="action" value="delete" data-ask="Delete this post permanently? Tell the author why (optional)." aria-label="Delete"><?= icon('trash', 14) ?></button>
          </form>
        </div>
      <?php endforeach ?>
    </div><?php endif ?>
  </div>
<?php endif ?>
