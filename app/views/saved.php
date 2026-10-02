<?php $title = 'Saved posts'; ?>
<div class="pagehead"><div><h1>Saved posts</h1><p>Posts you bookmarked to read or use later.</p></div></div>
<div class="feed" style="max-width:780px">
  <?php foreach ($posts as $p) partial('post_card', ['p' => $p, 'u' => $u]) ?>
  <?php if (!$posts): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No saved posts</h3><p>Tap the bookmark on any post to keep it here.</p><a class="btn btn-primary" href="<?= e(url('feed')) ?>">Browse the feed</a></div><?php endif ?>
</div>
