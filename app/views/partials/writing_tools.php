<section class="ai-writing-tools" aria-label="MANBAR AI writing tools">
  <span class="ai-writing-mark"><?= icon('sparkles', 21) ?></span>
  <div class="ai-writing-copy">
    <b>Write faster with MANBAR AI</b>
    <span>Start with a rough title. I can draft the description, fix spelling and grammar, and choose clearer vocabulary.</span>
  </div>
  <div class="ai-writing-actions">
    <label class="ai-tone-control">Tone
      <select class="select" data-ai-tone aria-label="AI writing tone">
        <option value="natural">Natural</option>
        <option value="professional">Professional</option>
        <option value="academic">Academic</option>
        <option value="friendly">Friendly</option>
        <option value="concise">Concise</option>
      </select>
    </label>
    <button class="btn btn-primary btn-sm" type="button" data-ai-autofill><?= icon('wand', 15) ?> Create a draft</button>
    <button class="btn btn-ghost btn-sm" type="button" data-ai-improve-all><?= icon('sparkles', 15) ?> Improve writing</button>
  </div>
</section>
