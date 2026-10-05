<div class="card profile-about-card <?= e($placement ?? '') ?>">
  <div class="profile-about-head"><span class="profile-about-icon"><?= icon('user', 19) ?></span><div><h3>About</h3><small>The person behind the profile</small></div></div>
  <p class="profile-bio <?= $p['bio'] ? '' : 'is-empty' ?>"><?= $p['bio'] ? nl2br(e($p['bio'])) : ($own ? 'Tell the MANBAR community what you are building, learning, or looking for.' : 'No introduction has been added yet.') ?></p>
  <?php if ($contacts): ?>
    <div class="profile-connect-head"><h4>Connect</h4><span><?= count($contacts) ?> link<?= count($contacts) === 1 ? '' : 's' ?></span></div>
    <div class="profile-links">
      <?php foreach ($contacts as [$kind, $label, $socialIcon, $href, $detail]): ?>
        <a class="profile-link profile-link-<?= e($kind) ?>" href="<?= e($href) ?>" <?= str_starts_with($href, 'http') ? 'target="_blank" rel="noopener nofollow"' : '' ?>>
          <span class="profile-link-icon"><?= icon($socialIcon, 18) ?></span><span class="profile-link-copy"><b><?= e($label) ?></b><small><?= e($detail) ?></small></span><span class="profile-link-arrow"><?= icon('arrow-right', 15) ?></span>
        </a>
      <?php endforeach ?>
    </div>
  <?php elseif ($own): ?><a class="profile-add-links" href="<?= e(url('profile/edit')) ?>"><?= icon('plus', 15) ?> Add your social and contact links</a><?php endif ?>
</div>
