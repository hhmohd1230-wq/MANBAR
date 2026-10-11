<?php /** Portfolio upload: files, a whole folder, links, and "this is proof of past work". Posts files[], folder[], links, is_proof, proof_note. */ ?>
<div class="lm-attach">
  <div class="mk-drops">
    <label class="lm-drop" data-drop>
      <input type="file" name="files[]" multiple accept="<?= e(COURSE_FILE_ACCEPT) ?>" data-drop-input>
      <span class="lm-drop-ico"><?= icon('upload', 22) ?></span>
      <b>Drop files or <u>browse</u></b>
      <span class="small muted">Images, PDF, Word, PowerPoint, Excel, ZIP</span>
      <span class="lm-drop-list" data-drop-list aria-live="polite"></span>
    </label>
    <label class="lm-drop" data-drop>
      <input type="file" name="folder[]" multiple webkitdirectory directory data-drop-input>
      <span class="lm-drop-ico"><?= icon('layers', 22) ?></span>
      <b>Upload a whole <u>folder</u></b>
      <span class="small muted">Shown as one folder buyers can open</span>
      <span class="lm-drop-list" data-drop-list aria-live="polite"></span>
    </label>
  </div>
  <p class="hint" style="margin:6px 0 0">Up to <?= SERVICE_FILE_MAX ?> files at a time, <?= COURSE_FILE_MAX_MB ?> MB each. Other file types in a folder are skipped.</p>
  <div class="field" style="margin:12px 0 0"><label class="f">Links (optional, one per line)</label>
    <textarea class="textarea" name="links" rows="2" placeholder="Behance project | https://www.behance.net/…&#10;https://github.com/your-name/your-project"></textarea>
    <span class="hint">YouTube and Vimeo links play on the page. Add a title with “Title | link”.</span></div>
  <label class="mk-proof-check"><input type="checkbox" name="is_proof" value="1"><span><b>This is proof of past work</b><span class="small muted">Shown first, under “Past work”, so buyers see what you already delivered.</span></span></label>
  <div class="field" style="margin:8px 0 0"><label class="f">Who was it for? (optional)</label><input class="input" name="proof_note" maxlength="255" placeholder="e.g. Logo for the AAU Robotics Club, March 2026"></div>
</div>
