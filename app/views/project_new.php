<?php $title = 'Start a project'; ?>
<div class="pagehead"><div><h1>Start a project</h1><p>Describe what you’re building and which skills you need. Students can then apply to join.</p></div></div>
<form method="post" action="<?= e(url('projects/new')) ?>" class="card project-create-card" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($from): ?><input type="hidden" name="from_post" value="<?= (int) $from['id'] ?>"><div class="chip mb">Turning your idea into a project: “<?= e(excerpt($from['title'], 50)) ?>”</div><?php endif ?>
  <div class="field"><label class="f">Project title</label><input class="input" name="title" data-draft="title" maxlength="200" required value="<?= e($from['title'] ?? '') ?>" placeholder="e.g. Campus study-buddy matcher"></div>
  <div class="field"><label class="f">What are you building?</label><textarea class="textarea" name="description" data-draft="body" rows="6" required placeholder="The problem, the plan, and what you want to achieve…"><?= e($from['body'] ?? '') ?></textarea></div>
  <fieldset class="project-cover-field">
    <legend>Project cover</legend>
    <p>Choose a MANBAR background or upload your own image.</p>
    <div class="project-cover-options">
      <?php foreach (PROJECT_COVER_THEMES as $i => $label): ?>
        <label class="project-cover-choice">
          <input type="radio" name="cover_theme" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?>>
          <span class="project-cover-swatch project-cover project-cover-t<?= $i ?>"><i><?= icon('check', 16) ?></i><b><?= e($label) ?></b></span>
        </label>
      <?php endforeach ?>
    </div>
    <label class="project-cover-upload" for="projectCoverInput">
      <span class="project-upload-icon"><?= icon('image', 22) ?></span>
      <span class="grow"><b>Upload your own image</b><small>JPG, PNG, WebP or GIF · maximum 4 MB</small></span>
      <span class="btn btn-ghost btn-sm">Choose image</span>
      <input id="projectCoverInput" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
    </label>
    <div class="project-cover-preview" id="projectCoverPreview" hidden><img alt="Project cover preview"><button class="btn btn-ghost btn-sm" id="projectCoverRemove" type="button"><?= icon('x', 14) ?> Remove</button></div>
  </fieldset>
  <div class="field"><label class="f">Skills you’re looking for</label><div class="tags-in" data-tags="needed_skills" data-initial="<?= e($from['tags'] ?? '') ?>" data-ph="UI design, Python, video editing…"></div></div>
  <div class="field" style="max-width:240px"><label class="f">Team size (max members)</label><input class="input" type="number" name="max_members" min="2" max="30" value="5"></div>
  <button class="btn btn-primary btn-lg" type="submit"><?= icon('rocket', 18) ?> Create project</button>
</form>
