<button class="guide-fab" id="guideFab" aria-label="Open MANBAR assistant" aria-expanded="false"><?= icon('sparkles', 20) ?><span>Ask MANBAR AI</span></button>
<section class="guide" id="guide" aria-label="MANBAR assistant">
  <div class="guide-head"><?= icon('sparkles', 26) ?><div class="grow"><h4>MANBAR Assistant</h4><small>Tell me your idea — I’ll point you to the right place</small></div><button class="icon-btn" id="guideClose" style="color:#fff" aria-label="Close"><?= icon('x', 20) ?></button></div>
  <div class="guide-msgs" id="guideMsgs">
    <div class="gm bot">Hi <?= e(explode(' ', current_user()['full_name'])[0]) ?>! 👋 Describe what you want to do — for example <i>“I want to find teammates for a mobile app”</i> or <i>“I can design logos for other students”</i> — and I’ll tell you where to post it.
      <div class="sugg">
        <button class="chip outline sm" data-sugg="I want to find teammates for a mobile app project">Find teammates</button>
        <button class="chip outline sm" data-sugg="I can design logos and posters, 50 AED each">Offer a service</button>
        <button class="chip outline sm" data-sugg="I need career advice from a mentor">Get a mentor</button>
      </div>
    </div>
  </div>
  <form class="guide-foot" id="guideForm"><textarea class="textarea" id="guideIn" rows="1" placeholder="What do you want to share or do?"></textarea><button class="btn btn-primary" type="submit" aria-label="Send"><?= icon('send', 18) ?></button></form>
</section>
<?php if (!empty($showTour)): ?>
<div class="product-tour" id="productTour" role="dialog" aria-modal="true" aria-labelledby="tourTitle" aria-describedby="tourText">
  <div class="tour-shade" data-tour-shade="top"></div>
  <div class="tour-shade" data-tour-shade="right"></div>
  <div class="tour-shade" data-tour-shade="bottom"></div>
  <div class="tour-shade" data-tour-shade="left"></div>
  <div class="tour-focus" id="tourFocus" aria-hidden="true"></div>
  <div class="tour-card" id="tourCard">
    <div class="tour-kicker"><span id="tourCount">Step 1 of 7</span><span>Getting started</span></div>
    <h2 id="tourTitle">Start with Ask MANBAR AI</h2>
    <p id="tourText">Tell MANBAR what you want to achieve. It can suggest the right tool, improve a draft, and help you decide what to do next.</p>
    <div class="tour-progress" id="tourProgress" aria-hidden="true"></div>
    <div class="tour-actions">
      <button class="btn btn-ghost" id="tourSkip" type="button">Skip tour</button>
      <div class="row">
        <button class="btn btn-ghost" id="tourBack" type="button">Back</button>
        <button class="btn btn-primary" id="tourNext" type="button">Next <?= icon('arrow-right', 15) ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif ?>
