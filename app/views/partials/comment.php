<?php
/** @var array $c comment; @var array $u; @var array $p post */
$reply = $reply ?? false;
$mineC = (int) $c['user_id'] === (int) $u['id'];
?>
<div class="comment" id="c<?= (int) $c['id'] ?>" data-comment="<?= (int) $c['id'] ?>">
  <a href="<?= e(url('profile/' . $c['user_id'])) ?>"><?= avatar($c, $reply ? 30 : 36) ?></a>
  <div class="grow">
    <div class="bubble"><b><a href="<?= e(url('profile/' . $c['user_id'])) ?>" style="color:inherit"><?= e($c['full_name']) ?></a></b><?= verified_badge($c) ?>
      <?php if ($c['role'] !== 'student'): ?> <span class="role-pill <?= e($c['role']) ?>"><?= e(role_label($c['role'])) ?></span><?php endif ?>
      <div class="ctext"><?= rich($c['body']) ?></div></div>
    <div class="cact">
      <span><?= e(time_ago($c['created_at'])) ?></span>
      <button class="<?= !empty($c['my_reaction']) ? 'on' : '' ?>" data-react="comment" data-id="<?= (int) $c['id'] ?>" data-kind="like"><?= !empty($c['my_reaction']) ? REACTIONS[$c['my_reaction']]['emoji'] : '👍' ?> <span data-ctotal><?= (int) ($c['reaction_total'] ?: 0) ?: 'Like' ?></span></button>
      <?php if (!$reply): ?><button data-reply="<?= (int) $c['id'] ?>">Reply</button><?php endif ?>
      <?php if ($mineC || is_admin()): ?><button data-del-comment="<?= (int) $c['id'] ?>">Delete</button><?php endif ?>
      <?php if (!$mineC): ?><button data-report="comment" data-id="<?= (int) $c['id'] ?>">Report</button><?php endif ?>
    </div>
    <?php if (!$reply): ?>
      <div class="replies" data-replies="<?= (int) $c['id'] ?>">
        <?php foreach ($c['replies'] ?? [] as $r) partial('comment', ['c' => $r, 'u' => $u, 'p' => $p, 'reply' => true]) ?>
      </div>
    <?php endif ?>
  </div>
</div>
