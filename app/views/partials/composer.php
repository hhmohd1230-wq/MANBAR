<?php
/** @var array $u; $compose = preselected type or '' */
$types = array_filter(POST_TYPES, fn($t) => !$t['staff'] || is_teacher());
$sel = isset($types[$compose ?? '']) ? $compose : 'idea';
$open = !empty($compose);
?>
<div class="card composer" id="composer">
  <div class="composer-collapsed" id="composerOpen" <?= $open ? 'hidden' : '' ?>>
    <?= avatar($u, 44) ?><div class="fake">Share an idea, ask a question or find teammates, <?= e(explode(' ', $u['full_name'])[0]) ?>…</div>
    <button class="btn btn-primary btn-sm" type="button"><?= icon('plus', 16) ?> Post</button>
  </div>
  <form id="composerForm" method="post" action="<?= e(url('post/create')) ?>" enctype="multipart/form-data" <?= $open ? '' : 'hidden' ?>>
    <?= csrf_field() ?>
    <div class="type-pick">
      <?php foreach ($types as $k => $t): ?><label class="tone-<?= e($t['tone']) ?>"><input type="radio" name="type" value="<?= e($k) ?>" data-ph="<?= e($t['ph']) ?>" <?= $k === $sel ? 'checked' : '' ?>><span><?= icon($t['icon'], 16) ?> <?= e($t['label']) ?></span></label><?php endforeach ?>
    </div>
    <div class="composer-head">
      <?= avatar($u, 44) ?>
      <div class="grow">
        <input class="title-in" name="title" id="cTitle" maxlength="200" placeholder="Give it a clear title" value="<?= e(old('title')) ?>" required>
        <textarea class="body-in" name="body" id="cBody" maxlength="5000" placeholder="<?= e($types[$sel]['ph']) ?>" required><?= e(old('body')) ?></textarea>
        <div id="imgPrev"></div>
      </div>
    </div>
    <div class="ai-panel" id="aiPanel" hidden></div>
    <div class="composer-foot">
      <label class="btn btn-ghost btn-sm" style="cursor:pointer"><?= icon('image', 16) ?> Photo<input type="file" name="image" id="cImage" accept="image/*" hidden></label>
      <div class="tags-in" style="flex:1;min-width:180px;padding:3px 8px" id="tagsBox"><input id="tagsIn" placeholder="Add #tags (press Enter)" aria-label="Tags"></div>
      <input type="hidden" name="tags" id="tagsVal" value="<?= e(old('tags')) ?>">
      <button type="button" class="btn btn-sm" id="aiFix" title="Fix spelling & grammar and find the best place to post"><?= icon('sparkles', 16) ?> AI assist</button>
      <button type="button" class="btn btn-ghost btn-sm" id="composerCancel">Cancel</button>
      <button class="btn btn-primary btn-sm" type="submit"><?= icon('send', 16) ?> Publish</button>
    </div>
  </form>
</div>
<?php clear_old(); ?>
