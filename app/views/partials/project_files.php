<?php
/** Team files and links: images as a gallery, videos as players, documents as cards, links as cards. Needs $files, $pid, $u, $isOwner. */
$url = fn(array $f) => url("projects/$pid/file/{$f['id']}");
$del = fn(array $f) => ((int) $f['user_id'] === (int) $u['id'] || $isOwner) ? '<form class="lm-del" method="post" action="' . e(url("projects/$pid/files/{$f['id']}/delete")) . '" data-confirm="Remove “' . e($f['title']) . '”?">' . csrf_field() . '<button class="icon-btn" type="submit" aria-label="Remove ' . e($f['title']) . '">' . icon('trash', 14) . '</button></form>' : '';
$badge = ['pdf' => 'PDF', 'doc' => 'DOC', 'ppt' => 'PPT', 'xls' => 'XLS', 'zip' => 'ZIP', 'txt' => 'TXT'];
$g = ['image' => [], 'video' => [], 'doc' => [], 'link' => []];
foreach ($files as $f) { $k = material_kind($f); $g[in_array($k, ['image', 'video', 'link'], true) ? $k : 'doc'][] = $f; }
$by = fn(array $f) => '<span class="pj-by">' . e(first_name($f['full_name'])) . ' · ' . e(time_ago($f['created_at'])) . '</span>';
?>
<div class="lm">
  <?php if ($g['image']): ?><div class="lm-gallery<?= count($g['image']) === 1 ? ' is-single' : '' ?>"><?php foreach ($g['image'] as $f): ?>
    <figure class="lm-img"><button type="button" data-lightbox="<?= e($url($f)) ?>" data-caption="<?= e($f['title']) ?>" aria-label="View image: <?= e($f['title']) ?>"><img src="<?= e($url($f)) ?>" alt="<?= e($f['title']) ?>" loading="lazy"><span class="lm-zoom"><?= icon('search', 16) ?></span></button><figcaption><?= e($f['title']) ?><?= $by($f) ?></figcaption><?= $del($f) ?></figure>
  <?php endforeach ?></div><?php endif ?>
  <?php foreach ($g['video'] as $f): ?><div class="lm-video"><iframe src="<?= e(video_embed_url($f['url'])) ?>" allowfullscreen loading="lazy" title="<?= e($f['title']) ?>"></iframe><div class="lm-video-cap"><?= icon('play', 14) ?> <b><?= e($f['title']) ?></b><?= $by($f) ?><?= $del($f) ?></div></div><?php endforeach ?>
  <?php if ($g['doc']): ?><div class="lm-docs"><?php foreach ($g['doc'] as $f): $k = material_kind($f); ?>
    <div class="lm-doc t-<?= $k ?>"><span class="lm-badge t-<?= $k ?>"><?= e($badge[$k] ?? 'FILE') ?></span><span class="lm-doc-body"><b><?= e($f['title']) ?></b><span><?= e(file_size_label((int) $f['size'])) ?> · <?= e(first_name($f['full_name'])) ?></span></span>
      <span class="lm-doc-act"><?php if ($k === 'pdf'): ?><button class="btn btn-sm" type="button" data-pdf="pf<?= (int) $f['id'] ?>" data-src="<?= e($url($f)) ?>">Preview</button><?php else: ?><a class="btn btn-sm" href="<?= e($url($f)) ?>?download=1"><?= icon('download', 14) ?> Download</a><?php endif ?><?= $del($f) ?></span>
      <?php if ($k === 'pdf'): ?><div class="lm-pdf" id="pf<?= (int) $f['id'] ?>" hidden></div><?php endif ?></div>
  <?php endforeach ?></div><?php endif ?>
  <?php if ($g['link']): ?><div class="lm-links"><?php foreach ($g['link'] as $f): $host = link_host($f['url']); ?>
    <div class="lm-link"><a href="<?= e($f['url']) ?>" target="_blank" rel="noopener"><span class="lm-fav"><?= e(mb_strtoupper(mb_substr(preg_replace('/^www\./', '', $host), 0, 1))) ?></span><span class="lm-link-body"><b><?= e($f['title']) ?></b><span><?= e($host) ?> · <?= e(first_name($f['full_name'])) ?></span></span><?= icon('arrow-right', 16) ?></a><?= $del($f) ?></div>
  <?php endforeach ?></div><?php endif ?>
</div>
