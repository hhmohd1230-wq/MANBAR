<button class="guide-fab" id="guideFab" aria-label="Open MANBAR assistant"><?= icon('sparkles', 20) ?><span>Ask MANBAR AI</span></button>
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
