<?php $title = 'Messages'; ?>
<div class="pagehead"><div><h1>Messages</h1><p>Private conversations with classmates, mentors and teachers.</p></div></div>
<div class="dm-grid">
  <div class="card" style="padding:12px;max-height:620px;overflow:auto">
    <?php foreach ($threads as $oid => $t): $pp = $people[$oid]; ?>
      <a class="thread <?= $other && (int) $other['id'] === (int) $oid ? 'on' : '' ?>" href="<?= e(url('messages/' . $oid)) ?>"><?= avatar($pp, 42) ?><span class="grow"><b><?= e($pp['full_name']) ?></b><br><span class="xs muted"><?= $t['last'] ? e(excerpt($t['last']['body'], 34)) : 'New conversation' ?></span></span><?php if ($t['unread']): ?><span class="unread"><?= $t['unread'] ?></span><?php endif ?></a>
    <?php endforeach ?>
    <?php if (!$threads): ?><div class="empty" style="padding:24px 8px"><h3>No conversations</h3><p class="small">Open a profile and press “Message”.</p><a class="btn btn-sm" href="<?= e(url('people')) ?>">Find people</a></div><?php endif ?>
  </div>
  <div class="card" style="display:flex;flex-direction:column;padding:0;min-height:520px">
    <?php if ($other): ?>
      <div class="row" style="padding:14px 18px;border-bottom:1px solid var(--line)"><?= avatar($other, 42) ?><div class="grow"><a href="<?= e(url('profile/' . $other['id'])) ?>"><b><?= e($other['full_name']) ?></b></a><?= verified_badge($other) ?><div class="xs muted"><?= e(role_label($other['role'])) ?></div></div></div>
      <div class="chat dm grow" id="dmChat" style="padding:18px;max-height:none;min-height:340px;overflow-y:auto">
        <?php foreach ($conv as $m): $mine = (int) $m['sender_id'] === (int) $u['id']; ?><div class="msg <?= $mine ? 'mine' : '' ?>"><div class="bub"><?= rich($m['body']) ?><div class="xs muted" style="margin-top:3px"><?= e(date('M j, H:i', strtotime($m['created_at']))) ?></div></div></div><?php endforeach ?>
        <?php if (!$conv): ?><p class="muted tc small" style="margin:auto">Say hello to <?= e(explode(' ', $other['full_name'])[0]) ?> 👋</p><?php endif ?>
      </div>
      <form method="post" action="<?= e(url('messages/' . $other['id'])) ?>" class="row" style="padding:14px;border-top:1px solid var(--line)"><?= csrf_field() ?><input class="input" name="body" placeholder="Write a message…" required autocomplete="off" autofocus><button class="btn btn-primary" type="submit"><?= icon('send', 17) ?></button></form>
    <?php else: ?><div class="empty" style="margin:auto"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>Select a conversation</h3><p>Or start a new one from someone’s profile.</p></div><?php endif ?>
  </div>
</div>
