<?php /** Clear "back" button shown at the very top of learning pages. Needs $href (path for url()) and $label. */ ?>
<a class="lc-back" href="<?= e(url($href)) ?>"><?= icon('arrow-right', 16, 'flip') ?> <span><?= e($label) ?></span></a>
