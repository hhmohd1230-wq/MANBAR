<?php
$title = 'Settings';
$tabs = ['account' => ['Account', 'user', 'Your MANBAR identity'], 'notifications' => ['Notifications', 'bell', 'What we tell you about'],
    'billing' => ['Billing & payments', 'wallet', 'Cards, PayPal, tabby and wallet'], 'appearance' => ['Appearance', 'sun', 'Theme and layout']];
$methodLogo = fn(string $m) => pay_logo(['card' => 'visa', 'applepay' => 'applepay', 'paypal' => 'paypal', 'tabby' => 'tabby', 'cash' => 'cash'][$m] ?? 'visa');
?>
<section class="st-board">
  <span class="st-kicker"><?= icon('settings', 15) ?> Settings</span>
  <h1>Your account, your way</h1>
  <p>Manage your profile, notifications, payment methods and how MANBAR looks.</p>
</section>

<div class="st">
  <nav class="st-nav" aria-label="Settings sections">
    <?php foreach ($tabs as $k => [$label, $ic, $sub]): ?>
      <a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= e(url('settings?tab=' . $k)) ?>" <?= $tab === $k ? 'aria-current="page"' : '' ?>>
        <span class="st-nav-ic"><?= icon($ic, 18) ?></span><span><b><?= e($label) ?></b><small><?= e($sub) ?></small></span>
      </a>
    <?php endforeach ?>
  </nav>

  <div class="st-main">
  <?php if ($tab === 'account'): ?>
    <section class="card st-card">
      <div class="st-id">
        <?= avatar($u, 72) ?>
        <div><h2><?= e($u['full_name']) ?></h2><p class="muted"><?= e($u['headline'] ?: 'MANBAR member') ?></p>
          <div class="st-id-btns"><a class="btn btn-primary btn-sm" href="<?= e(url('profile/edit')) ?>"><?= icon('edit', 15) ?> Edit profile</a><a class="btn btn-ghost btn-sm" href="<?= e(url('me')) ?>"><?= icon('eye', 15) ?> View profile</a></div>
        </div>
      </div>
      <dl class="st-facts">
        <div><dt><?= icon('mail', 15) ?> University email</dt><dd><?= e($u['email']) ?></dd></div>
        <div><dt><?= icon('tag', 15) ?> Student ID</dt><dd><?= e($u['student_id'] ?: '—') ?></dd></div>
        <div><dt><?= icon('shield', 15) ?> Account type</dt><dd><?= e(ucfirst($u['role'])) ?></dd></div>
        <div><dt><?= icon('calendar', 15) ?> Member since</dt><dd><?= e(date('F Y', strtotime($u['created_at']))) ?></dd></div>
      </dl>
      <p class="st-note"><?= icon('info', 15) ?> Your name, email and student ID come from your university account, so they can’t be changed here.</p>
    </section>
    <section class="card st-card">
      <div class="st-head"><h2><?= icon('lock', 18) ?> Sign-in &amp; security</h2></div>
      <div class="st-row"><span><b>Signed in with your AAU account</b><small>MANBAR uses your university login, so there’s no separate password to remember.</small></span><span class="st-ok"><?= icon('check-circle', 16) ?> Verified</span></div>
      <div class="st-row"><span><b>Sign out on this device</b><small>You can sign back in any time with your university account.</small></span>
        <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= icon('logout', 15) ?> Sign out</button></form></div>
    </section>

  <?php elseif ($tab === 'notifications'): ?>
    <form class="card st-card" method="post" action="<?= e(url('settings/notifications')) ?>"><?= csrf_field() ?>
      <div class="st-head"><h2><?= icon('bell', 18) ?> What should we notify you about?</h2><a class="small" href="<?= e(url('notifications')) ?>">See all notifications <?= icon('arrow-right', 14) ?></a></div>
      <p class="muted small st-lead">Turned-off types won’t appear in your bell. Important account messages always come through.</p>
      <div class="st-toggles">
        <?php foreach (NOTIF_GROUPS as $g => [$label, $desc, $ic, $tone]): $on = ($prefs['notif_' . $g] ?? '1') !== '0'; ?>
          <label class="st-toggle tone-<?= e($tone) ?>">
            <span class="st-tg-ic"><?= icon($ic, 18) ?></span>
            <span class="st-tg-text"><b><?= e($label) ?></b><small><?= e($desc) ?></small></span>
            <input type="checkbox" name="notif[<?= e($g) ?>]" value="1" <?= $on ? 'checked' : '' ?>><span class="st-switch" aria-hidden="true"></span>
          </label>
        <?php endforeach ?>
      </div>
      <div class="st-actions"><button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> Save notification settings</button></div>
    </form>

  <?php elseif ($tab === 'billing'): ?>
    <?php if ($wallet): ?>
    <section class="st-wallet">
      <div class="st-wallet-main"><span><?= icon('wallet', 16) ?> Wallet balance</span><b>AED <?= number_format($wallet['available']) ?></b><small>ready to cash out</small></div>
      <dl><div><dt>On hold</dt><dd>AED <?= number_format($wallet['held']) ?></dd></div><div><dt>Total earned</dt><dd>AED <?= number_format($wallet['earned']) ?></dd></div></dl>
      <div class="st-wallet-btns"><a class="btn st-wbtn-main" href="<?= e(url('marketplace/earnings')) ?>"><?= icon('wallet', 16) ?> Wallet &amp; payouts</a><a class="btn st-wbtn" href="<?= e(url('marketplace/orders')) ?>"><?= icon('briefcase', 16) ?> My orders</a></div>
    </section>
    <?php endif ?>

    <section class="card st-card">
      <div class="st-head"><h2><?= icon('wallet', 18) ?> Saved payment methods</h2><span class="chip sm"><?= count($methods) ?></span></div>
      <?php if ($methods): ?>
        <div class="st-methods">
          <?php foreach ($methods as $m): $exp = $m['kind'] === 'card' ? sprintf('%02d/%02d', $m['exp_month'], $m['exp_year'] % 100) : ''; ?>
            <div class="st-method">
              <div class="st-cc st-cc-<?= e($m['kind'] === 'card' ? $m['brand'] : $m['kind']) ?>">
                <span class="st-cc-top"><?= $m['kind'] === 'card' ? '<span class="st-cc-chip"></span>' : '<span></span>' ?><?= pay_logo($m['kind'] === 'card' ? $m['brand'] : $m['kind']) ?></span>
                <span class="st-cc-num"><?= $m['kind'] === 'card' ? '•••• •••• •••• ' . e($m['last4']) : e($m['hint']) ?></span>
                <span class="st-cc-row"><span><?= e($m['kind'] === 'card' ? mb_strtoupper($m['holder']) : ($m['kind'] === 'paypal' ? 'PayPal account' : 'Pay in 4, no interest')) ?></span><span><?= e($exp) ?></span></span>
              </div>
              <div class="st-method-foot">
                <span><b><?= e(billing_label($m)) ?></b><?php if ($m['is_default']): ?> <span class="st-default"><?= icon('check', 12) ?> Default</span><?php endif ?></span>
                <span class="st-method-acts">
                  <?php if (!$m['is_default']): ?><form method="post" action="<?= e(url('settings/billing/' . $m['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="default"><button class="btn btn-ghost btn-sm" type="submit">Make default</button></form><?php endif ?>
                  <form method="post" action="<?= e(url('settings/billing/' . $m['id'])) ?>" data-confirm="Remove <?= e(billing_label($m)) ?>?"><?= csrf_field() ?><input type="hidden" name="do" value="remove"><button class="btn btn-ghost btn-sm st-danger" type="submit" aria-label="Remove <?= e(billing_label($m)) ?>"><?= icon('trash', 15) ?></button></form>
                </span>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <div class="st-empty"><?= pay_logos_row() ?><b>No payment methods yet</b><span class="small muted">Add a card, PayPal or tabby to check out faster on courses and marketplace orders.</span></div>
      <?php endif ?>
    </section>

    <section class="card st-card" id="add-method">
      <div class="st-head"><h2><?= icon('plus', 18) ?> Add a payment method</h2></div>
      <div class="st-kinds" role="tablist">
        <button type="button" class="on" data-kind="card"><?= pay_logo('visa') . pay_logo('mastercard') . pay_logo('amex') ?> Card</button>
        <button type="button" data-kind="paypal"><?= pay_logo('paypal') ?></button>
        <button type="button" data-kind="tabby"><?= pay_logo('tabby') ?></button>
      </div>

      <form class="st-add" method="post" action="<?= e(url('settings/billing')) ?>" data-billing-form data-kind="card"><?= csrf_field() ?>
        <input type="hidden" name="kind" value="card"><input type="hidden" name="brand"><input type="hidden" name="last4"><input type="hidden" name="exp_month"><input type="hidden" name="exp_year"><input type="hidden" name="holder">
        <div class="st-add-grid">
          <div class="st-cc st-cc-preview st-cc-visa" data-cc-preview>
            <span class="st-cc-top"><span class="st-cc-chip"></span><span data-cc-logo><?= pay_logo('visa') ?></span></span>
            <span class="st-cc-num" data-cc="num">•••• •••• •••• ••••</span>
            <span class="st-cc-row"><span data-cc="name">YOUR NAME</span><span data-cc="exp">MM/YY</span></span>
          </div>
          <div class="st-fields">
            <div class="field st-span2"><label class="f" for="stNum">Card number</label><input class="input" id="stNum" inputmode="numeric" autocomplete="off" maxlength="23" placeholder="1234 5678 9012 3456" data-cf="num"></div>
            <div class="field st-span2"><label class="f" for="stName">Name on card</label><input class="input" id="stName" autocomplete="off" maxlength="60" placeholder="As written on the card" data-cf="name"></div>
            <div class="field"><label class="f" for="stExp">Expiry</label><input class="input" id="stExp" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/YY" data-cf="exp"></div>
            <div class="field"><label class="f" for="stCvc">CVC</label><input class="input" id="stCvc" inputmode="numeric" autocomplete="off" maxlength="4" placeholder="123" data-cf="cvc"></div>
          </div>
        </div>
        <p class="st-err" data-cf-err role="alert" hidden></p>
        <div class="st-add-foot">
          <label class="st-check"><input type="checkbox" name="make_default" value="1"> Make this my default</label>
          <button class="btn btn-ghost btn-sm" type="button" data-cf-test><?= icon('sparkles', 14) ?> Use the test card</button>
          <button class="btn btn-primary" type="submit"><?= icon('lock', 16) ?> Save card</button>
        </div>
      </form>

      <form class="st-add" method="post" action="<?= e(url('settings/billing')) ?>" data-kind="paypal" hidden><?= csrf_field() ?>
        <input type="hidden" name="kind" value="paypal">
        <div class="field"><label class="f" for="stPp">PayPal email</label><input class="input" id="stPp" type="email" name="email" placeholder="you@example.com" required autocomplete="email"></div>
        <p class="small muted">In live mode you’ll log in to PayPal to approve MANBAR. We only keep a masked hint of the email.</p>
        <div class="st-add-foot"><label class="st-check"><input type="checkbox" name="make_default" value="1"> Make this my default</label><button class="btn btn-primary" type="submit"><?= icon('link', 16) ?> Connect PayPal</button></div>
      </form>

      <form class="st-add" method="post" action="<?= e(url('settings/billing')) ?>" data-kind="tabby" hidden><?= csrf_field() ?>
        <input type="hidden" name="kind" value="tabby">
        <div class="field"><label class="f" for="stTb">Mobile number</label><input class="input" id="stTb" name="phone" inputmode="tel" placeholder="05X XXX XXXX" required autocomplete="tel"></div>
        <p class="small muted">Split purchases of AED 20 or more into 4 interest-free payments. tabby checks eligibility in seconds.</p>
        <div class="st-add-foot"><label class="st-check"><input type="checkbox" name="make_default" value="1"> Make this my default</label><button class="btn btn-primary" type="submit"><?= icon('link', 16) ?> Link tabby</button></div>
      </form>
      <p class="st-note"><?= icon('shield', 15) ?> MANBAR never stores your full card number or CVC: only the brand, the last 4 digits and the expiry. Demo mode: nothing is charged.</p>
    </section>

    <section class="card st-card">
      <div class="st-head"><h2><?= icon('file', 18) ?> Billing history</h2><span class="small muted">Your payments for courses and orders</span></div>
      <?php if ($history): ?>
        <div class="st-history">
          <?php foreach ($history as $h): ?>
            <div class="st-hrow"><span class="st-hic"><?= icon($h['kind'] === 'course' ? 'book' : 'store', 16) ?></span>
              <span class="st-hmain"><b><?= e(excerpt($h['title'], 60)) ?></b><small><?= e(date('j M Y', strtotime($h['created_at']))) ?> · <?= e($h['ref'] ?: '—') ?></small></span>
              <span class="st-hlogo"><?= $methodLogo($h['method']) ?></span>
              <span class="st-hamt">AED <?= number_format((float) $h['amount']) ?><small class="st-hst st-<?= e($h['status']) ?>"><?= e(['held' => 'On hold', 'released' => 'Paid', 'paid' => 'Paid', 'cash' => 'Cash', 'refunded' => 'Refunded', 'pending' => 'Pending'][$h['status']] ?? $h['status']) ?></small></span>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?><p class="muted small">No payments yet. Courses and orders you pay for will be listed here.</p><?php endif ?>
    </section>

  <?php else: ?>
    <section class="card st-card">
      <div class="st-head"><h2><?= icon('sun', 18) ?> Theme</h2></div>
      <div class="st-choices" data-theme-choices>
        <button type="button" class="st-choice" data-theme-choice="light"><span class="st-prev st-prev-light"><i></i><i></i><i></i></span><b><?= icon('sun', 15) ?> Light</b></button>
        <button type="button" class="st-choice" data-theme-choice="dark"><span class="st-prev st-prev-dark"><i></i><i></i><i></i></span><b><?= icon('moon', 15) ?> Dark</b></button>
      </div>
    </section>
    <section class="card st-card st-hide-s">
      <div class="st-head"><h2><?= icon('panel-left-close', 18) ?> Sidebar</h2></div>
      <div class="st-choices" data-sidebar-choices>
        <button type="button" class="st-choice" data-sidebar-choice="open"><span class="st-prev st-prev-side"><i></i><i></i><i></i></span><b>Full menu</b></button>
        <button type="button" class="st-choice" data-sidebar-choice="collapsed"><span class="st-prev st-prev-side is-compact"><i></i><i></i><i></i></span><b>Icons only</b></button>
      </div>
      <p class="muted small">You can also use the round button on the edge of the sidebar.</p>
    </section>
  <?php endif ?>
  </div>
</div>
