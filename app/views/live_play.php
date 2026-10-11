<?php $title = 'Live · ' . $s['title']; ?>
<?php partial('learn_assets') ?>
<div class="lv qt" id="lvPlay" data-code="<?= e($s['code']) ?>">
  <div class="lv-top">
    <span class="lc-live-dot"></span><b class="grow"><?= e($s['title']) ?> <span class="small muted" style="font-weight:500">· live quiz</span></b>
    <span class="lv-chips row" hidden><span class="chip">Rank <b data-rank style="margin-left:4px">–</b></span><span class="chip on"><b data-score>0</b>&nbsp;pts</span></span>
  </div>
  <div class="lv-stage" aria-live="polite"><p class="muted" style="text-align:center">Connecting…</p></div>
</div>
