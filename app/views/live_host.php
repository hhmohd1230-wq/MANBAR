<?php $title = 'Hosting · ' . $s['title']; ?>
<?php partial('learn_assets') ?>
<div class="lv" id="lvHost" data-code="<?= e($s['code']) ?>">
  <div class="lv-top">
    <div class="lv-code"><div><small>Join at <?= e(rtrim(cfg('base_url') ?: ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/') . url('live')) ?> with code</small><b><?= e($s['code']) ?></b></div></div>
    <div class="grow"><b style="font-size:18px"><?= e($s['title']) ?></b><br><span class="small muted"><?= (int) $total ?> questions · <?= (int) $s['live_seconds'] ?> s each · <span data-players>0</span> joined</span></div>
    <a class="btn" href="<?= e(url('live/' . $s['code'] . '?player=1')) ?>" target="_blank" rel="noopener" title="Join as a player in a new tab to see what students see"><?= icon('eye', 16) ?> Open student view</a>
    <a class="btn btn-ghost" data-back href="<?= e(url('learn/' . $c['id'] . '/dashboard#live')) ?>">Exit</a>
  </div>
  <div class="lv-stage" aria-live="polite"><p class="muted" style="text-align:center">Loading…</p></div>
  <div class="row wrap lv-ctrl" style="justify-content:center;margin-top:16px"></div>
</div>
