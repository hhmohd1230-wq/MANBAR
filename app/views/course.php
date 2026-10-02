<?php
$title = $c['title'];
$total = count($lessons);
$doneN = count(array_filter($lessons, fn($l) => in_array((int) $l['id'], $done, true)));
$pct = $total ? round($doneN / $total * 100) : 0;
$canEdit = $isAuthor || is_admin();
if (!function_exists("yt_embed")) { function yt_embed(?string $u): ?string
{
    if (!$u) return null;
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})~', $u, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    return null;
} }
?>
<div class="page-grid">
  <div class="stack gap-l">
    <?php if ($lesson): $emb = yt_embed($lesson['video_url']); $isDone = in_array((int) $lesson['id'], $done, true); ?>
      <a class="small row" href="<?= e(url('learn/' . $c['id'])) ?>"><?= icon('chevron-right', 14, '') ?> Back to <?= e($c['title']) ?></a>
      <div class="card">
        <span class="chip sm">Lesson <?= (int) $lesson['position'] ?></span><h1 style="margin-top:8px"><?= e($lesson['title']) ?></h1>
        <?php if ($emb): ?><iframe class="video mb" src="<?= e($emb) ?>" allowfullscreen loading="lazy" title="Lesson video"></iframe><?php elseif ($lesson['video_url']): ?><p><a class="btn" target="_blank" rel="noopener" href="<?= e($lesson['video_url']) ?>"><?= icon('play', 16) ?> Open lesson video</a></p><?php endif ?>
        <div class="prose"><?= rich($lesson['content']) ?></div>
        <?php if ($enrolled): ?><form method="post" action="<?= e(url('learn/' . $c['id'] . '/lesson/' . $lesson['id'] . '/complete')) ?>" class="mt-l"><?= csrf_field() ?>
          <button class="btn btn-primary btn-lg"><?= $isDone ? icon('check-circle', 18) . ' Completed — continue' : icon('check', 18) . ' Mark complete &amp; continue' ?></button></form><?php endif ?>
      </div>
    <?php else: ?>
      <div class="card" style="padding:0;overflow:hidden">
        <div class="item-cover" style="height:150px;<?= theme_css((int) $c['theme']) ?>"><span class="chip sm" style="background:rgba(255,255,255,.92)"><?= e(COURSE_CATEGORIES[$c['category']] ?? 'General') ?></span><span class="ic-big"><?= icon('book', 38) ?></span></div>
        <div style="padding:24px 28px 28px"><h1 style="margin-bottom:8px"><?= e($c['title']) ?></h1>
          <div class="row wrap small muted"><span class="chip sm outline"><?= e(ucfirst($c['level'])) ?></span><span><?= icon('layers', 14) ?> <?= $total ?> lessons</span><span><?= icon('users', 14) ?> <?= $students ?> enrolled</span></div>
          <div class="prose" style="margin-top:14px;font-size:15.5px"><?= rich($c['description']) ?></div>
          <div class="row wrap" style="margin-top:18px">
            <?php if ($enrolled): ?><span class="chip on"><?= icon('check', 14) ?> Enrolled</span><?php if ($total && $lessons): $next = null; foreach ($lessons as $l) if (!in_array((int) $l['id'], $done, true)) { $next = $l; break; } ?><a class="btn btn-primary" href="<?= e(url('learn/' . $c['id'] . '/lesson/' . ($next['id'] ?? $lessons[0]['id']))) ?>"><?= icon('play', 16) ?> <?= $doneN ? ($next ? 'Continue' : 'Review') : 'Start learning' ?></a><?php endif ?>
            <?php elseif (!$isAuthor): ?><form method="post" action="<?= e(url('learn/' . $c['id'] . '/enroll')) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-lg"><?= icon('rocket', 18) ?> Enroll for free</button></form><?php endif ?>
          </div>
          <?php if ($enrolled && $total): ?><div class="progress" style="margin-top:16px"><i style="width:<?= $pct ?>%"></i></div><div class="xs muted" style="margin-top:4px"><?= $doneN ?>/<?= $total ?> lessons complete</div><?php endif ?>
          <?php if ($enrolled && $enrolled['completed_at']): ?><div class="card flat mt" style="background:var(--g50)"><b><?= icon('cap', 18) ?> You completed this course 🎓</b></div><?php endif ?>
        </div>
      </div>
    <?php endif ?>

    <?php if ($canEdit && !$lesson): ?>
    <div class="card"><div class="card-title"><h3><?= icon('plus', 18) ?> Add a lesson</h3></div>
      <form method="post" action="<?= e(url('learn/' . $c['id'] . '/lessons')) ?>"><?= csrf_field() ?>
        <div class="field"><label class="f">Lesson title</label><input class="input" name="title" required maxlength="200"></div>
        <div class="field"><label class="f">Content</label><textarea class="textarea" name="content" rows="6" required placeholder="Write the lesson. Line breaks and links are supported."></textarea></div>
        <div class="field"><label class="f">Video link (optional, YouTube works best)</label><input class="input" name="video_url" placeholder="https://www.youtube.com/watch?v=…"></div>
        <button class="btn btn-primary" type="submit">Add lesson</button></form></div>
    <?php endif ?>
  </div>
  <aside class="aside">
    <div class="card"><div class="card-title"><h3>Lessons</h3><span class="chip sm"><?= $total ?></span></div>
      <div class="lesson-list"><?php foreach ($lessons as $l): $d = in_array((int) $l['id'], $done, true); ?>
        <a class="<?= $lesson && (int) $lesson['id'] === (int) $l['id'] ? 'on' : '' ?>" href="<?= e(url('learn/' . $c['id'] . '/lesson/' . $l['id'])) ?>"><span class="lesson-num <?= $d ? 'done' : '' ?>"><?= $d ? icon('check', 15) : (int) $l['position'] ?></span><span class="grow small"><b><?= e($l['title']) ?></b></span>
          <?php if ($canEdit): ?><form method="post" action="<?= e(url('learn/' . $c['id'] . '/delete')) ?>" data-confirm="Delete this lesson?" onclick="event.stopPropagation()"><?= csrf_field() ?><input type="hidden" name="lesson" value="<?= (int) $l['id'] ?>"><button class="icon-btn" style="width:28px;height:28px" aria-label="Delete lesson"><?= icon('trash', 14) ?></button></form><?php endif ?></a>
      <?php endforeach ?><?php if (!$lessons): ?><p class="muted small">No lessons yet.</p><?php endif ?></div></div>
    <div class="card"><div class="card-title"><h3>Instructor</h3></div>
      <div class="person"><a href="<?= e(url('profile/' . $c['author_id'])) ?>"><?= avatar($c, 50) ?></a><div class="grow"><a class="nm" href="<?= e(url('profile/' . $c['author_id'])) ?>"><?= e($c['full_name']) ?></a><?= verified_badge($c) ?><div class="xs muted"><?= e($c['headline'] ?: 'Teacher') ?></div></div></div>
      <?php if ($canEdit): ?><form method="post" action="<?= e(url('learn/' . $c['id'] . '/delete')) ?>" data-confirm="Delete the whole course?" class="mt"><?= csrf_field() ?><button class="btn btn-danger btn-sm btn-block"><?= icon('trash', 14) ?> Delete course</button></form><?php endif ?></div>
  </aside>
</div>
