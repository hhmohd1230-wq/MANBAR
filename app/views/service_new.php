<?php
$edit = (int) $s['id'] > 0;
$title = $edit ? 'Edit listing' : 'New listing';
$pk = [];
foreach (service_packages($s) as $p) $pk[$p['key'] ?? strtolower($p['name'])] = $p;
$defaults = ['basic' => ['Basic', 'The essentials'], 'standard' => ['Standard', 'Most popular'], 'premium' => ['Premium', 'Everything, faster']];
?>
<?php partial('market_assets') ?>
<?php partial('back_button', ['href' => $edit ? 'marketplace/' . $s['id'] : 'marketplace', 'label' => $edit ? 'Back to the listing' : 'Back to marketplace']) ?>
<div class="pagehead"><div><h1><?= $edit ? 'Edit your listing' : 'Create a listing' ?></h1><p>Offer a skill to your campus, or post what you need and let people apply.</p></div></div>

<form method="post" action="<?= e(url($edit ? 'marketplace/' . $s['id'] . '/edit' : 'marketplace/new')) ?>" enctype="multipart/form-data" class="mk-form" data-ai-writing-form data-ai-context="service"><?= csrf_field() ?>
  <section class="card">
    <h2 class="lc-h2">1 · What kind of listing?</h2>
    <div class="mk-kindpick">
      <label><input type="radio" name="kind" value="offer"<?= $s['kind'] !== 'request' ? ' checked' : '' ?>><span><?= icon('briefcase', 22) ?><b>Offer a service</b><small>You do the work: design, code, tutoring, editing…</small></span></label>
      <label><input type="radio" name="kind" value="request"<?= $s['kind'] === 'request' ? ' checked' : '' ?>><span><?= icon('user-plus', 22) ?><b>Find people</b><small>You need people for a project, team or event</small></span></label>
    </div>
  </section>

  <section class="card">
    <h2 class="lc-h2">2 · Cover picture</h2>
    <label class="mk-coverpick" style="<?= service_cover_css($s) ?>">
      <input type="file" name="cover" accept="image/png,image/jpeg,image/webp,image/gif" data-cover-input>
      <span class="mk-coverpick-msg"><?= icon('image', 22) ?> <b><?= empty($s['cover_image']) ? 'Add a cover picture' : 'Change the cover picture' ?></b><small>A wide image works best (1600 × 640). JPG, PNG or WebP.</small></span>
    </label>
  </section>

  <section class="card">
    <h2 class="lc-h2">3 · Details</h2>
    <?php partial('writing_tools') ?>
    <div class="field"><label class="f">Title</label><input class="input" name="title" data-ai-writing="title" maxlength="200" required value="<?= e($s['title']) ?>" placeholder="I will design a modern logo and brand kit for your project"></div>
    <div class="grid2"><div class="field"><label class="f">Category</label><select class="select" name="category"><?php foreach (SERVICE_CATEGORIES as $k => $l): ?><option value="<?= $k ?>"<?= $s['category'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
      <div class="field"><label class="f">Skills (comma separated)</label><input class="input" name="skills" value="<?= e((string) $s['skills']) ?>" placeholder="Figma, Branding, Illustrator"></div></div>
    <div class="field"><label class="f">Description</label><textarea class="textarea" name="description" data-ai-writing="body" rows="6" required placeholder="What exactly do you offer or need? Who is it for? How does it work?"><?= e($s['description']) ?></textarea></div>
    <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
    <div class="field"><label class="f"><span class="only-offer">What the buyer gets</span><span class="only-request">What the people will do</span> (one per line)</label><textarea class="textarea" name="includes" rows="4" placeholder="Logo in 3 colour versions&#10;PNG, SVG and PDF files&#10;A one-page brand guide"><?= e((string) $s['includes']) ?></textarea></div>
  </section>

  <section class="card">
    <h2 class="lc-h2">4 · <span class="only-offer">Price &amp; delivery</span><span class="only-request">People, budget &amp; timeline</span></h2>
    <div class="only-request">
      <div class="grid3">
        <div class="field"><label class="f">How many people do you need?</label><input class="input" type="number" name="slots" min="1" max="50" value="<?= (int) $s['slots'] ?>"></div>
        <div class="field"><label class="f">Budget per person (AED, 0 = volunteer)</label><input class="input" type="number" name="price_req" min="0" max="100000" value="<?= (int) $s['price'] ?>"></div>
        <div class="field"><label class="f">Timeline (days)</label><input class="input" type="number" name="days_req" min="1" max="90" value="<?= (int) $s['delivery_days'] ?>"></div>
      </div>
    </div>
    <div class="only-offer mk-pricing">
      <label class="mk-switchline"><input type="checkbox" name="use_packages" value="1"<?= $pk ? ' checked' : '' ?>><span><b>Offer 3 packages</b><small>Basic, Standard and Premium, like the big freelance sites</small></span></label>
      <div class="mk-single grid3">
        <div class="field"><label class="f">Price (AED, 0 = free)</label><input class="input" type="number" name="price" min="0" max="100000" value="<?= (int) $s['price'] ?>"></div>
        <div class="field"><label class="f">Delivery (days)</label><input class="input" type="number" name="delivery_days" min="1" max="90" value="<?= (int) $s['delivery_days'] ?>"></div>
        <div class="field"><label class="f">Revisions included</label><input class="input" type="number" name="revisions" min="0" max="20" value="<?= (int) $s['revisions'] ?>"></div>
      </div>
      <div class="mk-pkgedit">
        <?php foreach ($defaults as $k => [$name, $hint]): $p = $pk[$k] ?? null; ?>
          <div class="mk-pkgcol">
            <input class="input mk-pkgname" name="pkg_<?= $k ?>_name" value="<?= e($p['name'] ?? $name) ?>" maxlength="30" aria-label="<?= $name ?> package name">
            <label class="f">Price (AED)</label><input class="input" type="number" name="pkg_<?= $k ?>_price" min="0" max="100000" value="<?= (int) ($p['price'] ?? 0) ?>">
            <label class="f">Delivery (days)</label><input class="input" type="number" name="pkg_<?= $k ?>_days" min="1" max="90" value="<?= (int) ($p['days'] ?? 3) ?>">
            <label class="f">What’s included</label><textarea class="textarea" name="pkg_<?= $k ?>_desc" rows="3" maxlength="300" placeholder="<?= e($hint) ?>"><?= e($p['desc'] ?? '') ?></textarea>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </section>

  <section class="card">
    <h2 class="lc-h2">5 · <span class="only-offer">Portfolio &amp; proof of work</span><span class="only-request">Files &amp; brief</span> <span class="muted small" style="font-weight:500">(optional)</span></h2>
    <?php partial('service_upload_fields') ?>
  </section>

  <div class="row wrap"><button class="btn btn-primary btn-lg" type="submit"><?= icon($edit ? 'check' : 'store', 18) ?> <?= $edit ? 'Save changes' : 'Publish listing' ?></button><span class="small muted">MANBAR connects people; payment is agreed between you and the other person.</span></div>
</form>
