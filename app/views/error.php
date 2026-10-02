<?php $wrap = !current_user(); ?>
<?php if ($wrap): ?><div style="max-width:560px;margin:14vh auto;padding:0 16px"><?php endif ?>
<div class="error-page card"><div>
  <div class="big"><?= (int) $code ?></div>
  <h2><?= e($title) ?></h2>
  <p class="muted"><?= e($msg ?: 'Something is not right here.') ?></p>
  <a class="btn btn-primary" href="<?= e(url('')) ?>">Back to MANBAR</a>
</div></div>
<?php if ($wrap): ?></div><?php endif ?>
