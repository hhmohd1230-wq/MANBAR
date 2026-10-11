<?php
/**
 * Files and links of a lesson, summary or the whole course, shown by type:
 * images as a gallery (opens a viewer), videos as players, documents as file cards (PDFs preview inline), links as cards.
 * Needs $mats, $cid, $access, $manage; optional $inLesson (delete returns to the lesson).
 */
$inLesson = $inLesson ?? false;
$groups = ['image' => [], 'video' => [], 'doc' => [], 'link' => []];
foreach ($mats as $m) { $k = material_kind($m); $groups[in_array($k, ['image', 'video', 'link'], true) ? $k : 'doc'][] = $m; }
$fileUrl = fn(array $m) => url("learn/$cid/file/{$m['id']}");
$del = fn(array $m) => $manage ? '<form class="lm-del" method="post" action="' . e(url("learn/$cid/materials/{$m['id']}/delete")) . '" data-confirm="Remove “' . e($m['title']) . '”?">' . csrf_field() . ($inLesson ? '<input type="hidden" name="back" value="lesson">' : '') . '<button class="icon-btn" type="submit" aria-label="Remove ' . e($m['title']) . '">' . icon('trash', 14) . '</button></form>' : '';
$badge = ['pdf' => 'PDF', 'doc' => 'DOC', 'ppt' => 'PPT', 'xls' => 'XLS', 'zip' => 'ZIP', 'txt' => 'TXT'];
?>
<?php if (!$access): ?>
  <ul class="lm-locked">
    <?php foreach ($mats as $m): $k = material_kind($m); ?>
      <li><span class="lm-badge t-<?= $k ?>"><?= $k === 'image' ? icon('image', 15) : ($k === 'video' ? icon('play', 15) : ($k === 'link' ? icon('link', 15) : e($badge[$k] ?? 'FILE'))) ?></span><b><?= e($m['title']) ?></b><span class="muted small"><?= icon('lock', 13) ?> after you join</span></li>
    <?php endforeach ?>
  </ul>
<?php else: ?>
<div class="lm">
  <?php if ($groups['image']): ?>
    <div class="lm-gallery<?= count($groups['image']) === 1 ? ' is-single' : '' ?>" data-gallery>
      <?php foreach ($groups['image'] as $m): ?>
        <figure class="lm-img">
          <button type="button" data-lightbox="<?= e($fileUrl($m)) ?>" data-caption="<?= e($m['title']) ?>" aria-label="View image: <?= e($m['title']) ?>"><img src="<?= e($fileUrl($m)) ?>" alt="<?= e($m['title']) ?>" loading="lazy"><span class="lm-zoom"><?= icon('search', 16) ?></span></button>
          <figcaption><?= e($m['title']) ?></figcaption><?= $del($m) ?>
        </figure>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <?php foreach ($groups['video'] as $m): ?>
    <div class="lm-video">
      <iframe src="<?= e(video_embed_url($m['url'])) ?>" allowfullscreen loading="lazy" title="<?= e($m['title']) ?>" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe>
      <div class="lm-video-cap"><?= icon('play', 14) ?> <b><?= e($m['title']) ?></b><span class="muted small"><?= e(link_host($m['url'])) ?></span><?= $del($m) ?></div>
    </div>
  <?php endforeach ?>

  <?php if ($groups['doc']): ?>
    <div class="lm-docs">
      <?php foreach ($groups['doc'] as $m): $k = material_kind($m); $ext = strtoupper(pathinfo((string) $m['file_name'], PATHINFO_EXTENSION)); ?>
        <div class="lm-doc t-<?= $k ?>">
          <span class="lm-badge t-<?= $k ?>"><?= e($badge[$k] ?? $ext) ?></span>
          <span class="lm-doc-body"><b><?= e($m['title']) ?></b><span><?= e($ext) ?> · <?= e(file_size_label((int) $m['size'])) ?></span></span>
          <span class="lm-doc-act">
            <?php if ($k === 'pdf'): ?><button class="btn btn-sm" type="button" data-pdf="pdf<?= (int) $m['id'] ?>" data-src="<?= e($fileUrl($m)) ?>">Preview</button><a class="btn btn-sm btn-ghost" href="<?= e($fileUrl($m)) ?>" target="_blank" rel="noopener" aria-label="Open <?= e($m['title']) ?> in a new tab"><?= icon('arrow-right', 14) ?></a>
            <?php else: ?><a class="btn btn-sm" href="<?= e($fileUrl($m)) ?>?download=1"><?= icon('download', 14) ?> Download</a><?php endif ?>
            <?= $del($m) ?>
          </span>
          <?php if ($k === 'pdf'): ?><div class="lm-pdf" id="pdf<?= (int) $m['id'] ?>" hidden></div><?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <?php if ($groups['link']): ?>
    <div class="lm-links">
      <?php foreach ($groups['link'] as $m): $host = link_host($m['url']); ?>
        <div class="lm-link">
          <a href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><span class="lm-fav"><?= e(mb_strtoupper(mb_substr(preg_replace('/^www\./', '', $host), 0, 1))) ?></span><span class="lm-link-body"><b><?= e($m['title']) ?></b><span><?= e($host) ?></span></span><?= icon('arrow-right', 16) ?></a><?= $del($m) ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
<?php endif ?>
