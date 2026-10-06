<?php $title = 'Offer a service'; ?>
<div class="pagehead"><div><h1>Offer a service</h1><p><?= is_teacher() ? 'Teachers can also list tutoring, workshops and consulting here.' : 'Share what you’re good at. Set a price in AED, or offer it free / as a skill swap.' ?></p></div></div>
<form method="post" action="<?= e(url('marketplace/new')) ?>" class="card" style="max-width:760px" data-ai-writing-form data-ai-context="service"><?= csrf_field() ?>
  <div class="field"><label class="f">Service title</label><input class="input" name="title" data-draft="title" data-ai-writing="title" maxlength="200" required placeholder="I will design a professional logo for your project"></div>
  <div class="field"><label class="f">Description</label><textarea class="textarea" name="description" data-draft="body" data-ai-writing="body" rows="6" required placeholder="What exactly do you offer? What will the buyer receive?"></textarea></div>
  <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
  <div class="grid3"><div class="field"><label class="f">Category</label><select class="select" name="category"><?php foreach (SERVICE_CATEGORIES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach ?></select></div>
    <div class="field"><label class="f">Price (AED) — 0 = free</label><input class="input" type="number" name="price" min="0" max="100000" value="0"></div>
    <div class="field"><label class="f">Delivery (days)</label><input class="input" type="number" name="delivery_days" min="1" max="90" value="3"></div></div>
  <div class="hint mb">Payments happen between you and the buyer. MANBAR connects people; it does not process payments.</div>
  <button class="btn btn-primary btn-lg" type="submit"><?= icon('store', 18) ?> Publish service</button>
</form>
