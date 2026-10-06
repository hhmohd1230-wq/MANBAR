<?php $title = 'Create a course'; ?>
<div class="pagehead"><div><h1>Create a course</h1><p>Set up the course first, then add lessons one by one.</p></div></div>
<form method="post" action="<?= e(url('learn/new')) ?>" class="card" style="max-width:760px" data-ai-writing-form data-ai-context="course"><?= csrf_field() ?>
  <?php partial('writing_tools') ?>
  <div class="field"><label class="f">Course title</label><input class="input" name="title" data-draft="title" data-ai-writing="title" maxlength="200" required placeholder="Intro to Web Development"></div>
  <div class="field"><label class="f">Description</label><textarea class="textarea" name="description" data-draft="body" data-ai-writing="body" rows="5" required placeholder="What will students learn? Who is it for?"></textarea></div>
  <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
  <div class="grid2"><div class="field"><label class="f">Category</label><select class="select" name="category"><?php foreach (COURSE_CATEGORIES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach ?></select></div>
  <div class="field"><label class="f">Level</label><select class="select" name="level"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div></div>
  <button class="btn btn-primary btn-lg" type="submit"><?= icon('book', 18) ?> Create course</button>
</form>
