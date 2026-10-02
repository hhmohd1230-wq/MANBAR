<?php
/** @var array $p post row; @var array $u current user; optional $detail (full page) */
$detail = $detail ?? false;
$pt = post_type($p['type']);
$mine = (int) $p['user_id'] === (int) $u['id'];
$long = !$detail && mb_strlen($p['body']) > 380;
?>
<article class="card post tone-<?= e($pt['tone']) ?>" id="post-<?= (int) $p['id'] ?>" data-post="<?= (int) $p['id'] ?>">
  <div class="post-head">
    <a href="<?= e(url('profile/' . $p['user_id'])) ?>"><?= avatar($p, 46) ?></a>
    <div class="grow">
      <a class="who" href="<?= e(url('profile/' . $p['user_id'])) ?>"><?= e($p['full_name']) ?></a><?= verified_badge($p) ?>
      <?php if ($p['role'] !== 'student'): ?> <span class="role-pill <?= e($p['role']) ?>"><?= e(role_label($p['role'])) ?></span><?php endif ?>
      <div class="meta"><?= e($p['headline'] ?: 'Student') ?> · <a class="muted" href="<?= e(url('post/' . $p['id'])) ?>"><?= e(time_ago($p['created_at'])) ?></a><?php if (!empty($p['updated_at'])): ?> · edited<?php endif ?></div>
    </div>
    <?php if ($p['pinned']): ?><span class="pin-badge"><?= icon('pin-fix', 14) ?> Pinned</span><?php endif ?>
    <span class="type-badge"><?= icon($pt['icon'], 14) ?> <?= e($pt['label']) ?></span>
    <div class="user-menu">
      <button class="icon-btn" data-menu aria-label="More"><?= icon('more', 20) ?></button>
      <div class="dropdown">
        <a href="<?= e(url('post/' . $p['id'])) ?>"><?= icon('eye', 18) ?> Open post</a>
        <button type="button" data-copy="<?= e(url('post/' . $p['id'])) ?>"><?= icon('link', 18) ?> Copy link</button>
        <?php if ($mine && $p['type'] === 'idea'): ?><a href="<?= e(url('projects/new?from_post=' . $p['id'])) ?>"><?= icon('rocket', 18) ?> Turn into a project</a><?php endif ?>
        <?php if ($mine || is_admin()): ?><a href="<?= e(url('post/' . $p['id'] . '#edit')) ?>"><?= icon('edit', 18) ?> Edit</a>
          <form method="post" action="<?= e(url('post/' . $p['id'] . '/delete')) ?>" data-confirm="Delete this post permanently?"><?= csrf_field() ?><button type="submit"><?= icon('trash', 18) ?> Delete</button></form><?php endif ?>
        <?php if (is_admin()): ?><form method="post" action="<?= e(url('post/' . $p['id'] . '/pin')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('pin-fix', 18) ?> <?= $p['pinned'] ? 'Unpin' : 'Pin to top' ?></button></form><?php endif ?>
        <?php if (!$mine): ?><button type="button" data-report="post" data-id="<?= (int) $p['id'] ?>"><?= icon('flag', 18) ?> Report</button><?php endif ?>
      </div>
    </div>
  </div>
  <div class="post-body">
    <?php if ($detail): ?><h2 class="post-title" style="font-size:24px"><?= e($p['title']) ?></h2>
    <?php else: ?><a class="post-title" href="<?= e(url('post/' . $p['id'])) ?>"><?= e($p['title']) ?></a><?php endif ?>
    <div class="post-text"><?= rich($long ? excerpt($p['body'], 380) : $p['body']) ?><?php if ($long): ?> <a class="more" href="<?= e(url('post/' . $p['id'])) ?>">Read more</a><?php endif ?></div>
    <?php if (!empty($p['image'])): ?><a href="<?= e(url($p['image'])) ?>" target="_blank" rel="noopener"><img class="post-img" src="<?= e(url($p['image'])) ?>" alt="" loading="lazy"></a><?php endif ?>
    <?php if (!empty($p['original'])): $o = $p['original']; ?>
      <div class="shared-box"><div class="row"><?= avatar($o, 28) ?><b><?= e($o['full_name']) ?></b><span class="muted xs"><?= e(time_ago($o['created_at'])) ?></span></div>
        <a class="post-title" style="font-size:16px" href="<?= e(url('post/' . $o['id'])) ?>"><?= e($o['title']) ?></a><div class="post-text small"><?= rich(excerpt($o['body'], 200)) ?></div></div>
    <?php endif ?>
    <?php if (!empty($p['tags'])): ?><div class="post-tags"><?php foreach (csv_list($p['tags']) as $t): ?><a class="chip outline sm" href="<?= e(url('feed?tag=' . urlencode($t))) ?>">#<?= e($t) ?></a><?php endforeach ?></div><?php endif ?>
  </div>
  <div class="post-stats">
    <span class="row gap-s" data-react-summary>
      <?php if ($p['reaction_total']): ?><span class="react-stack"><?php foreach (array_slice(array_keys($p['reactions']), 0, 3) as $k): ?><span><?= REACTIONS[$k]['emoji'] ?></span><?php endforeach ?></span><b data-react-total><?= (int) $p['reaction_total'] ?></b><?php endif ?>
    </span>
    <span class="grow"></span>
    <a class="muted" href="<?= e(url('post/' . $p['id'] . '#comments')) ?>"><span data-comment-count><?= (int) $p['comment_count'] ?></span> comments</a>
    <?php if ($p['share_count']): ?><span><?= (int) $p['share_count'] ?> shares</span><?php endif ?>
  </div>
  <div class="post-actions">
    <div class="react-wrap">
      <button class="act <?= $p['my_reaction'] ? 'on' : '' ?>" data-react="post" data-id="<?= (int) $p['id'] ?>" data-kind="<?= e($p['my_reaction'] ?: 'like') ?>">
        <span class="emo"><?= $p['my_reaction'] ? REACTIONS[$p['my_reaction']]['emoji'] : '' ?></span><?= $p['my_reaction'] ? '' : icon('thumb', 19) ?><span class="lbl"><?= $p['my_reaction'] ? e(REACTIONS[$p['my_reaction']]['label']) : 'React' ?></span>
      </button>
      <div class="picker"><?php foreach (REACTIONS as $k => $r): ?><button type="button" data-pick="<?= e($k) ?>" title="<?= e($r['label']) ?>"><?= $r['emoji'] ?></button><?php endforeach ?></div>
    </div>
    <a class="act" href="<?= e(url('post/' . $p['id'] . '#comments')) ?>" data-focus-comment="<?= (int) $p['id'] ?>"><?= icon('chat', 19) ?><span class="lbl">Comment</span></a>
    <button class="act" data-share="<?= (int) $p['id'] ?>"><?= icon('share', 19) ?><span class="lbl">Share</span></button>
    <button class="act saved <?= $p['saved'] ? 'on' : '' ?>" data-save="<?= (int) $p['id'] ?>"><?= icon('bookmark', 19) ?><span class="lbl">Save</span></button>
  </div>
</article>
