<?php
$title = $t['full_name'];
$self = (int) $t['id'] === (int) $u['id'];
$banned = $t['status'] !== 'active';
$rk = rank_key((int) $rank['n']);
$parts = ['activity' => ['Activity points', 'sparkles'], 'appreciation' => ['Reactions received', 'heart'], 'reviews' => ['Marketplace reviews', 'star'], 'vouches' => ['Project vouches', 'users'], 'recommendations' => ['Recommendations', 'award'], 'completed_work' => ['Finished work', 'check-circle']];
$maxPart = max(1, ...array_map(fn($k) => (int) $bd[$k], array_keys($parts)));
$actLabel = ['ban' => ['Banned', 'lock', 'red'], 'unban' => ['Ban lifted', 'check-circle', 'green'], 'warn' => ['Warning sent', 'alert', 'amber'], 'xp' => ['Rank XP changed', 'trending', 'violet'],
    'badge_give' => ['Badge given', 'award', 'amber'], 'badge_remove' => ['Badge removed', 'award', 'rose'], 'post_delete' => ['Post deleted', 'trash', 'red'], 'post_hide' => ['Post hidden', 'eye', 'orange'],
    'post_restore' => ['Post restored', 'refresh', 'green'], 'hide_listings' => ['Listings hidden', 'store', 'orange'], 'refund' => ['Buyer refunded', 'wallet', 'blue'], 'comment_delete' => ['Comment deleted', 'trash', 'red'], 'comment_hide' => ['Comment hidden', 'eye', 'orange'], 'service_hide' => ['Listing hidden', 'store', 'orange'], 'service_delete' => ['Listing deleted', 'trash', 'red']];
?>
<a class="ad-back" href="<?= e(url('admin/users')) ?>"><?= icon('arrow-right', 15, 'flip') ?> All users</a>

<section class="au-hero rank-theme-<?= e($rk) ?> <?= $banned ? 'is-banned' : '' ?>">
  <div class="au-id">
    <span class="au-av"><?= avatar($t, 84) ?><span class="rank-emblem rank-<?= e($rk) ?> au-emblem" aria-hidden="true"></span></span>
    <div>
      <div class="au-name"><h1><?= e($t['full_name']) ?></h1><?= verified_badge($t) ?></div>
      <p><?= e($t['email']) ?> · <?= e($t['student_id'] ?: 'no student ID') ?> · joined <?= e(date('j M Y', strtotime($t['created_at']))) ?></p>
      <div class="au-pills">
        <span class="au-pill role-<?= e($t['role']) ?>"><?= icon('shield', 13) ?> <?= e(ucfirst($t['role'])) ?></span>
        <span class="au-pill <?= $banned ? 'is-red' : 'is-green' ?>"><?= icon($banned ? 'lock' : 'check-circle', 13) ?> <?= e(ban_label($t)) ?></span>
        <span class="au-pill"><?= icon('trophy', 13) ?> <?= e($rank['name']) ?> <?= str_repeat('★', $rank['star']) ?></span>
        <?php if ($t['last_login']): ?><span class="au-pill"><?= icon('clock', 13) ?> seen <?= e(time_ago($t['last_login'])) ?></span><?php endif ?>
      </div>
    </div>
  </div>
  <div class="au-hero-btns">
    <a class="btn au-btn-light" href="<?= e(url('profile/' . $t['id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?> View profile</a>
    <?php if (!$self): ?><a class="btn au-btn-ghost" href="<?= e(url('messages/' . $t['id'])) ?>"><?= icon('message', 16) ?> Message</a><?php endif ?>
  </div>
  <?php if ($banned): ?><div class="au-banned"><?= icon('lock', 16) ?> <span><b><?= e(ban_label($t)) ?>.</b> <?= e($t['ban_reason'] ?: 'No reason recorded.') ?></span></div><?php endif ?>
</section>

<div class="au-kpis">
  <?php foreach ([['trending', 'violet', number_format($score), 'Rank XP'], ['sparkles', 'green', number_format((int) $t['points']), 'Activity points'], ['award', 'amber', $counts['badges'], 'Badges'],
    ['chat', 'blue', $counts['posts'], 'Posts'], ['store', 'teal', $counts['orders'], 'Orders delivered'], ['flag', $counts['reports'] ? 'red' : 'green', $counts['reports'], 'Reports']] as [$ic, $tone, $v, $l]): ?>
    <div class="au-kpi tone-<?= $tone ?>"><span><?= icon($ic, 18) ?></span><b><?= $v ?></b><small><?= e($l) ?></small></div>
  <?php endforeach ?>
</div>

<div class="au-grid">
  <div class="au-col">
    <!-- rank & XP -->
    <section class="card au-card" id="xp">
      <div class="au-head"><h2><?= icon('trending', 18) ?> Rank &amp; XP</h2><span class="au-sub">Rank XP is activity points plus reactions, reviews, vouches, recommendations and finished work.</span></div>
      <div class="au-rank">
        <span class="rank-emblem rank-<?= e($rk) ?> au-rank-emblem" aria-hidden="true"></span>
        <div class="grow">
          <b><?= e($rank['name']) ?> <?= rank_stars_html($rank, 'rank-stars au-stars') ?></b>
          <span class="au-bar"><i style="width:<?= (int) $rank['pct'] ?>%"></i></span>
          <small><?= number_format($score) ?> XP<?= $rank['next'] ? ' · ' . max(0, (int) $rank['next_at'] - $score) . ' XP to ' . e($rank['next']) : ' · top rank' ?></small>
        </div>
      </div>
      <div class="au-parts">
        <?php foreach ($parts as $k => [$l, $ic]): ?>
          <div><span><?= icon($ic, 14) ?> <?= e($l) ?></span><span class="au-pbar"><i style="width:<?= round(max(0, (int) $bd[$k]) / $maxPart * 100) ?>%"></i></span><b><?= number_format((int) $bd[$k]) ?></b></div>
        <?php endforeach ?>
      </div>
      <form class="au-xp" method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>" data-xp-form data-score="<?= $score ?>" data-levels='<?= e(json_encode(array_map(fn($l) => [$l['xp'], $l['label']], $levels))) ?>'><?= csrf_field() ?>
        <input type="hidden" name="tool" value="xp">
        <div class="au-seg" role="radiogroup" aria-label="Change type">
          <label><input type="radio" name="mode" value="add" checked><span><?= icon('plus', 14) ?> Add XP</span></label>
          <label><input type="radio" name="mode" value="remove"><span>− Remove XP</span></label>
          <label><input type="radio" name="mode" value="set"><span><?= icon('target', 14) ?> Set rank</span></label>
        </div>
        <div class="au-xp-row" data-xp-amount>
          <input class="input" type="number" name="amount" min="0" max="100000" value="150" aria-label="Amount of XP">
          <div class="au-chips"><?php foreach ([50, 150, 450, 1000] as $c): ?><button type="button" data-xp-chip="<?= $c ?>"><?= $c ?></button><?php endforeach ?></div>
        </div>
        <select class="select" data-xp-level hidden aria-label="Rank to set">
          <?php foreach ($levels as $l): ?><option value="<?= $l['xp'] ?>" <?= $l['xp'] === (int) $rank['current_at'] ? 'selected' : '' ?>><?= e($l['label']) ?> · <?= number_format($l['xp']) ?> XP</option><?php endforeach ?>
        </select>
        <input class="input" name="note" maxlength="200" placeholder="Reason, e.g. Won the AAU hackathon" aria-label="Reason">
        <div class="au-preview" data-xp-preview></div>
        <div class="au-actions"><label class="au-check"><input type="checkbox" name="notify" value="1" checked> Tell them in a notification</label><button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> Apply</button></div>
      </form>
    </section>

    <!-- badges -->
    <section class="card au-card" id="badges">
      <div class="au-head"><h2><?= icon('award', 18) ?> Badges</h2><span class="au-sub">Click a badge to give it or take it away. Automatic badges come back if the person still meets the goal.</span></div>
      <div class="au-badges">
        <?php foreach ($badges as $b): $has = (bool) $b['earned_at']; ?>
          <form method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>" <?= $has ? 'data-confirm="Take the “' . e($b['name']) . '” badge away?"' : '' ?>><?= csrf_field() ?>
            <input type="hidden" name="tool" value="badge"><input type="hidden" name="code" value="<?= e($b['code']) ?>">
            <button class="au-badge tone-<?= e($b['tone']) ?> <?= $has ? 'is-on' : '' ?>" type="submit" title="<?= e($b['description']) ?>">
              <span class="au-badge-ic"><?= icon($b['icon'], 18) ?></span><b><?= e($b['name']) ?></b>
              <small><?= $has ? 'Earned ' . e(date('j M', strtotime($b['earned_at']))) : 'Give badge' ?></small>
            </button>
          </form>
        <?php endforeach ?>
      </div>
    </section>

    <!-- posts -->
    <section class="card au-card" id="posts">
      <div class="au-head"><h2><?= icon('chat', 18) ?> Posts</h2><span class="au-sub"><?= $counts['posts'] ?> posts · <?= $counts['comments'] ?> comments</span></div>
      <?php if ($posts): ?>
        <div class="au-posts">
          <?php foreach ($posts as $p): $pt = post_type($p['type']); ?>
            <div class="au-post <?= $p['status'] !== 'visible' ? 'is-hidden' : '' ?>">
              <span class="au-post-ic tone-<?= e($pt['tone']) ?>"><?= icon($pt['icon'], 16) ?></span>
              <span class="grow"><a href="<?= e(url('post/' . $p['id'])) ?>" target="_blank" rel="noopener"><b><?= e(excerpt($p['title'], 70)) ?></b></a>
                <small><?= e($pt['label']) ?> · <?= e(time_ago($p['created_at'])) ?> · <?= (int) $p['comments'] ?> comments<?= $p['status'] !== 'visible' ? ' · <b class="au-hidden-tag">hidden</b>' : '' ?></small></span>
              <form method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>" class="au-post-acts" data-reason-form><?= csrf_field() ?>
                <input type="hidden" name="tool" value="post"><input type="hidden" name="post" value="<?= (int) $p['id'] ?>"><input type="hidden" name="note">
                <?php if ($p['status'] === 'visible'): ?><button class="btn btn-sm btn-ghost" name="do" value="hide" data-ask="Why are you hiding this post? (sent to the author, optional)"><?= icon('eye', 14) ?> Hide</button>
                <?php else: ?><button class="btn btn-sm btn-ghost" name="do" value="restore"><?= icon('refresh', 14) ?> Restore</button><?php endif ?>
                <button class="btn btn-sm au-danger" name="do" value="delete" data-ask="Delete this post permanently? Add a reason for the author (optional)." aria-label="Delete post"><?= icon('trash', 14) ?></button>
              </form>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?><p class="muted small">No posts yet.</p><?php endif ?>
    </section>
  </div>

  <div class="au-col">
    <!-- ban -->
    <section class="card au-card au-ban <?= $banned ? 'is-banned' : '' ?>" id="ban">
      <div class="au-head"><h2><?= icon('lock', 18) ?> <?= $banned ? 'Account is banned' : 'Ban or block' ?></h2></div>
      <?php if ($self): ?><p class="muted small">This is your own account.</p>
      <?php elseif ($banned): ?>
        <p class="au-ban-now"><b><?= e(ban_label($t)) ?></b><br><?= e($t['ban_reason'] ?: 'No reason recorded.') ?></p>
        <form method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>"><?= csrf_field() ?><input type="hidden" name="tool" value="unban">
          <input class="input" name="note" maxlength="200" placeholder="Note for the log (optional)">
          <button class="btn btn-primary btn-block au-mt" type="submit"><?= icon('check-circle', 16) ?> Lift the ban</button></form>
      <?php else: ?>
        <form method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>" data-confirm="Ban <?= e($t['full_name']) ?>? They will be signed out and can’t sign in until the ban ends."><?= csrf_field() ?><input type="hidden" name="tool" value="ban">
          <label class="f" for="banReason">Reason</label>
          <select class="select" id="banReason" name="reason"><?php foreach (BAN_REASONS as $r): ?><option><?= e($r) ?></option><?php endforeach ?></select>
          <label class="f au-mt" for="banLen">How long</label>
          <div class="au-len"><?php foreach (BAN_LENGTHS as $d => $l): ?><label><input type="radio" name="days" value="<?= e((string) $d) ?>" <?= $d === '7' ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div>
          <textarea class="input au-mt" name="note" rows="2" maxlength="300" placeholder="Details (shown to them when they try to sign in)"></textarea>
          <button class="btn btn-block au-ban-btn au-mt" type="submit"><?= icon('lock', 16) ?> Ban this account</button>
        </form>
      <?php endif ?>
    </section>

    <!-- warn -->
    <?php if (!$self): ?>
    <section class="card au-card" id="warn">
      <div class="au-head"><h2><?= icon('alert', 18) ?> Send a warning</h2></div>
      <form method="post" action="<?= e(url('admin/users/' . $t['id'] . '/tools')) ?>"><?= csrf_field() ?><input type="hidden" name="tool" value="warn">
        <div class="au-chips au-warn-chips"><?php foreach (['Please keep comments respectful.', 'Don’t ask buyers to pay outside MANBAR.', 'Please stop posting advertising.', 'Deliver the order you were paid for.'] as $w): ?><button type="button" data-fill-warn="<?= e($w) ?>"><?= e($w) ?></button><?php endforeach ?></div>
        <textarea class="input" name="note" rows="3" maxlength="400" placeholder="Write the warning. It arrives as a notification." required></textarea>
        <button class="btn btn-block au-warn-btn au-mt" type="submit"><?= icon('send', 16) ?> Send warning</button>
      </form>
    </section>
    <?php endif ?>

    <!-- role -->
    <section class="card au-card">
      <div class="au-head"><h2><?= icon('shield', 18) ?> Role &amp; verification</h2></div>
      <form class="au-role" method="post" action="<?= e(url('admin/users/' . $t['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="role">
        <select class="select" name="role" <?= $self ? 'disabled' : '' ?>><?php foreach (['student', 'teacher', 'admin'] as $x): ?><option value="<?= $x ?>" <?= $t['role'] === $x ? 'selected' : '' ?>><?= ucfirst($x) ?></option><?php endforeach ?></select>
        <button class="btn btn-ghost" type="submit" <?= $self ? 'disabled' : '' ?>>Save role</button>
      </form>
      <form method="post" action="<?= e(url('admin/users/' . $t['id'])) ?>" class="au-mt"><?= csrf_field() ?><button class="btn btn-ghost btn-block" name="action" value="verify"><?= icon('check-circle', 15) ?> <?= $t['verified'] ? 'Remove verified tick' : 'Give verified tick' ?></button></form>
    </section>

    <!-- reports -->
    <section class="card au-card">
      <div class="au-head"><h2><?= icon('flag', 18) ?> Reports about them</h2><span class="chip sm"><?= count($reports) ?></span></div>
      <?php if ($reports): ?><div class="au-list"><?php foreach ($reports as $r): ?>
        <a class="au-li" href="<?= e(url($r['category'] === 'scam' ? 'admin/cases/' . $r['id'] : 'admin/moderation?tab=content')) ?>">
          <span class="au-li-ic <?= $r['category'] === 'scam' ? 'is-scam' : '' ?>"><?= icon($r['category'] === 'scam' ? 'alert' : 'flag', 14) ?></span>
          <span class="grow"><b><?= e($r['reason']) ?></b><small><?= e($r['target_type']) ?> · by <?= e($r['reporter']) ?> · <?= e(time_ago($r['created_at'])) ?></small></span>
          <span class="au-st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></a>
      <?php endforeach ?></div><?php else: ?><p class="muted small">No reports. 🎉</p><?php endif ?>
    </section>

    <!-- history -->
    <section class="card au-card">
      <div class="au-head"><h2><?= icon('clock', 18) ?> Moderation history</h2></div>
      <?php if ($history): ?><ol class="au-tl"><?php foreach ($history as $h): [$hl, $hic, $htone] = $actLabel[$h['action']] ?? [ucfirst(str_replace('_', ' ', $h['action'])), 'info', 'green']; ?>
        <li class="tone-<?= e($htone) ?>"><span class="au-tl-ic"><?= icon($hic, 13) ?></span><div><b><?= e($hl) ?></b><?php if ($h['detail']): ?><p><?= e($h['detail']) ?></p><?php endif ?><small><?= e($h['admin'] ?: 'System') ?> · <?= e(time_ago($h['created_at'])) ?></small></div></li>
      <?php endforeach ?></ol><?php else: ?><p class="muted small">No moderation actions yet.</p><?php endif ?>
    </section>

    <?php if (!$self): ?>
    <section class="card au-card au-dz">
      <div class="au-head"><h2><?= icon('trash', 18) ?> Danger zone</h2></div>
      <p class="small muted">Deleting removes the account and everything they posted. This can’t be undone. Banning is usually the better choice.</p>
      <form method="post" action="<?= e(url('admin/users/' . $t['id'])) ?>" data-confirm="Delete <?= e($t['full_name']) ?> and all their content permanently?"><?= csrf_field() ?><button class="btn btn-block au-danger" name="action" value="delete"><?= icon('trash', 15) ?> Delete account</button></form>
    </section>
    <?php endif ?>
  </div>
</div>
