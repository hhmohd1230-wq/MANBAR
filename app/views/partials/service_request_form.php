<?php /** Request a service (or apply to a "looking for people" post). Needs $s, $pk, $isReq. */ $sid = (int) $s['id']; ?>
<form method="post" action="<?= e(url("marketplace/$sid/request")) ?>" data-ai-writing-form data-ai-context="marketplace request"><?= csrf_field() ?>
  <?php if ($pk): ?><div class="mk-pkgs" role="radiogroup" aria-label="Package"><?php foreach ($pk as $i => $p): ?>
    <label class="mk-pkg"><input type="radio" name="package" value="<?= e($p['key']) ?>"<?= $i === min(1, count($pk) - 1) ? ' checked' : '' ?>><span class="mk-pkg-top"><b><?= e($p['name']) ?></b><b>AED <?= (int) $p['price'] ?></b></span><span class="small muted"><?= e($p['desc']) ?></span><span class="xs muted"><?= icon('clock', 12) ?> <?= (int) $p['days'] ?>-day delivery</span></label>
  <?php endforeach ?></div><?php endif ?>
  <label class="f mt"><?= $isReq ? 'Why you’d be a good fit' : 'What do you need?' ?></label>
  <textarea class="textarea" name="message" data-ai-writing="body" style="min-height:90px" placeholder="<?= $isReq ? 'Your skills, past projects and when you’re free…' : 'Describe what you need and when…' ?>"></textarea><div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
  <button class="btn btn-primary btn-block btn-lg mt" type="submit"><?= icon($isReq ? 'user-plus' : 'send', 17) ?> <?= $isReq ? 'Apply to join' : 'Request this service' ?></button>
  <?php if (!$isReq): ?>
    <ol class="mk-how"><li><b>Request</b> and chat with the seller</li><li>Get an <b>offer</b>: price, delivery and when you pay</li><li><b>Pay safely</b>: MANBAR holds it until you confirm delivery</li></ol>
    <div class="mk-paywith"><?= pay_logos_row() ?><span class="xs muted">or cash in person</span></div>
  <?php endif ?>
</form>
