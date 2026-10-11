<?php
/** Portfolio items of a listing: images (gallery + viewer), folders, documents and links. Needs $items, $sid, $canEdit. */
$fileUrl = fn(array $f) => url("marketplace/$sid/file/{$f['id']}");
$del = fn(array $f) => $canEdit ? '<form class="lm-del" method="post" action="' . e(url("marketplace/$sid/files/{$f['id']}/delete")) . '" data-confirm="Remove “' . e($f['title']) . '”?">' . csrf_field() . '<button class="icon-btn" type="submit" aria-label="Remove ' . e($f['title']) . '">' . icon('trash', 14) . '</button></form>' : '';
$badge = ['pdf' => 'PDF', 'doc' => 'DOC', 'ppt' => 'PPT', 'xls' => 'XLS', 'zip' => 'ZIP', 'txt' => 'TXT'];
$images = $docs = $links = $videos = $folders = [];
foreach ($items as $f) {
    if ($f['folder']) { $folders[$f['folder']][] = $f; continue; }
    $k = material_kind($f);
    if ($k === 'image') $images[] = $f; elseif ($k === 'video') $videos[] = $f; elseif ($k === 'link') $links[] = $f; else $docs[] = $f;
}
?>
<div class="lm">
  <?php if ($images): ?>
    <div class="lm-gallery<?= count($images) === 1 ? ' is-single' : '' ?>">
      <?php foreach ($images as $f): ?>
        <figure class="lm-img">
          <button type="button" data-lightbox="<?= e($fileUrl($f)) ?>" data-caption="<?= e($f['title'] . ($f['note'] ? ' · ' . $f['note'] : '')) ?>" aria-label="View image: <?= e($f['title']) ?>"><img src="<?= e($fileUrl($f)) ?>" alt="<?= e($f['title']) ?>" loading="lazy"><span class="lm-zoom"><?= icon('search', 16) ?></span></button>
          <figcaption><?= e($f['title']) ?><?php if ($f['note']): ?><span><?= e($f['note']) ?></span><?php endif ?></figcaption><?= $del($f) ?>
        </figure>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <?php foreach ($videos as $f): ?>
    <div class="lm-video"><iframe src="<?= e(video_embed_url($f['url'])) ?>" allowfullscreen loading="lazy" title="<?= e($f['title']) ?>" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe>
      <div class="lm-video-cap"><?= icon('play', 14) ?> <b><?= e($f['title']) ?></b><span class="muted small"><?= e($f['note'] ?: link_host($f['url'])) ?></span><?= $del($f) ?></div></div>
  <?php endforeach ?>

  <?php if ($folders || $docs): ?>
    <div class="lm-docs">
      <?php foreach ($folders as $name => $list): $size = array_sum(array_column($list, 'size')); ?>
        <details class="mk-folder">
          <summary><span class="mk-folder-ico"><?= icon('layers', 22) ?></span><span class="lm-doc-body"><b><?= e($name) ?></b><span>Folder · <?= count($list) ?> file<?= count($list) === 1 ? '' : 's' ?> · <?= e(file_size_label($size)) ?></span></span><span class="mk-folder-open">Open</span></summary>
          <ul><?php foreach ($list as $f): $k = material_kind($f); ?>
            <li><span class="lm-badge t-<?= $k ?>"><?= $k === 'image' ? icon('image', 13) : e($badge[$k] ?? 'FILE') ?></span><span class="grow"><?= e($f['file_name']) ?> <span class="muted xs"><?= e(file_size_label((int) $f['size'])) ?></span></span>
              <a class="btn btn-sm btn-ghost" href="<?= e($fileUrl($f)) ?><?= in_array($k, ['image', 'pdf'], true) ? '' : '?download=1' ?>" target="_blank" rel="noopener"><?= in_array($k, ['image', 'pdf'], true) ? 'View' : icon('download', 14) ?></a><?= $del($f) ?></li>
          <?php endforeach ?></ul>
        </details>
      <?php endforeach ?>
      <?php foreach ($docs as $f): $k = material_kind($f); $ext = strtoupper(pathinfo((string) $f['file_name'], PATHINFO_EXTENSION)); ?>
        <div class="lm-doc t-<?= $k ?>">
          <span class="lm-badge t-<?= $k ?>"><?= e($badge[$k] ?? $ext) ?></span>
          <span class="lm-doc-body"><b><?= e($f['title']) ?></b><span><?= e($f['note'] ?: $ext . ' · ' . file_size_label((int) $f['size'])) ?></span></span>
          <span class="lm-doc-act"><?php if ($k === 'pdf'): ?><button class="btn btn-sm" type="button" data-pdf="sf<?= (int) $f['id'] ?>" data-src="<?= e($fileUrl($f)) ?>">Preview</button><?php else: ?><a class="btn btn-sm" href="<?= e($fileUrl($f)) ?>?download=1"><?= icon('download', 14) ?> Download</a><?php endif ?><?= $del($f) ?></span>
          <?php if ($k === 'pdf'): ?><div class="lm-pdf" id="sf<?= (int) $f['id'] ?>" hidden></div><?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <?php if ($links): ?>
    <div class="lm-links">
      <?php foreach ($links as $f): $host = link_host($f['url']); ?>
        <div class="lm-link"><a href="<?= e($f['url']) ?>" target="_blank" rel="noopener"><span class="lm-fav"><?= e(mb_strtoupper(mb_substr(preg_replace('/^www\./', '', $host), 0, 1))) ?></span><span class="lm-link-body"><b><?= e($f['title']) ?></b><span><?= e($f['note'] ?: $host) ?></span></span><?= icon('arrow-right', 16) ?></a><?= $del($f) ?></div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
