<?php /** Drop zone for several files at once, plus links (one per line). Posts files[] and links. */ ?>
<div class="lm-attach">
  <label class="lm-drop" data-drop>
    <input type="file" name="files[]" multiple accept="<?= e(COURSE_FILE_ACCEPT) ?>" data-drop-input>
    <span class="lm-drop-ico"><?= icon('upload', 22) ?></span>
    <b>Drop files here or <u>browse</u></b>
    <span class="small muted">Images, PDF, Word, PowerPoint, Excel or ZIP · up to <?= COURSE_FILE_MAX_MB ?> MB each · pick several at once</span>
    <span class="lm-drop-list" data-drop-list aria-live="polite"></span>
  </label>
  <div class="field" style="margin:12px 0 0"><label class="f">Links (optional, one per line)</label>
    <textarea class="textarea" name="links" rows="2" placeholder="https://www.w3schools.com/sql/&#10;SQL practice | https://sqlbolt.com"></textarea>
    <span class="hint">YouTube and Vimeo links play on the page. Give a link a title with “Title | link”.</span></div>
</div>
