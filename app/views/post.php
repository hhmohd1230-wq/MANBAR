<?php $title = $p['title']; $mine = (int) $p['user_id'] === (int) $u['id']; ?>
<div class="page-grid">
  <div class="stack">
    <a class="small row" href="<?= e(url('feed')) ?>" style="color:var(--muted)"><?= icon('arrow-right', 14, '') ?><span style="margin-left:-4px">&nbsp;</span></a>
    <?php partial('post_card', ['p' => $p, 'u' => $u, 'detail' => true]) ?>
    <?php if ($mine || is_admin()): ?>
    <details class="card" id="edit" <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '#edit') ? 'open' : '' ?>>
      <summary style="cursor:pointer;font-weight:700"><?= icon('edit', 16) ?> Edit this post</summary>
      <form method="post" action="<?= e(url('post/' . $p['id'] . '/update')) ?>" class="mt"><?= csrf_field() ?>
        <div class="field"><label class="f">Title</label><input class="input" name="title" value="<?= e($p['title']) ?>" maxlength="200" required></div>
        <div class="field"><label class="f">Details</label><textarea class="textarea" name="body" required><?= e($p['body']) ?></textarea></div>
        <div class="field"><label class="f">Tags (comma separated)</label><input class="input" name="tags" value="<?= e($p['tags']) ?>"></div>
        <button class="btn btn-primary" type="submit">Save changes</button>
      </form>
    </details>
    <?php endif ?>
    <div class="card comments-card" id="comments" style="padding:0">
      <div class="card-title" style="padding:18px 22px 0"><h3><?= icon('chat', 18) ?> Discussion</h3></div>
      <div class="comments" style="border:0;background:none">
        <form class="comment-form" data-comment-form data-post="<?= (int) $p['id'] ?>" style="margin-top:6px">
          <?= avatar($u, 38) ?><div class="grow"><textarea class="textarea" name="body" rows="1" placeholder="Add to the discussion…" required></textarea></div><button class="btn btn-primary" type="submit" aria-label="Send"><?= icon('send', 17) ?></button>
        </form>
        <div id="commentList" data-post="<?= (int) $p['id'] ?>">
          <?php foreach ($comments as $c) partial('comment', ['c' => $c, 'u' => $u, 'p' => $p]) ?>
        </div>
        <?php if (!$comments): ?><p class="muted small tc" id="noComments" style="margin:22px 0 4px">No comments yet — start the conversation!</p><?php endif ?>
      </div>
    </div>
  </div>
  <?php partial('aside', ['w' => $w, 'u' => $u]) ?>
</div>
