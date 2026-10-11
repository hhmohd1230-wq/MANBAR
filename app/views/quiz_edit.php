<?php
$title = 'Edit quiz · ' . $quiz['title'];
$cid = (int) $c['id'];
$qid = (int) $quiz['id'];
$base = "learn/$cid/quiz/$qid";
$totalPts = array_sum(array_column($questions, 'points'));
$mcq = count(array_filter($questions, fn($q) => $q['type'] === 'mcq'));
?>
<?php partial('learn_assets') ?>
<?php partial('back_button', ['href' => "learn/$cid#quizzes", 'label' => 'Back to ' . $c['title']]) ?>
<div class="pagehead"><div><h1><?= e($quiz['title']) ?></h1><p><?= count($questions) ?> questions · <?= $totalPts ?> points · <?= $quiz['status'] === 'published' ? 'Published' : 'Draft (students can’t see it yet)' ?></p></div>
  <div class="row wrap">
    <form method="post" action="<?= e(url("$base/test")) ?>"><?= csrf_field() ?><button class="btn" type="submit" title="Take it like a student. Not counted in results."><?= icon('check-circle', 16) ?> Test quiz</button></form>
    <a class="btn" href="<?= e(url("$base/results")) ?>"><?= icon('chart', 16) ?> Results</a>
    <?php if ($mcq): ?><form method="post" action="<?= e(url("$base/live")) ?>"><?= csrf_field() ?><button class="btn btn-primary" type="submit"><?= icon('play', 16) ?> Start live quiz</button></form><?php endif ?>
  </div>
</div>

<div class="page-grid">
  <div class="stack gap-l">
    <section class="card" id="questions">
      <div class="card-title"><h3>Questions</h3><span class="chip sm"><?= count($questions) ?></span></div>
      <?php if ($questions): ?><div class="qb-list">
        <?php foreach ($questions as $i => $q): ?>
          <div class="qb-item">
            <span class="qb-n"><?= $i + 1 ?></span>
            <div><p><?= e($q['prompt']) ?></p>
              <?php if ($q['type'] === 'mcq'): ?><div class="qb-opts"><?php foreach ($q['options'] as $k => $o): ?><span class="<?= $k === (int) $q['correct_index'] ? 'ok' : '' ?>"><?= $k === (int) $q['correct_index'] ? '✔' : '○' ?> <?= chr(65 + $k) ?>) <?= e($o) ?></span><?php endforeach ?></div>
              <?php else: ?><div class="qb-opts"><span>✍ Essay answer<?= $q['keywords'] ? ' · auto-mark keywords: ' . e($q['keywords']) : ' · marked by you' ?></span></div><?php endif ?>
              <div class="qb-meta"><?= (int) $q['points'] ?> point<?= $q['points'] == 1 ? '' : 's' ?><?= $q['explanation'] ? ' · explanation: ' . e(excerpt($q['explanation'], 80)) : '' ?></div></div>
            <form method="post" action="<?= e(url("$base/questions/{$q['id']}/delete")) ?>" data-confirm="Delete question <?= $i + 1 ?>?"><?= csrf_field() ?><button class="icon-btn" aria-label="Delete question"><?= icon('trash', 15) ?></button></form>
          </div>
        <?php endforeach ?>
      </div><?php else: ?><p class="muted">No questions yet. Paste many at once below, or add them one by one.</p><?php endif ?>
      <span id="q-end"></span>
    </section>

    <section class="card" id="import">
      <div class="card-title"><h3><?= icon('upload', 18) ?> Add many questions at once</h3></div>
      <p class="small muted">Paste your questions (or upload a Word / text file). MANBAR splits them into separate questions and shows you a preview before saving.</p>
      <details style="margin:8px 0 12px"><summary class="small" style="cursor:pointer;font-weight:600">How to write them</summary>
        <div class="qb-format">1. What does HTML stand for?
A) Hyper Trainer Marking Language
B) Hyper Text Markup Language *
C) High Text Machine Language

2. Which tag makes the biggest heading? (2 pts)
a) &lt;h6&gt;
b) &lt;h1&gt;
Answer: B
Explanation: h1 is the top-level heading.

Essay: Explain margin vs padding. (5 marks)
Keywords: margin, padding, border, outside, inside</div>
        <p class="hint">Mark the right option with <b>*</b> or <b>(correct)</b>, or add an <b>Answer: B</b> line. A question with no options becomes an essay. <b>Keywords</b> let MANBAR auto-mark essays (you can still change the mark).</p>
      </details>
      <form method="post" action="<?= e(url("$base/import")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
        <div class="field"><textarea class="textarea" name="text" rows="9" placeholder="1. Question…&#10;A) …&#10;B) … *&#10;&#10;2. Next question…" style="font-family:ui-monospace,Consolas,monospace;font-size:14px"><?= e($preview['text'] ?? '') ?></textarea></div>
        <div class="row wrap"><label class="small" style="font-weight:600">Or a file: <input type="file" name="file" accept=".docx,.txt,.md"></label><span class="grow"></span>
          <button class="btn" type="submit" name="mode" value="preview"><?= icon('eye', 16) ?> Preview split</button>
          <?php if ($preview && $preview['questions']): ?><button class="btn btn-primary" type="submit" name="mode" value="save"><?= icon('check', 16) ?> Save <?= count($preview['questions']) ?> questions</button><?php endif ?>
        </div>
      </form>
      <?php if ($preview): ?>
        <?php if ($preview['questions']): ?>
          <div class="qb-preview"><b><?= icon('check-circle', 16) ?> Found <?= count($preview['questions']) ?> question<?= count($preview['questions']) === 1 ? '' : 's' ?></b>
            <div class="qb-list" style="margin-top:10px"><?php foreach ($preview['questions'] as $i => $q): ?>
              <div class="qb-item" style="grid-template-columns:auto 1fr"><span class="qb-n"><?= $i + 1 ?></span><div><p><?= e($q['prompt']) ?></p>
                <?php if ($q['type'] === 'mcq'): ?><div class="qb-opts"><?php foreach ($q['options'] as $k => $o): ?><span class="<?= $k === $q['correct'] ? 'ok' : '' ?>"><?= $k === $q['correct'] ? '✔' : '○' ?> <?= chr(65 + $k) ?>) <?= e($o) ?></span><?php endforeach ?></div><?php else: ?><div class="qb-opts"><span>✍ Essay<?= $q['keywords'] ? ' · keywords: ' . e($q['keywords']) : '' ?></span></div><?php endif ?>
                <div class="qb-meta"><?= (int) $q['points'] ?> pt<?= $q['explanation'] ? ' · ' . e(excerpt($q['explanation'], 70)) : '' ?></div></div></div>
            <?php endforeach ?></div></div>
        <?php endif ?>
        <?php if ($preview['problems']): ?><div class="qb-problems"><b><?= $preview['saved'] ? 'Skipped while saving:' : 'Needs fixing (these will be skipped):' ?></b><ul style="margin:6px 0 0;padding-left:18px"><?php foreach ($preview['problems'] as $p): ?><li><?= e($p) ?></li><?php endforeach ?></ul></div><?php endif ?>
      <?php endif ?>
    </section>

    <form class="card" id="add" method="post" action="<?= e(url("$base/questions")) ?>"><?= csrf_field() ?>
      <div class="card-title"><h3><?= icon('plus', 18) ?> Add one question</h3></div>
      <div class="lc-switch" data-switch><label><input type="radio" name="type" value="mcq" checked><span>Multiple choice</span></label><label><input type="radio" name="type" value="essay"><span>Essay</span></label></div>
      <div class="field"><label class="f">Question</label><textarea class="textarea" name="prompt" rows="3" required></textarea></div>
      <div data-when="mcq"><label class="f">Options (select the right one)</label>
        <div data-opts><?php for ($k = 0; $k < 4; $k++): ?><div class="qb-optrow"><input type="radio" name="correct" value="<?= $k ?>" aria-label="Correct answer"><input class="input" name="options[]" placeholder="Option <?= chr(65 + $k) ?>"></div><?php endfor ?></div>
        <button class="btn btn-sm btn-ghost" type="button" data-add-opt><?= icon('plus', 14) ?> Add option</button></div>
      <div class="field" data-when="essay"><label class="f">Keywords for auto-marking (optional, comma-separated)</label><input class="input" name="keywords" placeholder="margin, padding, border"></div>
      <div class="grid2" style="margin-top:12px"><div class="field"><label class="f">Points</label><input class="input" type="number" name="points" min="1" max="100" value="1"></div>
        <div class="field"><label class="f">Explanation shown after answering (optional)</label><input class="input" name="explanation" maxlength="2000"></div></div>
      <button class="btn btn-primary" type="submit">Add question</button>
    </form>
  </div>

  <aside class="aside">
    <form class="card" method="post" action="<?= e(url("$base/settings")) ?>"><?= csrf_field() ?>
      <div class="card-title"><h3><?= icon('settings', 16) ?> Quiz settings</h3></div>
      <div class="field"><label class="f">Title</label><input class="input" name="title" value="<?= e($quiz['title']) ?>" maxlength="200" required></div>
      <div class="field"><label class="f">Instructions (optional)</label><textarea class="textarea" name="description" rows="3"><?= e((string) $quiz['description']) ?></textarea></div>
      <div class="grid2"><div class="field"><label class="f">Time limit (min)</label><input class="input" type="number" name="time_limit_min" min="0" max="300" value="<?= (int) $quiz['time_limit_min'] ?>"><p class="hint">0 = none</p></div>
        <div class="field"><label class="f">Pass mark (%)</label><input class="input" type="number" name="pass_percent" min="0" max="100" value="<?= (int) $quiz['pass_percent'] ?>"></div></div>
      <div class="grid2"><div class="field"><label class="f">Attempts</label><input class="input" type="number" name="max_attempts" min="0" max="20" value="<?= (int) $quiz['max_attempts'] ?>"><p class="hint">0 = unlimited</p></div>
        <div class="field"><label class="f">Live: seconds / question</label><input class="input" type="number" name="live_seconds" min="5" max="120" value="<?= (int) $quiz['live_seconds'] ?>"></div></div>
      <label class="row small" style="margin:6px 0"><input type="checkbox" name="shuffle" value="1" <?= $quiz['shuffle'] ? 'checked' : '' ?>> Shuffle question order</label>
      <label class="row small" style="margin:6px 0"><input type="checkbox" name="essay_auto" value="1" <?= $quiz['essay_auto'] ? 'checked' : '' ?>> Auto-mark essays that have keywords</label>
      <label class="row small" style="margin:6px 0 12px;align-items:flex-start"><input type="checkbox" name="live_reveal" value="1" <?= !empty($quiz['live_reveal']) ? 'checked' : '' ?> style="margin-top:3px"> <span>Live quiz: show the right answer and scores after each question<br><span class="muted">Off: students see all right answers and their result at the end.</span></span></label>
      <div class="field"><label class="f">Visibility</label><select class="select" name="status"><option value="draft" <?= $quiz['status'] !== 'published' ? 'selected' : '' ?>>Draft (only you)</option><option value="published" <?= $quiz['status'] === 'published' ? 'selected' : '' ?>>Published (students + notify them)</option></select></div>
      <button class="btn btn-primary btn-block" type="submit">Save settings</button>
    </form>
    <div class="card"><div class="card-title"><h3><?= icon('check-circle', 16) ?> Test it yourself</h3></div>
      <p class="small muted">Take the quiz exactly as a student sees it (timer, one question per screen, auto-marking) and see the mark you would get. Test runs are never counted.</p>
      <form method="post" action="<?= e(url("$base/test")) ?>"><?= csrf_field() ?><button class="btn btn-block" type="submit"><?= icon('play', 16) ?> Test quiz</button></form></div>
    <div class="card"><div class="card-title"><h3><?= icon('play', 16) ?> Live quiz</h3></div>
      <p class="small muted">Run this quiz live: everyone answers the same question at the same time, faster correct answers score more, with a live leaderboard. Uses the <?= $mcq ?> multiple-choice question<?= $mcq === 1 ? '' : 's' ?>.</p>
      <?php if ($mcq): ?><form method="post" action="<?= e(url("$base/live")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-block" type="submit">Start live session</button></form><?php else: ?><p class="small">Add multiple-choice questions to run it live.</p><?php endif ?></div>
    <form class="card" method="post" action="<?= e(url("$base/delete")) ?>" data-confirm="Delete this quiz and all its results?"><?= csrf_field() ?><button class="btn btn-danger btn-sm btn-block"><?= icon('trash', 14) ?> Delete quiz</button></form>
  </aside>
</div>
